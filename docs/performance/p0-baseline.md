# P0 Performance Baseline

The P0 gate measures security-critical paths; it does not claim a production QPS capacity. The benchmark installs a fresh reference profile into MySQL, seeds 5,000 typed targets, verifies result correctness, and then records p50, p95, and p99 latency.

## Fixed Dataset And Runtime

- MySQL image: `mysql:8.4.10`.
- PHP baseline runtime: `8.3.24`.
- Baseline host: Darwin arm64 development host.
- Three complete warm-container measurements were taken on 2026-07-16.
- The checked-in p95 for each scenario is the highest observed p95 across those runs.
- CI uses the same lock files, PHP minor version, database image, fixture size, sample counts, and script.

The versioned machine-readable values are in `tests/performance/p0-baseline.json`. `scripts/test-performance` fails when the current p95 exceeds the recorded value by more than 20 percent.

## Recorded P95

| Scenario | Dataset | Baseline p95 | Query bound |
| --- | ---: | ---: | ---: |
| Tenant login | 12 independent sessions | 272.413 ms | Password hashing and transaction |
| Tenant refresh | 10 one-time rotations | 13.004 ms | One rotation transaction |
| Tenant context | 30 validations | 1.635 ms | Session and state validation |
| Typed targets | 10 IDs | 1.421 ms | 1 query |
| Typed targets | 500 IDs | 5.052 ms | 1 query |
| Typed targets | 5,000 IDs | 55.754 ms | 10 queries of at most 500 IDs |
| Shared-master scope | 10 typed targets | 1.070 ms | 1 query |

The typed-target scenarios also assert identical membership results and reject a missing target. The shared-master scenario uses one truth table and one scope table and verifies that only the typed-target-visible candidate is returned.

## Interpretation

These values are regression references, not service-level objectives. A production product must establish its own hardware, dataset, concurrency, cache, and endpoint baselines. A changed runner class requires a separately reviewed baseline update; increasing a number solely to make CI pass is prohibited.
