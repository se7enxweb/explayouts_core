# explayouts_core TODO

- Services perform no input validation or permission checks; callers must validate. Consider optional validation similar to upstream `netgen/layouts-core` validators.
- No database transactions around multi-row operations (`copy()`, `delete()` cascade across zones/blocks/rules); a failure mid-way can leave partial data.
- `expLayoutsCoreLayoutService::load()` ignores draft/published distinction unless a status is passed explicitly; the draft workflow relies on callers picking the right method.
- Upstream concepts not ported: layout archiving/restoring, translations on the service level, transaction-wrapped publish.
- No automated tests.
