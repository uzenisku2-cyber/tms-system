# Sprint 066 â€” Vehicle Cost Allocation Bank Matching Handoff

The bank-matching handoff records externally supplied bank transaction evidence for an approved billing-document instruction. It is explicit, hierarchy-authorized, idempotent, optimistic-revision guarded and append-only.

The handoff supports a responsible organization or driver and preserves the instruction, billing document, amount, currency, booking date, transaction reference and evidence note.

This foundation does not create or import a bank transaction, does not perform bank matching, does not mark a payment, does not create an invoice, and does not execute a deposit offset, settlement deduction, repair-fund movement or other financial automation. Those remain separate controlled workflows.