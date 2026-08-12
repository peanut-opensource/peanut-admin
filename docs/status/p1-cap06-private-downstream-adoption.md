# P1-CAP06 Private Downstream Adoption Record

## Result

```text
task: P1-CAP06
state: passed
test_owner: P1-CROSS-PRODUCT-DOWNSTREAM-001
scope: single-default-Tenant private sequential adoption
publication_authorized: false
```

Peanut Admin consumed the repaired Alpha.5 private candidate through its public
Revision → Collaboration → Quota → Workflow APIs. The one focused real MySQL
gate passed after static review. The Host proved trusted context, Article
permission and visibility checks, one positive sequential flow, and identical
non-enumerating permission and visibility denials before any Core write.

This result does not prove cross-Tenant Article isolation: Article has no
`tenant_id` until the application's MT02 stage. It also does not claim one
global transaction across the four Runtime authorities. Each authority retains
its own transaction boundary, and the Host compensates a supported pending
quota reservation when a later step fails.

## Immutable Evidence

| Field | Value |
| --- | --- |
| Core source commit | `0f3c0a530f2b6369bf5883b2508f40a79501ed98` |
| Core source tree | `691cf4812d08dc4a3927a78331be3267aa1e9c77` |
| Composer split commit | `ef06da45c9e77ae4b194bfc1f859ec007aa0e022` |
| Composer split tree | `e7beef2fe583ec6778e92b0d88702b1065fdb419` |
| Composer projection | 694 files / 14 PSR-4 roots / SHA-256 `8779231b00f8bd634635c246d569e896e36183f0d0ece8807584a8aa2632dcbd` |
| npm source | `0f3c0a530f2b6369bf5883b2508f40a79501ed98`, subdirectory `packages/web` |
| npm projection | unchanged 72 files / 15 exports / retained SHA-256 `5d01076276a4599682b65fcfde812f5fe201c3e597f2fab38b8ef23cbabe8c80` |
| Application implementation commit | `d27e4b0ca2a17d5c0758bf743a6aead796276fdc` |
| Application final `dev` commit | `bafdf5b5aeb34d63e3b6c21a29817e688783ed21` |
| Application final tree | `8193d219f2109f8d7b7ea0366a575cc2956715e4` |
| Composer lock SHA-256 | `cecdc402534ca26eed343483f2b1902510e36afbd70d0d3f4f4b375c445bdda6` |
| pnpm lock SHA-256 | `9200bda605347fc421832ac31785ce7346d21ab026e2452e49e0a70d8dc7f21f` |

The Composer root manifest names the private candidate branch without a
commit-ref so `composer validate --strict` remains valid; the committed lock is
the immutable authority and fixes both source and dist references to the split
commit above. The npm lock fixes the source commit and `packages/web` path.

## Verification

- Static gate: exact seven-file implementation write set, PHP 8.3 lint, both
  lock identities, and `git diff --check` passed.
- Focused gate: one real MySQL 8.4 run of
  `server/tests/Productization/CrossProductDownstreamAdoptionTest.php` passed on
  the final implementation tree. No CAP01–CAP05 or full local suite was rerun.
- Application PR #23 carried the implementation but had a Composer strict
  validation failure and is not the final CI acceptance evidence.
- Follow-up application PR #24 changed only the Composer manifest constraint
  and lock content hash; PHP 8.3, Web, PC, UniApp and Docs site all passed before
  merge. The focused MySQL gate was not repeated.

## Stop Line

CAP06 authorizes only this private exact-input adoption result. It does not
publish Composer or npm packages, create a tag or Release, move a registry
dist-tag, authorize production, complete MT01/MT02, or form
`PA-DCS-ADOPT-01`. Public Alpha.5 publication remains an independent decision.
