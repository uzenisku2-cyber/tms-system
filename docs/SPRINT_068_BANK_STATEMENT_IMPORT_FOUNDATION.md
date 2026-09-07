# Sprint 068 â€“ Bank Statement Import Foundation

This sprint introduces organization-scoped configurable CSV bank statement imports. Each batch preserves the original file identity, parser and mapping versions, encoding, delimiter and immutable raw rows. Normalized values create existing `BankTransactionEvidence` records only after validation.

Deterministic row and transaction fingerprints support exact duplicate detection. Core-field comparisons preserve probable duplicates as unresolved candidates instead of silently accepting or discarding them. Repeated idempotency keys return the original batch, while repeated file hashes are rejected within the organization context.

The initial parser is bank-neutral. ÄŚSOB/ABO, CAMT.053 and other bank-specific adapters remain separate future increments. Importing evidence does not execute bank matching, mark a payment, modify a billing document, execute a deposit offset, apply a settlement deduction or move repair-fund money.

Access requires `compensation.manage`. Validation uses isolated SQLite and never the persistent PostgreSQL database.
