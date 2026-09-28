<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecoverAdminMfa extends Command
{
    protected $signature = 'admin:recover-mfa
        {email : Exact email address of the sole active administrator}
        {--force : Explicitly authorize non-interactive execution}';

    protected $description = 'Operator-only MFA enrollment reset for the sole Admin; password is unchanged';

    protected $help = 'For an authorized server operator after identity verification and a coordinated checkpoint. '
        .'Resets only MFA enrollment, revokes current Admin sessions, and requires normal password login and fresh MFA enrollment. '
        .'It does not reset the password or replace the separate backup/recovery policy. '
        .'Interactive confirmation is required unless --force is supplied with the exact Admin email.';

    public function handle(): int
    {
        if (config('session.driver') !== 'database'
            || (config('session.connection') !== null
                && config('session.connection') !== DB::getDefaultConnection())) {
            $this->error('Recovery requires database sessions on the primary application connection. No state changed.');

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');
        $admins = Admin::withTrashed()->get();

        if ($admins->count() !== 1 || $admins->first()->trashed()
            || $admins->first()->email !== $email) {
            $this->error('Administrator identity or single-account state does not match. No state changed.');

            return self::FAILURE;
        }

        $admin = $admins->first();
        if (blank($admin->getAppAuthenticationSecret())) {
            $this->error('The administrator is not enrolled in MFA. No state changed.');

            return self::FAILURE;
        }

        $this->line('Administrator: '.$admin->email);
        $this->line('MFA: enrolled');
        $this->warn('This clears the current MFA enrollment and revokes existing Admin sessions. The password is unchanged.');

        if (! $this->option('force')
            && (! $this->input->isInteractive() || ! $this->confirm('Continue with authorized MFA recovery?', false))) {
            $this->warn('Recovery declined. No state changed.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($admin, $email): void {
                if (DB::connection()->getDriverName() !== 'pgsql') {
                    throw new \LogicException('PostgreSQL is required for the recovery lock.');
                }

                // Share provisioning's transaction lock so account-count changes
                // cannot interleave with the final single-Admin check.
                DB::select('select pg_advisory_xact_lock(?)', [740_210_001]);

                $lockedAdmins = Admin::withTrashed()->orderBy('id')->lockForUpdate()->get();
                if ($lockedAdmins->count() !== 1 || $lockedAdmins->first()->trashed()
                    || $lockedAdmins->first()->id !== $admin->id
                    || $lockedAdmins->first()->email !== $email
                    || blank($lockedAdmins->first()->getAppAuthenticationSecret())) {
                    throw new \LogicException('Administrator state changed during recovery.');
                }

                $lockedAdmin = $lockedAdmins->first();
                $lockedAdmin->app_authentication_secret = null;
                $lockedAdmin->mfa_recovery_version = (int) $lockedAdmin->mfa_recovery_version + 1;
                $lockedAdmin->save();

                DB::table(config('session.table'))->where('user_id', $lockedAdmin->id)->delete();

                // A CLI operator is not an authenticated Admin actor. Keep that
                // attribution null; the external change ticket identifies them.
                AuditLog::create([
                    'admin_id' => null,
                    'event_type' => 'admin_mfa_recovery',
                    'subject_type' => Admin::class,
                    'subject_id' => $lockedAdmin->id,
                    'new_values' => ['source' => 'cli_operator', 'outcome' => 'success'],
                ]);
            });
        } catch (Throwable) {
            // Do not print DB errors: connection strings and bound values can
            // contain sensitive material. The transaction rolls back all writes.
            $this->error('MFA recovery failed; no successful recovery was committed. Review protected application logs.');

            return self::FAILURE;
        }

        $this->info('MFA enrollment reset. Existing sessions are revoked; sign in with the unchanged password and enroll a new authenticator.');

        return self::SUCCESS;
    }
}
