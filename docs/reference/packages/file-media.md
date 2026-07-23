# File And Media Package

`peanut-admin/file-media` owns Tenant-private file metadata and the neutral
storage-provider contract. `@peanut-admin/file-media` contributes the guarded
`/app/files` administration page to the existing Admin Web.

The reference Host uses `local-private`, which stores opaque objects below an
absolute private root outside Web roots. It is a development and single-node
adapter. Production object storage, malware scanning, public delivery,
retention deletion, attachments, thumbnails, and resumable uploads are not
claimed by Starter v1.

The five Tenant operations require `peanut.file-media.read`,
`peanut.file-media.create`, or `peanut.file-media.delete`. Tenant identity is
accepted only from trusted context; metadata and storage queries are always
Tenant-scoped. API and audit output never include provider keys, storage keys,
paths, content, account/member IDs, tokens, or infrastructure errors.

Hosts may narrow the 10 MiB upload limit and the default PNG, JPEG, PDF, plain
text, and CSV allow-list. They must not broaden either through untrusted input.
The server detects MIME and SHA-256 from the bytes and treats the original name
only as normalized display metadata.

See the [C02 File And Media contract](../../status/starter-v1-c02-file-media-contract.md)
for the exact schema, concurrency, compensation, API, and development stop
lines.
