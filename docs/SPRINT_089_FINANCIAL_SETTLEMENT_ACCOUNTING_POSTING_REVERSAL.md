# Sprint 089 â€“ Financial settlement accounting posting reversal

This sprint adds an organization-scoped, idempotent and append-only reversal of an executed financial-settlement accounting posting.

The reversal references exactly one original posting execution and emits exactly two compensating entries. Every debit becomes a credit and every credit becomes a debit while account, amount and currency are preserved. The reversal and its audit event are immutable.

The command requires the expected execution revision, a unique idempotency key and a mandatory reason. Replaying the same command returns the existing reversal; reusing the key for different command data conflicts.

The original posting execution, handoff, settlement payment, reconciliation, statement, billing document and bank evidence remain unchanged. Corrections, replacement postings, exports and tax processing remain separate responsibilities.
