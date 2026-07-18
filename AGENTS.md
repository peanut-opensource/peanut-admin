# Peanut Admin Agent Contract

This repository is the clean implementation home for Peanut Admin.

## Required Reading

Before changing files, read in order:

1. `README.md`
2. `docs/README.md`
3. `docs/content-status.json`
4. `docs/status/index.md`
5. `docs/status/runtime-operation-coverage.json`
6. The task-specific files named by the controlling prompt.

## Current Boundary

- Work only on the explicitly assigned P0 task.
- Keep each write task in one independently reviewable commit.
- Do not create runtime code before its task is approved.
- Do not copy code, Git history, schemas, or documents from any legacy framework repository.
- Do not add product-specific business logic, names, tables, pages, or examples.
- Do not install dependencies without an accepted dependency decision record.
- Prefer mature libraries when an accepted dependency exists; do not recreate established infrastructure without a recorded reason.

## Runtime Remediation Stop Line

- The historical D04 commit `f351a21` is not a qualified P0 Runtime or a downstream-consumption baseline.
- The remediation history contains implementation evidence through R07 and the revised documentation and recovery gates; commit subjects alone do not prove qualification.
- A candidate is qualified only when a fresh D04 aggregate check and the fixed-commit D05 nine-role review are both recorded against the same resulting tree.
- Do not merge a remediation candidate, publish packages, create a tag or release, or provide a downstream-consumption baseline without the required qualification evidence and separate approval.
- The Runtime tree fixed at `d26186dfb23af34c62c58b4da94fea77bd63d724` and the D05 closure at `b010803ccd0c99179c5f7b35fb7bd89b177ea455` satisfy that evidence requirement. The 2026-07-18 approval permits promotion to `dev` and exact-commit private downstream validation only.
- External Module hosting and isolated Tenant Clients are separately qualified for exact-commit private downstream validation at `0ab02a9b735ba9f4c23509cb366b9bf04039ebf8`; see `docs/reviews/external-host-consumption-qualification.md`.
- That approval does not permit a tag, GitHub Release, package publication, production claim, or consumption of later unqualified Runtime changes.
- Do not add product-specific business models, tables, pages, names, or workflows to the Kernel, reusable packages, internal starter, or fictional examples.

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
