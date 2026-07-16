# Backup And Recovery Boundary

P0-D01 provides installation and local schema upgrade. The verified backup, restore-to-new-database, clean-install, and measured recovery drill are delivered by P0-D03. Until that gate is complete, Peanut Admin does not claim a built-in recovery workflow.

## Current Operator Requirements

Before an upgrade, use the database platform's mature snapshot or `mysqldump` workflow and verify that the artifact can be restored to a separate database. Keep application secrets, encryption keys, and uploaded files outside the SQL dump and protect them through their own approved backup system.

Never test recovery by overwriting the active database. Restore into a new database name, deploy the matching application release against it, then verify:

- schema and migration versions;
- tenant and platform login;
- tenant isolation and wrong-audience denial;
- Module installation and migration checksums;
- important row counts and fixture hashes;
- application and cache health behavior.

## Recovery Evidence

RPO and RTO must be measured from a real drill. Documentation must record observed values and environment details, not unsupported promises.

The future reference scripts will orchestrate mature MySQL tools; they will not implement a custom database backup engine. Cloud deployment adapters remain provider-specific runbooks.
