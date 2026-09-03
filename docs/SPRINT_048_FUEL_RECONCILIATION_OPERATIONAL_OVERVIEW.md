# Sprint 048 - Fuel Reconciliation Operational Overview

## Objective

Provide dispatchers and authorized carrier managers with a clear operational overview of fuel transaction reconciliation. The overview must make unresolved and review-required transactions immediately visible without exposing raw import payloads.

## Business scope

- Summary cards for total, pending, matched, review required, and resolved transactions.
- Filters by operating date range, provider, effective driver, reconciliation status, and business search text.
- Readable breakdowns by reconciliation status, provider, and effective driver.
- Direct drill-down to the existing fuel transaction administration and reconciliation decision workflow.
- Organization-scoped results respecting the existing carrier hierarchy and authorization boundaries.
- Masked fuel-card identifiers and Czech business labels in the user interface.
- Financial amounts remain imported facts and are not editable from the overview.

## Guardrails

- Reuse existing fuel transaction and reconciliation records.
- Do not expose raw JSON or technical import payloads in the business overview.
- Do not change attribution, reconciliation, or financial history from summary widgets.
- Preserve append-only reconciliation decisions and revision conflict protection.
- Do not add a migration unless discovery proves that the current schema cannot support the overview.
- Use disposable SQLite for verification and isolated preview data.
- Never use the persistent PostgreSQL database for tests or preview seeding.

## Delivery sequence

1. Discover the existing administration, reconciliation, authorization, and UI contracts.
2. Define aggregate projections and filtering contracts.
3. Implement the organization-scoped backend overview.
4. Implement and review the Czech operational UI.
5. Verify with disposable SQLite, targeted tests, Pint, PHPStan, and PHP syntax checks.
6. Perform controlled commit, publish, pull request, CI, merge, and closure.

## Definition of done

- Dispatchers can identify pending and review-required fuel transactions at a glance.
- Filters and aggregate counts use the same organization-scoped dataset.
- Effective driver and reconciliation status are represented consistently.
- Drill-down preserves the existing administration and manual decision workflow.
- Raw provider payloads are absent from the business UI and API projection.
- Targeted regression tests pass without using persistent PostgreSQL.
- Documentation, source paths, and repository state are verified before commit.


## Backend implementation contract

- The overview endpoint reuses the administration request filters and organization scope.
- Transactions without a reconciliation record are counted as pending.
- Attention required is the sum of pending and review-required transactions.
- Provider and effective-driver breakdowns use the same filtered transaction dataset.
- Financial totals are grouped by currency and never combine different currencies.
- The endpoint is read-only and does not expose provider card identifiers or import payloads.
- Existing reconciliation evaluation and decision workflows remain unchanged.

## User interface contract

- The operational overview is displayed above the transaction table and uses the same active filters.
- Attention required, pending, review-required, matched, resolved and total counts remain visibly distinct.
- Provider and effective-driver breakdowns show transaction counts without exposing raw provider payloads or full card identifiers.
- Financial totals remain separated by currency in the data contract; the current Czech UI uses the neutral `Financni souhrn` heading and CZK operational data.
- The existing transaction table, driver attribution editor and reconciliation decision modal remain available.
