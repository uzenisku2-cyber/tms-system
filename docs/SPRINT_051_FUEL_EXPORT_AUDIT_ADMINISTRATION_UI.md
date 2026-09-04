# Sprint 051 - Fuel Export Audit Administration UI

## Goal

Expose the existing organization-scoped fuel CSV export audit in the operational fuel transaction workspace.

## Scope

- Add a Czech export-history panel to the fuel transaction administration page.
- Show export timestamp, actor, safe filename, exported row count and normalized filters.
- Use the existing `GET /api/v1/fuel-transactions/export-history` endpoint and `compensation.view` boundary.
- Keep audit-history pagination independent from fuel-transaction pagination.
- Refresh history after a successful CSV export and through an explicit reload action.

## Safety and boundaries

- Render all server values through the existing HTML escaping helper.
- Display card filters only as `card_last_four` with a masked prefix.
- Never display CSV bytes, bearer tokens, raw provider payloads, normalized provider payloads or complete card identifiers.
- Preserve organization context, provider wall-clock transaction display and existing reconciliation behavior.
- Do not change audit persistence, routes, controllers, services or database migrations.

## Quality contract

- The panel has loading, empty, success and error states.
- Actor, timestamp, filename, row count and safe filters have explicit UI coverage.
- Existing fuel administration and export behavior remains covered by regression tests.
- Validation uses disposable SQLite and never the persistent PostgreSQL database.