<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const REFERENCES = [
        'pages' => ['featured_media_id'],
        'news' => ['featured_media_id'],
        'gallery_albums' => ['cover_media_id'],
        'gallery_album_items' => ['media_id'],
        'documents' => ['file_media_id', 'thumbnail_media_id'],
        'locations' => ['media_id'],
    ];

    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->string('cleanup_status', 30)->nullable()->index();
            $table->string('cleanup_error', 100)->nullable();
            $table->timestampTz('cleanup_started_at')->nullable();
        });

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION guard_managed_media_reference()
RETURNS trigger AS $$
DECLARE
    candidate_id bigint;
    previous_id bigint;
BEGIN
    candidate_id := NULLIF(to_jsonb(NEW) ->> TG_ARGV[0], '')::bigint;
    IF candidate_id IS NULL THEN
        RETURN NEW;
    END IF;

    IF TG_OP = 'UPDATE' THEN
        previous_id := NULLIF(to_jsonb(OLD) ->> TG_ARGV[0], '')::bigint;
        IF candidate_id IS NOT DISTINCT FROM previous_id THEN
            RETURN NEW;
        END IF;
    END IF;

    PERFORM 1
      FROM media
     WHERE id = candidate_id
       AND deleted_at IS NULL
       AND cleanup_status IS DISTINCT FROM 'pending'
       AND cleanup_status IS DISTINCT FROM 'failed'
       AND cleanup_status IS DISTINCT FROM 'completed'
     FOR UPDATE;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Managed media % is archived, deleting, or missing', candidate_id
            USING ERRCODE = '23503';
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
SQL);

        foreach (self::REFERENCES as $table => $columns) {
            foreach ($columns as $column) {
                $trigger = "guard_{$table}_{$column}";
                DB::unprepared("CREATE TRIGGER {$trigger} BEFORE INSERT OR UPDATE OF {$column} ON {$table} FOR EACH ROW EXECUTE FUNCTION guard_managed_media_reference('{$column}')");
            }
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            foreach (self::REFERENCES as $table => $columns) {
                foreach ($columns as $column) {
                    DB::unprepared("DROP TRIGGER IF EXISTS guard_{$table}_{$column} ON {$table}");
                }
            }
            DB::unprepared('DROP FUNCTION IF EXISTS guard_managed_media_reference()');
        }

        Schema::table('media', function (Blueprint $table): void {
            $table->dropColumn(['cleanup_status', 'cleanup_error', 'cleanup_started_at']);
        });
    }
};
