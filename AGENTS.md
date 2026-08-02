# AI Agent Operating Contract
AI agents MUST NOT rely entirely on automatic reading of this file; prompt executors must explicitly command agents to read AGENTS.md.
Agents MUST strictly adhere to these guardrails:
1. Executor MUST read AGENTS.md before starting work.
2. Agents MUST ONLY work on the specified worktree and branch.
3. Touching other worktrees or backups is strictly prohibited.
4. git add . is PROHIBITED. Use explicit staging (git add <file>).
5. Destructive Git commands are PROHIBITED: no git reset, git clean, git restore, git stash, git checkout --, git commit --amend, or git push --force.
6. Destructive DB commands are PROHIBITED: no migrate:fresh, migrate:refresh, migrate:reset,
ollback, db:wipe, or seeding the recovery/development database.
7. Reading or printing secrets/credentials is PROHIBITED.
8. Modifying .env, dependencies, lockfiles, endor,
ode_modules, or pg_hba.conf without explicit permission is PROHIBITED.
9. Wrappers safe-test.ps1 and safe-build.ps1 are MANDATORY for all test and build executions.
10. One scope, one worktree, one commit.
11. Testing and Build requirements:
    - targeted tests dijalankan terlebih dahulu;
    - full test suite wajib sebelum checkpoint/commit final ketika runtime tersedia dan scope memengaruhi aplikasi;
    - visual approval wajib untuk perubahan visual.
12. Fail-closed: If any output is unexpected or validations fail, abort operations immediately.
13. Recovery MUST be done via verified checkpoints or new worktrees.
14. Local git hooks are NOT an absolute security boundary; server-side branch protections and CI validations are still required.
