# Sprint 067 â€“ Bank Transaction Evidence Foundation

This sprint introduces organization-scoped, immutable bank transaction evidence with stable source identity, booking and value dates, direction, amount, currency, counterparty metadata, idempotency and append-only audit events.

The record is external money-movement evidence only. It does not prove a business purpose and is not itself an invoice, payment allocation, reconciliation decision or settlement. Recording evidence does not match a bank transaction, mark a payment, modify a billing document, execute a deposit offset, apply a settlement deduction or move repair-fund money.

Access requires `compensation.manage` in the active organization context. Repeated submission of the same idempotency key returns the original evidence, while duplicate source identity in the same organization is rejected.

Matching execution, payment marking, bank import batches, raw bank rows, duplicate detection, corrections and reversals remain separate controlled workflows.