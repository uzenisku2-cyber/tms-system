# Sprint 090 â€“ Financial settlement accounting posting correction

This sprint adds an organization-scoped, idempotent correction command for an executed accounting posting that already has a completed reversal.

The correction creates one balanced replacement posting execution and immutable posting entries. It links the original execution, reversal, and replacement execution through an append-only correction record and event.

The original execution, reversal, handoff, payment, reconciliation, settlement statement, billing document, and bank evidence remain unchanged. Conflicting idempotency reuse, stale revisions, unbalanced entries, missing reversals, duplicate corrections, and cross-organization access are rejected.

The canonical one-handoff-to-one-execution invariant remains intact. A correction replacement execution has no direct handoff foreign key; its provenance is carried by the immutable correction links and source snapshot.

Persistent PostgreSQL is not used by installation or validation scripts.
