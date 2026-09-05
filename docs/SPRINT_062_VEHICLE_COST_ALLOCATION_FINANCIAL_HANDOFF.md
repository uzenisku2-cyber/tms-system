# Sprint 062 â€” Vehicle Cost Allocation Financial Handoff

An approved vehicle cost allocation can be prepared as an immutable, revision-bound and idempotent financial handoff.

Each source line preserves its responsible party, net amount, VAT amount, gross amount, currency and VAT treatment. Settlement modes are translated explicitly: `invoice_required` to a billing-document instruction, `deposit_offset` to a settlement deduction, `repair_fund_reserve` to a repair-fund instruction, recovery modes to receivable tracking, and review modes to non-executing instructions.

Preparation must not create an invoice, billing document or financial calculation; it must not mark a bank payment or deposit as paid; and it must not move money into or out of a repair fund. Those operations require later explicit application workflows and their own authorization, tax validation, evidence and idempotency controls.

The main carrier hierarchy remains enforced through `compensation.manage`, manageable organization scope and the approved allocation organization context. Every handoff, instruction and event is append-only and auditable.