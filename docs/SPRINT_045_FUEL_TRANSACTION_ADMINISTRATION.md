# Sprint 045 - Fuel Transaction Administration

## Goal

Provide a business-friendly overview and administration surface for imported fuel transactions.

## Business outcomes

- Users can browse imported fuel transactions independently of import batches.
- Users can filter transactions by period, provider, fuel card, and driver.
- Card holder and effective fueling driver are visibly distinguished.
- Authorized supervisors can open the existing audited driver-attribution workflow.
- Attribution history remains available without exposing technical JSON data.

## Display requirements

- The primary view uses business labels and readable values.
- Date, time, quantity, monetary total, currency, station, product, and masked card are visible.
- Technical payloads and raw normalized JSON are not part of the default transaction overview.
- User-facing text is Czech and UTF-8 clean.
- Empty, loading, validation, and authorization states are understandable.

## Security and data integrity

- Organization and supervisory authorization boundaries are preserved.
- Imported financial and provider values remain immutable.
- Driver corrections reuse the append-only attribution history from Sprint 044.
- No cross-organization transaction or driver data is exposed.
- Concurrent attribution changes continue to use revision protection.

## Verification

- Targeted UI and API contract tests cover filters, authorization, and presentation.
- Existing fuel import and driver-attribution lifecycle tests remain passing.
- Pint, PHPStan, PHP syntax, and relevant automated tests pass before commit.
- Tests use disposable infrastructure and do not touch persistent PostgreSQL data.

## Out of scope

- Rewriting imported provider or financial values.
- Deleting or rewriting attribution history.
- Fuel-consumption analytics or automated anomaly scoring.
- Changes to pricing or surcharge calculations.

## Time semantics

- `occurred_at` is the provider-reported local wall-clock time. The API returns `Y-m-d H:i:s`, and the transaction overview must display the same clock value without browser timezone conversion.
- `corrected_at` is an audit instant. Audit history continues to convert that timestamp for the viewer's locale.
