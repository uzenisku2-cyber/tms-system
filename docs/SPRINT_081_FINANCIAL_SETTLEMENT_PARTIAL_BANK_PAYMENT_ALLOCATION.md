# Sprint 081 — Financial Settlement Partial Bank Payment Allocation

## Scope

Sprint 081 extends settlement bank-payment allocation from exact-only materialization to partial and repeated payments. The existing candidate, payment and append-only event tables remain authoritative; no schema or route change is required.

## Dynamic settlement balance

- Total amount is the absolute settlement statement net balance.
- Paid amount is the sum of active settlement bank payments.
- Unpaid amount is `max(0, total - paid)`.
- Presentation state is `unpaid`, `partially_paid`, or `paid`.
- Reversed payments are excluded from the active sum and therefore restore the unpaid balance.

## Proposal and materialization

- A proposal uses the lesser of current unpaid settlement balance and remaining shared bank-evidence capacity.
- Exact and partial amount relationships are recorded explicitly in match reasons.
- Each accepted candidate may still be materialized only once.
- Materialization rechecks the current unpaid balance and rejects a stale candidate that would over-allocate it.
- Repeated candidates may allocate distinct payments until the dynamic unpaid balance reaches zero.

## Boundaries

- Shared bank-evidence capacity includes active allocations from every supported financial domain.
- Reversal releases shared capacity through payment status, preserving append-only events.
- Settlement statements and billing documents are not mutated or marked as paid.
- No generic bank-matching execution or persistent-database validation is introduced by this sprint.
