# Sprint 085 - Financial Settlement Reconciliation Export Reporting

## Goal

Provide a business-readable CSV export of settlement bank payment reconciliations for controlled accounting handoff.

## Scope

- Export reconciliations belonging to settlement statements visible in the active organization.
- Reuse the statement administration filters: status, recipient type, output direction and service period.
- Include Czech business columns, payment and bank reference, allocated amount, reconciliation state and timestamps.
- Produce UTF-8 CSV with BOM and semicolon delimiters for spreadsheet use.
- Preserve the existing `compensation.view` authorization and organization middleware boundary.

## Explicit boundaries

- The export is read-only and performs no accounting posting.
- It does not mutate settlement statements, payments, reconciliation projections or billing documents.
- It does not create export-audit persistence; that remains a separate explicit lifecycle.
- It adds no database migration and no new Composer dependency.
- Verification must use disposable SQLite and must not use persistent PostgreSQL.