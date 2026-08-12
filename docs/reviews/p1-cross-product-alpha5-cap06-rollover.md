# P1 Alpha.5 CAP06 Remediation Rollover

## Decision

CAP06 exposed two MySQL 8.4 compatibility defects after the original CAP05
qualification: the ArtifactRevision schema used an unsupported check over an
auto-increment identifier, and three Workflow statements reused named PDO
placeholders under native prepares. Core PRs #18–#21 bounded, verified and
merged those repairs. This record fixes the repaired private-adoption input; it
does not authorize public publication or repeat the completed CAP01–CAP05 gates.

## Fixed Candidate

| Field | Value |
| --- | --- |
| Source commit | `db348c783ff8620fd77615294c946a36bca25a49` |
| Source tree | `2511693481eaa811656e462a5e6640003a208836` |
| Composer candidate | `peanut-admin/core@0.1.0-alpha.5` |
| Composer projection | 694 files, 14 PSR-4 roots, SHA-256 `d079bf25aafa90c039481eabee722191012f4d2b88e39696acdc350930dd3d6a` |
| npm candidate | `@peanut-admin/admin@0.1.0-alpha.5` |
| npm projection | unchanged 72 files / 15 exports, retained SHA-256 `5d01076276a4599682b65fcfde812f5fe201c3e597f2fab38b8ef23cbabe8c80` |

The Composer digest is from `git archive --format=tar HEAD packages/php` at
the fixed commit. The one rollover projection preflight passed its manifest,
product-neutrality, isolated PHP 8.3 consumer, file-count and PSR-4 checks. The
npm subtree is byte-identical to the CAP05 source and was not regenerated.

## Boundary

CAP06 may regenerate only the generated Composer split candidate and move the
application locks to these exact inputs. Tags, Releases, Packagist, npm registry
writes and stable compatibility claims remain outside this record. The
conflicting local draft `f7b4dd5` is not an input.
