# Sprint 049 - Fuel Reconciliation Export and Reporting

## Goal

Provide a business-readable export of the currently filtered fuel transaction and reconciliation overview for dispatcher review and accounting handoff.

## Scope

- Export the same organization-scoped dataset used by the operational overview.
- Preserve active date, provider, effective-driver, reconciliation-status, card, and search filters.
- Include business fields only: provider transaction time, station, product, quantity, gross amount, currency, masked card, card holder, effective driver, vehicle, reconciliation status, and status detail.
- Prefer a spreadsheet-friendly CSV export; evaluate XLSX only if the repository already contains an established safe implementation.
- Keep monetary totals separated by currency; the initial Czech operational workflow is CZK-focused.
- Use the effective driver after append-only attribution corrections.
- Exclude raw provider payloads, authentication data, internal audit metadata, and unmasked card numbers.

## Authorization and data boundaries

- The main carrier can export data within its authorized organizational scope.
- External carriers remain restricted to their own organization and subordinate drivers.
- Export authorization must reuse the same policy and organization boundary as the on-screen overview.
- Export requests must not broaden filters or bypass row-level scoping.

## Quality contract

- No migration unless discovery proves one is necessary.
- No mutation of imported financial values or reconciliation history.
- Deterministic column order and Czech business-facing headings.
- Correct UTF-8 output suitable for current spreadsheet applications.
- Feature, authorization, UI, route, and regression tests are required.
- Verification uses disposable SQLite and never the persistent PostgreSQL database.

## Delivery sequence

1. Read-only discovery of existing export, response, authorization, and fuel overview patterns.
2. Backend export contract and filtered dataset reuse.
3. Administration UI export action and business labels.
4. Disposable verification and isolated preview if useful.
5. Controlled commit, pull request, CI, merge, and closure.
## Backend implementation

- `GET /api/v1/fuel-transactions/export` reuses `IndexFuelTransactionRequest` and the existing `compensation.view` route boundary.
- `FuelTransactionAdministrationService::exportRows()` reuses the exact organization-scoped filter pipeline and reads matching rows lazily in batches of 500.
- `FuelTransactionCsvExportService` writes UTF-8 with BOM, semicolon delimiters, Czech headings, Czech decimal commas and provider wall-clock timestamps.
- The export contains only masked card data and business-facing projections. Raw provider payloads and full provider card identifiers are excluded.
- No migration and no Composer dependency were added.

## UI export workflow

- The fuel transaction overview exposes an `Exportovat CSV` action.
- The download reuses the same active date, provider, effective-driver, reconciliation-status, card, and search filters as the operational overview.
- The browser request includes the authenticated bearer token and active organization header.
- Download failures are shown in the existing page message area and do not navigate away from the administration workspace.
- The server-provided CSV filename is preserved when available.