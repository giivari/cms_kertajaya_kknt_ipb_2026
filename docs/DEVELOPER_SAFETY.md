# Developer Safety Guardrails
Workflow: Start -> Implement -> Validate -> safe-test/safe-build -> Visual Approval -> PreCommit -> Explicit staging -> Commit.
- .guardrails.local.json: Local configurations (ignored).
- Linked Worktrees: Git config like core.hooksPath affects ALL worktrees. Hooks are local boundaries, not ultimate server-side security.
- Git hooks do NOT intercept --no-verify or destructive commands executed outside git push/git commit.
- AI prompts must explicitly instruct agents to read AGENTS.md.

## Example Safe PowerShell Workflow
`powershell
# Run a safe target test
& .\scripts\guardrails\safe-test.ps1 -TestPaths @('tests/Feature/ExampleTest.php')

# Run a safe build
& .\scripts\guardrails\safe-build.ps1
`
"@

# 7. RECOVERY.md
Write-Utf8NoBom 'docs/RECOVERY.md' @"
# Recovery Guide
- If preflight or validation fails, STOP immediately.
- Record the branch, HEAD, and git status.
- Do NOT modify the dirty worktree to attempt fixing the failure.
- Create a completely new worktree from a verified checkpoint to recover functionality.
- Do NOT run destructive Git operations (git reset, git clean, git checkout --) or database operations (db:wipe, migrate:fresh) on the broken environment.
- Escalate the failure to the reviewer/team lead.