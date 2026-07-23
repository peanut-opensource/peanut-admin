# Tenant-Private Files

Enable `peanut.file-media` in the product profile and grant the smallest of:

- `peanut.file-media.read` for list, metadata, and private download;
- `peanut.file-media.create` for one-file multipart upload;
- `peanut.file-media.delete` for optimistic archive.

The reference configuration is `backend/config/file-media.php`. Set
`FILE_MEDIA_STORAGE_ROOT` to an absolute private directory outside every Web
root. The API never accepts or returns that path. Unknown providers, invalid
roots, symlinks, traversal, failed atomic writes, and missing ready objects fail
closed as `FILE_STORAGE_UNAVAILABLE`.

The Admin Web uses the existing `/app/files` route. There is no separate Demo.
Archived rows remain metadata-only retention records, are excluded from the
default list, and cannot be downloaded.
