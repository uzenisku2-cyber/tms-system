# Sprint 094 - Financial settlement accounting period close readiness

This sprint adds an organization- and currency-scoped close-readiness projection for an accounting period.

Readiness is blocked by prepared accounting posting handoffs without a canonical execution and by posting executions whose immutable entries are not balanced. The read endpoint requires `compensation.view`. The existing close command re-evaluates readiness under locks and rejects a non-ready period while preserving optimistic revision and idempotency behavior.

The readiness projection and close transition do not mutate handoffs, executions, entries, reversals, corrections, settlement statements, payments, reconciliations, billing documents or bank evidence. No schema migration is introduced.
