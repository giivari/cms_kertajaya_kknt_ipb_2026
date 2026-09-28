# Managed media legacy cutover

The application route `media.derivative` is the authoritative delivery path. New derivatives are written to `storage/app/private/derivatives/<media-id>/` and are never published through `public/storage`.

Before deployment, run the read-only inventory and save its JSON output outside the release directory:

```powershell
php artisan media:lifecycle-inventory --json
```

This command is read-only but inspects the configured database **and public storage root**; its output can reveal media filenames/paths. Run it only on the explicitly authorized installation, restrict access to the output, and do not treat `--env=testing` alone as filesystem isolation. P8 did not run it on working media.

Legacy rows whose active derivative uses the `public` disk remain readable through the controlled route for compatibility. Their old `storage/app/public/media/**` bytes are also reachable through `/storage/media/**` while the standard `public/storage` symlink or an equivalent web-server alias remains exposed. Source deployment alone therefore does not complete D07 for those known URLs.

The operator cutover is:

1. Back up the database, `storage/app/private`, and `storage/app/public/media` together.
2. Record the inventory and verify that every public derivative path belongs to a current or retired database identity.
3. Deploy the application and migrations, then verify controlled URLs for active media.
4. Configure Nginx/the origin server to deny the historical managed namespaces before removing any legacy byte. For the standard deployment, place these locations before the general PHP/static rules:

   ```nginx
   location ^~ /storage/media/ { return 404; }
   location ^~ /storage/originals/ { return 404; }
   ```

   Review any additional directory reported by `legacy_public_original_records` rather than broadening the deny rule to unrelated public storage.
5. Verify that active controlled URLs still work and archived/unknown media return 404.
6. Plan a separate reviewed copy-and-pointer transition for legacy active derivatives to the private disk. This repository intentionally provides no bulk mover or destructive cleanup.

Rollback must keep `/storage/media/` and `/storage/originals/` denied. Do not restore a rule that reopens legacy static access. An application rollback is blocked until the previous release can serve the required active media through an equivalently protected route; otherwise keep the controlled-serving release or return a maintenance response while legacy bytes remain private. CDN/browser copies already fetched before the deny rule cannot be remotely erased; use the provider's cache purge control if immediate edge invalidation is required.

Failed permanent deletion remains an archived row with a cleanup status. Inspect one exact record with `php artisan media:cleanup <id>`; retrying file deletion requires the explicit `--execute` flag. Never use age alone as evidence that an unclassified inventory file is safe to delete.
