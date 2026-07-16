# P0 Performance Baseline

The P0 gate measures security-critical paths; it does not claim a production QPS capacity. The benchmark installs a fresh reference profile into MySQL, seeds 5,000 typed targets, verifies result correctness, and then records p50, p95, and p99 latency.

## Fixed Dataset And Runtime

- MySQL image: `mysql:8.4.10`.
- PHP baseline runtime: `8.3.24`.
- Baseline host: Darwin arm64 development host.
- Six complete warm-container calibration measurements were taken on 2026-07-16; the final three use the current login and refresh sample counts.
- The checked-in p95 for each scenario is the highest observed p95 with its current operation shape.
- CI uses the same lock files, PHP minor version, database image, fixture size, sample counts, and script.

The versioned machine-readable values are in `tests/performance/p0-baseline.json`. `scripts/test-performance` fails when the current p95 exceeds the recorded value by more than 20 percent.

## Recorded P95

| Scenario | Dataset | Baseline p95 | Query bound |
| --- | ---: | ---: | ---: |
| Tenant login | 20 independent sessions | 194.584 ms | Password hashing and transaction |
| Tenant refresh | 18 one-time rotations | 27.282 ms | One rotation transaction |
| Tenant context | 30 samples x 20 validations | 100.278 ms | Session and state validation |
| Typed targets | 20 operations x 10 IDs | 21.230 ms | 1 query per operation |
| Typed targets | 5 operations x 500 IDs | 29.182 ms | 1 query per operation |
| Typed targets | 5,000 IDs | 90.637 ms | 10 queries of at most 500 IDs |
| Shared-master scope | 20 operations x 10 typed targets | 36.372 ms | 1 query per operation |

The typed-target scenarios also assert identical membership results and reject a missing target. The shared-master scenario uses one truth table and one scope table and verifies that only the typed-target-visible candidate is returned.

## Interpretation

These values are regression references, not service-level objectives. A production product must establish its own hardware, dataset, concurrency, cache, and endpoint baselines. A changed runner class requires a separately reviewed baseline update; increasing a number solely to make CI pass is prohibited.
