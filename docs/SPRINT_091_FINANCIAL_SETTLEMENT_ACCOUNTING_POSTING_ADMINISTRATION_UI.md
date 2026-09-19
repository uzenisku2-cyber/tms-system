# Sprint 091 â€“ Financial settlement accounting posting administration UI

This sprint adds an organization-scoped, read-only administration API and settlement workspace panel for the complete accounting posting lifecycle.

The list supports status, direction, currency, accounting-reference and posting-date filters. The detail presents balanced execution entries and an audit timeline linking the handoff, execution, reversal, correction and replacement execution.

Access requires `compensation.view` and the active organization context. Cross-organization records are never returned.

The feature does not calculate, post, reverse or correct accounting data. It does not mutate settlement statements, reconciliations, payments, billing documents, bank evidence, handoffs, executions, reversals or corrections. No schema migration is introduced.
