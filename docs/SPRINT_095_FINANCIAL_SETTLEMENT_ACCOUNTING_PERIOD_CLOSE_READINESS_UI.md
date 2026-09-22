# Sprint 095 - Financial settlement accounting period close readiness UI

The accounting period administration workspace now retrieves a fresh close-readiness projection before offering the close command.

The panel displays the handoff and posting-execution summary together with detailed pending-handoff and unbalanced-posting blockers. Close confirmation is available only when the projection is ready, and the command keeps the projected revision as its optimistic `expected_revision` with a new idempotency key.

The server-side close guard remains authoritative. The UI does not mutate posting handoffs, posting executions, entries, reversals, corrections, statements, payments, reconciliations, billing documents or bank evidence. No schema migration is introduced.
