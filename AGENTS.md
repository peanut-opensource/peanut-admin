# Peanut Admin Agent Contract

This repository is the clean implementation home for Peanut Admin.

## Required Reading

Before changing files, read in order:

1. `company-rules/core.md` and `company-rules/code-repository.md` when the local company-only directory exists.
2. `README.md`
3. `docs/README.md`
4. `docs/content-status.json`
5. `docs/status/index.md`
6. `docs/status/runtime-operation-coverage.json`
7. `docs/status/p1-execution-baseline.md` for P1 work.
8. The task-specific files named by the controlling prompt.

`company-rules/` is synchronized by CompanyOS for internal work and is intentionally Git-ignored in this public repository. External clones remain governed by this public `AGENTS.md` when that local directory is absent.

## Current Boundary

- Work only on an explicitly assigned P0 or P1 task.
- Keep each write task in one independently reviewable commit.
- Do not create runtime code before its task is approved.
- Do not copy code, Git history, schemas, or documents from any legacy framework repository.
- Do not add product-specific business logic, names, tables, pages, or examples.
- Do not install dependencies without an accepted dependency decision record.
- Prefer mature libraries when an accepted dependency exists; do not recreate established infrastructure without a recorded reason.

## P1 Execution Stop Line

- P1 starts from the fixed input recorded in `docs/status/p1-execution-baseline.md`.
- Historical plans are not executable task definitions. Every P1 Runtime task must name its prerequisite commit, exact file whitelist, schema owner, API contract, security behavior, test owner, and stop line.
- Existing P0 operations and models are inherited. Do not rebuild them under new names or weaken their fail-closed behavior.
- A P1 operation must be classified as `p1` in the Runtime coverage ledger and must have executable test ownership.
- A P1 dependency may be installed only after its decision record is accepted for the exact use case.
- Later P1 commits are not downstream-consumption baselines until a new fixed-commit aggregate qualification and review explicitly approve them.

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

## Verification Policy

- Ordinary reversible work runs the smallest set of unit, integration, type,
  build, static, or documentation checks that covers the changed behavior.
- Tenant isolation, identity, authorization, migrations, deletion, concurrency,
  idempotency, external side effects, and breaking public-contract changes must
  run risk-proportionate focused checks in the task that introduces the risk.
- `./scripts/check` remains the aggregate repository entry point. Run it for a
  fixed Starter v1 milestone, qualification, or release candidate, not as a
  mandatory step after every bounded development task.
- Full browser matrices, clean install and upgrade, backup and restore,
  performance, and independent fixed-commit review belong to milestone
  qualification unless a task directly changes the corresponding boundary.
- Record every deferred aggregate check and the milestone that will run it. A
  failed focused or aggregate check is never hidden or waived.
- A performance failure blocks the affected qualification, release, or
  downstream lock movement. It does not freeze unrelated ordinary feature work.

## Task Execution

1. Confirm the repository, branch, clean worktree, and prerequisite commit.
2. Read the task whitelist and stop line.
3. Add a check or test that fails for the missing capability when the task requires runtime behavior.
4. Modify only whitelisted files.
5. Run the focused and risk-sensitive checks required by this task, run
   `git diff --check`, and record deferred milestone verification.
6. Inspect the staged diff and commit only the current task.
7. Stop after the assigned task.

If facts conflict or the file whitelist is insufficient, stop and report the conflict instead of guessing.
