# Sprint 073 â€” Financial Document and Fuel Rebilling Foundation

## S073-01A Commercial identity foundation

- Extends the canonical `billing_documents` aggregate with `supplier_fuel_invoice`.
- Stores commercial identity separately so existing settlement documents remain compatible.
- Captures document number, variable symbol, issue/tax/due dates and payment direction.
- Preserves an immutable counterparty snapshot and revisioned audit-event foundation.
- Does not allocate fuel transactions, match bank transactions, mark payments, or post accounting entries.
- Purchase cost and future rebilled value remain separate; margin is not treated as an allocation error.

## S073-02A Supplier fuel invoice draft API

- Creates an idempotent payable supplier-fuel-invoice draft with exact-cent VAT validation.
- Records immutable counterparty identity and a created audit event.
- Does not allocate fuel transactions, match bank evidence, or mark payment.
## Supplier fuel invoice transaction allocation

- Supplier fuel invoice drafts can be allocated to one or more fuel transactions in exact integer minor units.
- Partial allocation is explicit; the invoice state is unallocated, partially_allocated, or fully_allocated.
- Active allocation totals may not exceed either the supplier invoice gross amount or the fuel transaction gross amount.
- Allocation commands are organization-scoped, idempotent, locked, versioned, and append-only audited.
- Allocation does not execute bank matching, mark payment, or mutate driver/carrier fuel settlement applications.

- Allocation reversal is append-only, revision-checked, audited, and releases invoice and transaction capacity without deleting history.
