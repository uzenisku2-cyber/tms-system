# Sprint 054 - Fuel Transaction Settlement Application

## Goal

Record the controlled application of one eligible fuel transaction to settlement while preventing double settlement.

## Contract

- A database unique `fuel_transaction_id` permits only one settlement application, including after reversal.
- Application requires the current `eligible` projection and snapshots its eligibility revision, resolved reconciliation revision, historical card policy, target, amount basis, VAT mode, amount and currency.
- The resolved reconciliation must still exist at the snapshotted revision; stale eligibility fails closed.
- The application aggregate stores current status and optimistic revision. Applied and reversed lifecycle events are append-only.
- Reversal requires an expected revision and mandatory reason. It never deletes the application or its original evidence.
- `financial_calculation_id` is nullable and reserved for a later controlled link. This foundation never creates or mutates `financial_calculations`.
- Imported fuel transaction values, reconciliation history and eligibility evaluations remain unchanged.

## Authorization

Reads use the organization-scoped `compensation.view` boundary. Apply and reverse use `compensation.manage`. Driver supervisory visibility is checked independently for driver-targeted applications.

## Verification

Contract, migration and lifecycle tests cover snapshot integrity, stale revision rejection, duplicate prevention, append-only reversal, organization scope and separation from financial calculations. SQLite and disposable PostgreSQL are used for validation; persistent PostgreSQL is never used.