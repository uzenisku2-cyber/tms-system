# Sprint 064 â€” Vehicle Cost Allocation Deposit Offset Handoff

An authorized operator may explicitly acknowledge one paid advance for a prepared `deposit_offset` instruction. The acknowledgement is revision guarded, idempotent and append-only. It snapshots the responsible organization or driver, net, VAT, gross, currency, payment method, reference and evidence note.

This workflow does not create an invoice or other billing document. The VAT amount remains evidence attached to the acknowledged advance; `vat_disposition=repair_fund_pending` records an agreed future intention only. It does not move money into a repair fund and does not make a tax or accounting conclusion.

Acknowledgement does not match a bank transaction, mark a payment, apply a settlement deduction, create a financial calculation, or alter existing monetary amounts. Those actions require separate explicit, authorized and idempotent workflows. Cash, bank-transfer, card and other evidence may be recorded, but recording evidence is not proof of bank reconciliation.