# Sprint 067 â€“ Bank Transaction Matching Execution

This increment introduces an explicit, organization-scoped and append-only execution that connects one prepared vehicle-cost bank-matching handoff with one recorded bank transaction evidence record and its billing-document target.

Execution requires `compensation.manage`, organization and responsible-party visibility, optimistic revisions for both source records, a stable idempotency key, matching bank reference, compatible direction and identical currency. The matched amount may be partial, but active executed matches must not exceed the recorded bank transaction, the prepared handoff amount or the billing document gross amount. Currency conversion remains a separate controlled workflow.

The execution and its audit event are immutable. Repeating the same idempotency key returns the original execution. A handoff can be executed only once, while one bank transaction may support multiple controlled allocations when its remaining amount permits them.

Bank matching proves only the approved connection represented by this execution. It does not mark a payment, close or modify a billing document, rewrite bank evidence, execute a deposit offset, apply a settlement deduction, create a financial calculation or move repair-fund money. Those effects require separate explicit workflows and audit records.
