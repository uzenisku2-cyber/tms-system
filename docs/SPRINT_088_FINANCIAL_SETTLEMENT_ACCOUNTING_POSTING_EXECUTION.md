# Sprint 088 â€“ Financial settlement accounting posting execution

## Goal

Execute one explicit, organization-scoped and auditable double-entry accounting posting from a prepared settlement accounting handoff.

## Contract

- Only a prepared handoff at the expected revision may be executed.
- One immutable execution is allowed per handoff.
- Idempotency keys are organization-scoped and protected by a command fingerprint.
- Every execution creates exactly one debit and one credit entry for the same positive amount and currency.
- Execution, entries and events are append-only.
- The handoff snapshot remains the authoritative source evidence.

## Boundary

This workflow does not mutate the handoff, payment, reconciliation, settlement statement, billing document or bank evidence. Reversal, correction, external accounting export and tax filing remain separate explicit workflows.
