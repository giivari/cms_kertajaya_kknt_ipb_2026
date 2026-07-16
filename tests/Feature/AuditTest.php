<?php

use App\Models\AuditLog;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('audit logs track lifecycle events and exclude sensitive data', function () {
    $news = clone new News; // Avoid triggering immediately? We create it first.

    // 1. Create
    $news = News::create([
        'title' => 'Audit Test',
        'slug' => 'audit-test',
        'content' => 'Some content',
        'status' => 'draft',
    ]);

    $createLog = AuditLog::where('subject_type', News::class)
        ->where('subject_id', $news->id)
        ->where('event_type', 'created')
        ->first();

    expect($createLog)->not->toBeNull();

    // 2. Update / Publish
    $news->update(['status' => 'published', 'published_at' => now()]);

    $updateLog = AuditLog::where('subject_type', News::class)
        ->where('subject_id', $news->id)
        ->where('event_type', 'updated')
        ->first();

    expect($updateLog)->not->toBeNull();

    // 3. Delete (Soft)
    $news->delete();

    $deleteLog = AuditLog::where('subject_type', News::class)
        ->where('subject_id', $news->id)
        ->where('event_type', 'deleted')
        ->first();

    expect($deleteLog)->not->toBeNull();

    // 4. Restore
    $news->restore();

    $restoreLog = AuditLog::where('subject_type', News::class)
        ->where('subject_id', $news->id)
        ->where('event_type', 'restored')
        ->first();

    expect($restoreLog)->not->toBeNull();

    // Check that sensitive data is excluded from old_values and new_values
    $logs = AuditLog::where('subject_type', News::class)
        ->where('subject_id', $news->id)
        ->get();

    foreach ($logs as $log) {
        $old = json_encode($log->old_values);
        $new = json_encode($log->new_values);

        // They should not contain APP_KEY, passwords, etc.
        // In this case, the payload shouldn't include environment variables, but let's assert.
        expect($old)->not->toContain(env('APP_KEY', 'some-fallback-key'));
        expect($new)->not->toContain(env('APP_KEY', 'some-fallback-key'));

        expect($old)->not->toContain('WATERMARK_SIGNING_KEY');
        expect($new)->not->toContain('WATERMARK_SIGNING_KEY');

        expect($old)->not->toContain('password');
        expect($new)->not->toContain('password');
    }
});
