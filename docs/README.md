# Peanut Admin Documentation

This directory is the public documentation source for Peanut Admin.

## Current Scope

The buildable documentation site contains the P0 foundation overview, core concepts, architecture, engineering standards, task status, dependency decisions, and an explicitly draft API placeholder. Runtime reference pages are added only with the tasks that implement and verify them.

Run the site locally from the repository root:

```bash
corepack pnpm install --frozen-lockfile
corepack pnpm docs:dev
```

Build the same static output used by GitHub Pages:

```bash
./scripts/check-docs
```

## Content Status

Every Markdown document under `docs/` must be registered in `content-status.json`.

Allowed states:

- `canonical`: current implementation fact source;
- `draft`: incomplete and not an implementation fact source;
- `superseded`: historical content excluded from current guidance;
- `generated`: produced from code or schema and not manually edited.

Run `./scripts/check-doc-content-status` from the repository root after adding, moving, or removing documentation.

## Editing Rule

Documentation and implementation must change in the same task when public behavior changes. Generated areas are updated only through their generator.
