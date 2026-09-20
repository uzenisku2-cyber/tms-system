# Sprint 092 â€“ Financial settlement accounting period close

This sprint introduces organization- and currency-scoped accounting periods with open, closed, and reopened states. Close and reopen commands use optimistic revision checks, idempotency keys, and append-only audit events.

A closed period rejects new canonical posting executions linked to an accounting handoff. Reversals remain allowed, and correction replacement executions remain allowed because they intentionally have no direct handoff link and retain immutable correction provenance.

Existing executions, entries, handoffs, reversals, corrections, payments, reconciliations, statements, billing documents, and bank evidence are not mutated by a period transition.

Installation and validation use no persistent database.
