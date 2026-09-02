# Sprint 046 - Fuel Transaction Reconciliation

## Goal

Create a controlled reconciliation layer between imported fuel transactions and daily operational activity without rewriting either source.

## Authorization

- Reading reconciliation state requires `compensation.view` and is restricted to drivers visible through the supervisory hierarchy.
- Running automatic evaluation or recording a manual decision requires `compensation.manage` and the same hierarchy visibility.
- Driver organization assignment determines historical operational ownership; authorization scope independently determines which organizations and drivers the actor may access.
- The master carrier may access its own and subordinate carriers' visible drivers. An external carrier remains restricted to its own visible subordinate drivers.

## Separation

- Import `match_status` remains the historical card-assignment result.
- Driver attribution remains an independent append-only correction history.
- Automatic reconciliation evaluations are immutable suggestions.
- Manual reconciliation decisions are separate immutable approvals.
- The reconciliation aggregate stores only the current projection and revision.

## Deterministic evaluation v1

The effective driver is `actual_driver_id` when present, otherwise the imported `driver_id`. The driver organization assignment must be historically valid and visible through the existing supervisory hierarchy with `compensation.manage`. Operational candidates are non-draft daily reports for the effective driver, assignment organization and provider wall-clock date.

Multiple routes on one day produce a driver-day match and never force a route. A unique vehicle match may select one daily report. Missing activity, missing identity, import review, vehicle mismatch and unconfirmed vehicle data require review.

## Manual decisions

Authorized users may confirm a driver-day match, select an eligible daily report, accept a transaction without operational activity with a reason, or return a resolved case to review. Every decision uses optimistic revision control and records actor, time, reason and previous/new state.

## Integrity

Imported provider values, money, quantity, timestamp, import matching and driver-attribution history are immutable. Automatic evaluation cannot overwrite a manual resolution. All queries are organization scoped. Tests use disposable SQLite infrastructure and never persistent PostgreSQL.
