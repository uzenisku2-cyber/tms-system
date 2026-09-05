# Sprint 061 - Vehicle Cost Allocation Application Service

The application layer creates explicit vehicle cost-allocation drafts and approves immutable revisions. It validates organization context, supervisory hierarchy, responsible organizations and drivers, currency, and net/VAT/gross arithmetic.

Settlement modes remain instructions only: `invoice_required`, `deposit_offset`, `repair_fund_reserve`, insurance/state recovery, informational only, and manual review. Creating or approving an allocation must not create an invoice, financial calculation, tax document, repair-fund transaction, payment, bank match, or paid state.

Approval uses an expected revision and an append-only snapshot. Existing revisions, allocation lines, and lifecycle events remain unchanged. Downstream billing, deposit, repair-fund, and bank workflows require separate explicit application steps and their own authorization and evidence.