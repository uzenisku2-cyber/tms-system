# Sprint 075 - Financial billing and mutual charge foundation

## S075-01A Financial mutual charge foundation

The financial mutual charge is the canonical pre-document item shared by the billing, driver settlement and external carrier settlement domains.

- A charge belongs to one managing organization and names exactly one counterparty: an organization or a driver.
- Direction is explicit from the managing organization's perspective: receivable or payable.
- Monetary values are positive integer minor units. Direction is never encoded by a negative amount.
- Supported categories are fuel, vehicle cost, damage, advance, rental, penalty, bonus and other.
- Source type, public identifier and immutable snapshot retain traceability without coupling the charge to one source module.
- A draft remains private. Confirmation may make it shared with the named counterparty; disputed and reversed are explicit lifecycle states.
- Offset eligibility is recorded independently from visibility and lifecycle state.
- Idempotency and source-direction uniqueness prevent accidental duplicate recognition.
- Every lifecycle transition must append an audit event with actor, reason, evidence and revision.

This foundation does not create a settlement statement, billing document, invoice, payment, paid state or bank match. Those actions require explicit later workflows.

Supplier fuel purchase cost and a later fuel charge remain separate values. Their difference is an allowed commercial margin and is not an allocation mismatch.
## S075-02A Financial mutual charge lifecycle API

- Draft creation is exact-cent, source-traceable and idempotent.
- Confirmation changes a private draft into a shared bilateral item.
- A shared organization counterparty may dispute the item in its own organization context.
- Only the managing organization may confirm or reverse the item.
- Every command uses optimistic revision control and an append-only idempotent audit event.
- Driver counterpart identity is retained, while driver self-service authorization is deferred to the driver financial account boundary.
- No settlement statement, billing document, payment state or bank match is created by this lifecycle.
## S075-03A Financial settlement statement foundation

- A statement targets exactly one recipient: an organization or a driver.
- Immutable lines reference either a financial calculation or a confirmed mutual charge by public identity and source revision.
- Earnings and deductions are stored as positive minor-unit values; net balance is signed and equals earnings minus deductions.
- A positive net balance is payable to the recipient. A negative balance is receivable from the recipient.
- Statement-local source uniqueness prevents the same calculation or mutual charge from being counted twice.
- Source snapshots preserve the evidence used by the statement even when the source later changes lifecycle state.
- Statement lines and events are append-only.
- This foundation does not calculate a statement or create a billing document, payment, paid state or bank match.
## S075-04A Financial settlement statement draft API

- A draft explicitly selects approved calculations and confirmed offset-eligible mutual charges.
- Every source must match the recipient, statement currency and applicable period.
- Organization calculations belong to the provider organization; driver calculations must snapshot the selected performed-by driver.
- Calculations are earnings. A mutual charge payable by the owner is an earning for the recipient; a receivable is a deduction.
- Totals use exact minor units and net balance may be positive or negative.
- Draft creation is idempotent and snapshots every selected source revision.
- No approval, billing document, payment state or bank match is created.
## S075-05A Financial settlement statement lifecycle

- Draft statements move through review, approval and closure under optimistic revision control.
- Closed statements can be cancelled without deleting their immutable source rows or audit history.
- Every transition is idempotent and organization scoped; billing, payment and bank matching remain separate.
## S075-06B Settlement output materialization foundation

- A closed statement has at most one materialized billing document and records its output kind, direction and timestamp.
- Carrier payable/receivable and driver payout/deduction outputs remain distinct; zero balance creates no billing document.
- This foundation installs persistence contracts only and performs no billing, payment or bank matching side effect.
## S075-07A Settlement output materialization API

- A closed statement materializes exactly once into a carrier settlement, driver remuneration, or zero-balance output.
- Carrier documents require explicit commercial identity, dates and VAT values whose gross total equals the absolute statement balance.
- Driver outputs are internal non-VAT documents; payment marking and bank matching remain separate downstream processes.
