# Peanut Admin Documentation

This directory is the public documentation source for Peanut Admin.

## Current Scope

P0-A01 contains only this documentation index and the machine-readable content status registry. Architecture guides, API references, package documentation, and tutorials are added by later approved tasks.

## Content Status

Every Markdown document under `docs/` must be registered in `content-status.json`.

Allowed states:

- `canonical`: current implementation fact source;
- `draft`: incomplete and not an implementation fact source;
- `superseded`: historical content excluded from current guidance;
- `generated`: produced from code or schema and not manually edited.

Run `../scripts/check-doc-content-status` after adding, moving, or removing documentation.

## Editing Rule

Documentation and implementation must change in the same task when public behavior changes. Generated areas are updated only through their generator.
