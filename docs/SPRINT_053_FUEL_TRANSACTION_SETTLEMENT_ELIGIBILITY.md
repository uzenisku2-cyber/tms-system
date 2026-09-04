# Sprint 053 â€” Fuel Transaction Settlement Eligibility

Settlement eligibility is a separate, revisioned projection over immutable imported fuel transactions, reconciliation state and historically effective fuel-card settlement policy.

An evaluation is eligible only when reconciliation is resolved, exactly one policy applies on the provider wall-clock date, the configured carrier or driver target exists, and the selected net or gross amount is available. Missing or ambiguous inputs fail closed with an explicit result code.

Every evaluation is append-only and snapshots the reconciliation revision, policy, target, amount basis, discount beneficiary, VAT mode, amount and currency. The mutable aggregate stores only the current projection and optimistic revision.

This foundation does not write to `financial_calculations` and does not mean payment or accounting application. A future application ledger must enforce a unique `fuel_transaction_id` to prevent double settlement.

Reads require `compensation.view`; evaluation requires `compensation.manage`. Organization ownership and driver supervisory visibility remain independent controls.