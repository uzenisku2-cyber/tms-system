# Sprint 083 - Financial settlement bank payment reconciliation

## Scope

This increment introduces an explicit reconciliation projection for an existing financial settlement bank payment. An authorized operator confirms that the immutable payment allocation and its bank evidence have been reviewed, or reopens that decision for another controlled review.

## Contract

- One reconciliation projection exists per settlement bank payment.
- Confirmation requires an active, organization-scoped payment and exact payment and reconciliation revisions.
- Reopen requires the current confirmed reconciliation revision.
- Commands are idempotent and reject reuse of a key with a different fingerprint.
- Every confirmation and reopen appends an immutable event with the payment revision snapshot.
- A reopened reconciliation may be confirmed again at the next revision.

## Financial boundary

Reconciliation records an operational decision only. It does not allocate or reverse money, mutate the settlement statement, billing document or bank evidence, mark an accounting entry, execute a generic vehicle-cost bank match, or export accounting data. Those remain separate explicit workflows.
