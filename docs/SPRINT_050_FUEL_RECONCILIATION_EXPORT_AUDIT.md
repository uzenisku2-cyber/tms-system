# Sprint 050 - Fuel Reconciliation Export Audit

## Goal

Provide an append-only, organization-scoped audit trail showing who exported fuel reconciliation data, when it was exported, and which filters defined the exported dataset.

## Scope

- Record every successful reconciliation CSV export as an immutable audit event.
- Capture actor, organization, export timestamp, normalized active filters, exported row count, and a non-sensitive report identity.
- Provide an organization-scoped administration overview of export history.
- Preserve the existing CSV content, active-filter behavior, provider wall-clock semantics, and effective-driver attribution.
- Do not store generated CSV payloads, bearer tokens, raw provider payloads, unmasked card numbers, or secrets in the audit trail.
- Keep the Czech operational workflow and CZK-focused presentation.

## Authorization and data boundaries

- The main carrier can inspect export events within its authorized organizational hierarchy.
- External carriers remain restricted to export events for their own organization and subordinate operational scope.
- Export execution and audit-history access must use explicit capabilities and existing organization boundaries.
- Audit reads must never reveal another unauthorized organization's filters, actors, or report metadata.

## Quality contract

- Discovery must determine the canonical audit/event persistence pattern before any migration is added.
- Audit records are append-only and must not mutate imported transactions, financial values, driver attribution, or reconciliation history.
- Failed exports must not be recorded as successful; failure recording, if implemented, must exclude sensitive exception data.
- Normalized filters must be deterministic and safe for business display.
- Feature, authorization, lifecycle, UI, route, migration, and regression tests are required as applicable.
- Verification uses disposable SQLite and never the persistent PostgreSQL database.

## Delivery sequence

1. Read-only discovery of audit-event, activity-log, authorization, and export patterns.
2. Append-only export audit persistence and lifecycle contract.
3. Organization-scoped audit API and administration UI.
4. Disposable migration verification and isolated preview if useful.
5. Controlled commit, pull request, CI, merge, and closure.

## Discovery and backend decision

- Existing append-only event models and organization-scoped event indexes are reused as the architectural pattern.
- Successful CSV generation creates one `fuel_transaction_export_events` record after all rows are written.
- The event stores actor, organization, safe filename, deterministic normalized filters, row count, format and timestamps.
- Card filtering is persisted only as `card_last_four`; CSV bytes, bearer tokens, raw provider payloads and full card identifiers are never stored.
- Export history is read through the active organization context and `compensation.view` authorization boundary.