# Sprint 056 - Fuel Settlement Financial Calculation Integration

## Goal

Attach an applied fuel-transaction settlement to one existing financial calculation without rewriting either aggregate's historical inputs or calculated totals.

## Contract

- The operation requires `compensation.manage` and the active organization context.
- Only an `applied` settlement application and a `calculated` financial calculation can be linked.
- Both aggregates must belong to the same organization and use the same currency.
- A driver settlement requires `input_snapshot.performed_by_driver_id` to match the snapshotted target driver.
- An organization settlement requires `input_snapshot.organization_id` to match the snapshotted target organization.
- `expected_revision` protects the mutable application projection.
- Repeating the same link is idempotent; linking a different calculation is rejected.
- The link advances the application revision and appends a `financial_calculation_attached` event.
- Reversal preserves the financial-calculation reference in history and does not mutate or delete the financial calculation.
- Financial calculation lines, `subtotal_amount`, `total_amount`, price-list snapshots, eligibility revisions and reconciliation revisions remain immutable.
- The unique fuel-transaction application guard continues to prevent double settlement.

## Deferred

Creating fuel adjustment lines, changing financial totals, accounting export, payment and correction settlements remain separate controlled work.
