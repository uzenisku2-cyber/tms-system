# Sprint 082 - Financial settlement bank payment administration UI

## Scope

Sprint 082 exposes the existing settlement bank-match candidate and bank-payment lifecycles in the financial settlement administration detail. The read model derives paid and remaining minor-unit amounts from active settlement payments and presents `unpaid`, `partially_paid`, or `paid` without persisting a second payment state.

## Administration workflow

- A user with `compensation.view` can inspect the settlement payment summary, candidates, payment allocations and append-only events within the active organization context.
- A user with `compensation.manage` can propose a candidate, accept, reject or supersede it, materialize an accepted candidate and reverse an active payment.
- Every command uses a fresh idempotency key and the authoritative candidate or payment revision.
- The UI refreshes the statement read model after each successful command.

## Boundaries

- Existing Sprint 079-081 services, tables and lifecycle routes remain authoritative.
- No schema change is introduced.
- Payment state is dynamic and is not written to the settlement statement or billing document.
- Candidate proposal and review do not execute generic bank reconciliation.
- Materialization and reversal remain explicit service commands with shared bank-evidence capacity control.
- Billing-document and settlement-statement financial values are not mutated by the administration read layer.

## Administration usability repair

- The statement list exposes payment state, paid amount, and remaining amount without opening the detail.
- Technical output types and audit event codes are presented as Czech business labels.
- Long internal identifiers are compacted in the list while command payloads retain exact public identifiers.
- The repair reuses existing proposal, review, materialization, and reversal lifecycle commands.
- No schema, settlement statement, billing document, or generic reconciliation execution mutation is introduced.
