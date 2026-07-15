# Peanut Admin Agent Contract

This repository is the clean implementation home for Peanut Admin.

## Required Reading

Before changing files, read in order:

1. `README.md`
2. `docs/README.md`
3. `docs/content-status.json`
4. The task-specific files named by the controlling prompt

## Current Boundary

- Work only on the explicitly assigned P0 task.
- Keep each write task in one independently reviewable commit.
- Do not create runtime code before its task is approved.
- Do not copy code, Git history, schemas, or documents from any legacy framework repository.
- Do not add product-specific business logic, names, tables, pages, or examples.
- Do not install dependencies without an accepted dependency decision record.
- Prefer mature libraries when an accepted dependency exists; do not recreate established infrastructure without a recorded reason.

## Safety Rules

- Treat tenant isolation, authorization, audit, and module boundaries as fail-closed contracts.
- Never add a super-user flag, tenant-scope bypass, silent fallback, or test-only production bypass.
- Never expose passwords, tokens, cookies, secrets, private paths, or personal data in logs or commits.
- Do not use destructive Git commands or rewrite shared history.
- Do not skip, weaken, or remove checks to make a task pass.

## Task Execution

1. Confirm the repository, branch, clean worktree, and prerequisite commit.
2. Read the task whitelist and stop line.
3. Add a check or test that fails for the missing capability when the task requires runtime behavior.
4. Modify only whitelisted files.
5. Run task checks, `./scripts/check`, and `git diff --check`.
6. Inspect the staged diff and commit only the current task.
7. Stop after the assigned task.

If facts conflict or the file whitelist is insufficient, stop and report the conflict instead of guessing.
