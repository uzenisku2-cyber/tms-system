# Sprint 076 â€” Fuel Invoice Payment and Rebilling Control

## S076-01A supplier fuel invoice bank payment foundation

- A payment allocation links immutable bank transaction evidence to a supplier fuel invoice billing document.
- Amounts are stored in exact minor currency units and support partial and multiple payments.
- Active and reversed allocations remain distinct from generic bank matching execution.
- Idempotency, evidence revision and optimistic revision fields prepare a guarded lifecycle API.
- Audit events are append-only.
- This foundation does not automatically match bank transactions, mark an invoice paid, mutate fuel settlement, or create billing documents.

## Planned rebilling control

- Purchase cost follows supplier invoice allocation to fuel transactions.
- Rebilling follows fuel settlement applications into driver or carrier settlement statements and their materialized outputs.
- Positive or zero margin is acceptable.
- Missing rebilling and negative margin are review conditions, not automatic financial mutations.
## S076-02A payment lifecycle API

- Manually allocates recorded debit bank evidence to a payable supplier fuel invoice.
- Enforces organization, currency, evidence revision, invoice capacity and bank transaction capacity.
- Derives unpaid, partially paid and paid state from active exact-cent allocations.
- Reversal releases both capacities and appends an immutable audit event.
- The lifecycle is idempotent and does not mutate fuel settlement or create financial documents.
## S076-03A Fuel rebilling coverage foundation

- Coverage follows every active supplier-invoice allocation through the fuel transaction, settlement application, financial calculation, driver/carrier statement, and materialized output document.
- Purchase and rebilled values are stored in exact minor units and may only be compared on one explicit `net` or `gross` basis.
- Complete coverage with zero or positive margin is accepted. Missing or partial rebilling and negative margin remain visible review states.
- Evaluation is an immutable snapshot with append-only audit; it creates no bank match, payment, settlement, calculation, statement, or billing document.

## S076-04A Fuel rebilling coverage evaluation API

- An organization-scoped, idempotent command evaluates all active allocations of one supplier fuel invoice.
- Rebilling is recognized only when an active settlement application on the requested `net` or `gross` basis reaches a closed, materialized settlement statement through its financial calculation.
- Purchase and rebilled values are proportioned to each invoice allocation in exact minor units; incomplete chains remain visible as missing coverage and negative margins remain review findings.
- Evaluation writes only its coverage snapshot, lines, and append-only event. It does not create or mutate bank evidence, payments, settlement applications, calculations, statements, or billing documents.

## S076-04B Coverage API lifecycle verification

- Runtime verification covers driver and carrier outputs, positive and negative margin, missing materialization, idempotent replay, and organization isolation.
- A calculation link is insufficient by itself: the closed materialized statement must address the same driver or carrier, use a compatible output kind, and own a materialized billing document.
- Coverage evaluation is verified not to create bank evidence, invoice payments, settlement applications, settlement statements, or billing documents.
## S076-05A Payment and rebilling administration presentation

- Supplier fuel invoice reads expose exact-cent payment totals and unpaid, partially paid, or paid state.
- The administration UI shows manual payment evidence and the latest driver/carrier rebilling coverage, including margin.
- Authorized users can request a coverage re-evaluation from the invoice detail.
- The UI does not initiate payment allocation or automatic bank matching.
