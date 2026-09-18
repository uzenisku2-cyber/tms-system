# Sprint 087 â€“ Financial settlement accounting posting handoff

## Goal

Prepare an explicit, organization-scoped and auditable accounting posting handoff from the current confirmed settlement bank-payment reconciliation.

## Contract

- The command requires an active settlement bank payment and a confirmed reconciliation.
- Payment and reconciliation revisions are checked optimistically under row locks.
- One immutable handoff is allowed per reconciliation revision.
- Idempotency keys are organization-scoped and protected by a command fingerprint.
- The handoff snapshots the payment amount, currency, direction, bank reference, posting date and source revisions.
- Handoff records and events are append-only.

## Boundary

This sprint does not post to a ledger, create an accounting entry, export to an accounting system, or mutate the payment, reconciliation, settlement statement, billing document or bank evidence. Those actions require separate explicit workflows.
