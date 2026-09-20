# Sprint 093 - Financial settlement accounting period administration UI

This sprint adds an organization-scoped administration workspace over the existing accounting-period lifecycle.

- Read API supports status, currency and overlapping-date filters plus paginated results.
- Detail exposes append-only accounting-period event history.
- The workspace can open, close and reopen periods through the existing idempotent optimistic-revision commands.
- Read access requires `compensation.view`; mutations continue to require `compensation.manage`.
- The read layer does not mutate periods, events, postings, settlements, payments or billing documents.
- Closing a period continues to block canonical posting dates through the existing accounting-period domain guard.
