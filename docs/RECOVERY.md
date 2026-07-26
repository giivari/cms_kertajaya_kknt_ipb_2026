# Recovery Guide
If preflight fails, DO NOT modify the dirty worktree or run destructive git commands (no git reset, git clean, git restore, git stash, git checkout --).
Instead, recover via a verified checkpoint or create a completely new worktree.
Do NOT run migrate:fresh, db:wipe, seed the recovery database, or modify pg_hba.conf.