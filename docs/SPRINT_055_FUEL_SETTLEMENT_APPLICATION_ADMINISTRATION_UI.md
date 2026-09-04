# Sprint 055 - Fuel Settlement Application Administration UI

## Goal

Expose settlement eligibility and the immutable settlement application lifecycle in the existing fuel transaction administration workspace.

## Contract

- Users with `compensation.view` may inspect eligibility, the applied snapshot and append-only application events.
- Users with `compensation.manage` may evaluate eligibility, apply an eligible transaction and reverse an applied settlement with a mandatory reason.
- Every write sends the current `expected_revision` or `expected_eligibility_revision`; stale responses refresh the detail.
- The UI displays the snapshotted settlement target, amount and currency without exposing sensitive provider payloads.
- Applied, reversed and not-applied states remain distinct. Reversal never deletes the original application or its events.
- This sprint does not modify settlement services, routes, migrations, financial calculations or persistent PostgreSQL data.