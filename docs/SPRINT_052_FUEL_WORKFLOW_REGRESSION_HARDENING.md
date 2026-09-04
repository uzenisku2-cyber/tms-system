# Sprint 052 - Fuel Workflow Regression Hardening

## Goal

Protect the end-to-end fuel workflow when a dispatcher changes the actual driver after reconciliation has already been evaluated or manually resolved.

## Invalidation contract

A driver-attribution correction changes the effective operational identity. In the same database transaction, an existing reconciliation projection is locked, reset to `pending`, stripped of derived driver, assignment, candidate, report and result values, and advanced by one revision. Stale clients therefore cannot reuse the previous reconciliation revision.

The reconciliation projection is mutable current state. Historical reconciliation evaluations, manual decisions and driver-attribution events remain append-only. Imported provider identity, timestamp, card reference, quantity and financial values remain unchanged.

## Regression workflow

The cross-module lifecycle test executes the real ORLEN import, automatic reconciliation, manual report selection, actual-driver correction, projection invalidation, stale-revision rejection, reevaluation for the new driver, filtered CSV export and successful export audit recording.

## Database safety

This installation and its first validation use disposable SQLite test databases only. PostgreSQL compatibility is verified separately with a disposable PostgreSQL 16 container. The persistent PostgreSQL service is never used or modified.