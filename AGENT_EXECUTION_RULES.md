# Peanut Admin Core Agent Execution Rules

This file is the execution-policy entry point for Peanut Admin Core. Repository
state, Runtime boundaries, and task-specific stop lines remain in `AGENTS.md`
and the status documents that it names.

## 1. Facts And Completion Evidence

- Read the relevant implementation before judging architecture, behavior, or completeness. Separate verified facts from unverified inference.
- Chat, plans, task titles, status retellings, and notifications are not completion evidence. Code claims require a diff, exact commit/tree, check, or runtime result.
- Do not conflate development-complete, qualified, release-ready, downstream-consumable, and production-ready.
- A final report names changed files, the full commit, checks run, checks not run, risks, and remaining work.

## 2. Authorization And Stop Lines

- Complete ordinary reversible work within the approved scope without inventing a human gate.
- Confirm before real financial transactions, destructive production-data changes, Git history rewrites, unknown external side effects, or an explicitly retained human gate.
- Use credentials only for their authorized target action. Never print, persist, or commit them.
- Before editing, confirm the repository, baseline, branch, and exact write set. Preserve unrelated work.

## 3. Minimum Blocking Closure

- Classify every failure or wait and block only its dependency closure:
  - security, Tenant isolation, authorization, data-corruption, and core behavior failures block the affected implementation and direct consumers;
  - Schema/API contracts and immutable dependency identity block dependent adoption, merge, or release;
  - formatting, documentation, dependency metadata, and mechanical CI failures block only the current PR closure;
  - external Registry, credential, or long-CI waits block only the corresponding external action and final consumer probe.
- Independent work may continue, but continuation is not acceptance. Keep failing candidates on feature or stacked-fix branches; never merge red required checks into `dev` or `main`.
- Continue work that does not consume the blocked artifact and has disjoint file ownership. Prefer one critical path and at most two useful independent lines; state the concrete dependency when no parallel work exists.
- Audit, preparation, research, and “about to start” are not material progress by themselves. Produce a diff, commit, PR, fixture, executable contract, command result, or updated recovery pointer.

## 4. Verification And Reporting

- Run each acceptance condition once with the smallest sufficient check. Manifest and lock changes include the CI-equivalent strict validator and exact identity verification.
- Deduplicate by failure cause. Rerun only the failed group within the task's retry budget; do not rerun passed gates or widen the suite by default.
- Tests must exercise the claimed branch. Never substitute weaker permission, Tenant, transaction, or database scenarios for the claimed evidence.
- Report gate start, material state change, failure, timeout, and completion. Name the repository, PR, and evidence; do not repeat unchanged polling output.

## 5. Git, Concurrency, And Worktrees

- A file has one owner at a time. Shared databases, containers, caches, services, and other mutable resources also require one owner and project isolation.
- Long-running and parallel write tasks use isolated branches and worktrees. Temporary directories are not durable facts; preserve unknown or dirty worktrees.
- Do not use `git add .`, `git commit -a`, `git reset --hard`, or blind overwrites. Inspect the staged file list and run `git diff --cached --check`.
- Never commit secrets, cookies, caches, temporary logs, test data, or build output.
- Public interfaces and downstream adoption pin the full commit/tree, immutable source, compatibility statement, and verification evidence, never only a branch or moving HEAD.

## 6. Technical Discipline

- Read neighboring implementation before editing and follow the existing stack, naming, error handling, and call patterns.
- Do not opportunistically refactor, upgrade dependencies, or expand features. A new dependency requires an accepted decision for its exact use case.
- Namespace database, cache, queue, and file resources by project. Use unique run IDs for disposable resources and never clean another project's state.
- Keep service location and credentials environment-configurable; do not commit personal paths, real passwords, or fixed development hosts as application defaults.
- Do not override framework or base-class methods with incompatible signatures; use supported extension points for cross-cutting behavior.
