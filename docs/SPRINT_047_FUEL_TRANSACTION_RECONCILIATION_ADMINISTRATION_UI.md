# Sprint 047 - Fuel Transaction Reconciliation Administration UI

## Goal

Expose the Sprint 046 reconciliation lifecycle inside the existing fuel transaction administration workspace without duplicating reconciliation business logic.

## Overview contract

- The transaction index includes one eager-loaded reconciliation summary and never performs one detail request per table row.
- Missing reconciliation projection is presented as `pending`.
- Users can filter `pending`, `matched`, `review_required`, and `resolved` states.
- Provider wall-clock transaction time remains unchanged.

## Detail and decisions

- The reconciliation modal shows current status, result, candidate reports, immutable evaluations, immutable manual decisions, and revision.
- `compensation.view` users receive a read-only surface.
- `compensation.manage` users may evaluate, confirm a driver-day, select an eligible report, accept without operational activity, or return a resolution to review.
- Every write sends `expected_revision`; a stale response refreshes the detail instead of overwriting concurrent work.

## Integrity

- UI actions call the existing Sprint 046 endpoints.
- Import `match_status`, provider values, financial values, timestamps, and driver-attribution history are not rewritten.
- Driver organization assignment and authorization visibility remain separate contracts.
- No database migration is required.

## Verification

- Contract, lifecycle, and MVP UI tests cover the list projection, state filter, permissions, actions, and revision handling.
- Pint, PHPStan, full SQLite migration, and the targeted fuel suite pass before commit.
- Tests use disposable infrastructure and never persistent PostgreSQL.