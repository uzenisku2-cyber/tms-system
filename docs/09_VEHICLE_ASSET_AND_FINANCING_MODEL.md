# TMS Vehicle Asset and Financing Model v1.1

## 1. Purpose

This document defines the vehicle asset, ownership, operation, custody, financing, cost-allocation, insurance, valuation, and lifecycle architecture of the TMS platform.

The model supports:

- privately owned vehicles
- organization-owned vehicles
- vehicles owned by external parties
- purchased vehicles
- financed vehicles
- finance leases
- operating leases
- rentals
- temporary vehicle use
- shared vehicles
- employee and external-carrier drivers
- changing owners and operators
- financing schedules
- invoices and payments
- bank reconciliation
- internal cost allocation
- vehicle reserve funds
- insurance policies and claims
- acquisition and disposal
- complete historical audit

The model must remain consistent with:

- TMS Business Principles
- TMS Core Organization Model
- TMS User Roles and Permissions
- TMS Driver and Vehicle Model
- TMS Daily Report Process
- TMS Fuel Management
- TMS Pricing and Compensation Model
- TMS Availability and Resource Planning
- TMS Notification and Rule Engine
- audit and data integrity rules


## 2. Core Separation Principle

The following concepts must remain separate:

- vehicle identity
- legal ownership
- registered operator
- operational responsibility
- custody
- authorized usage
- default assignment
- planned assignment
- actual usage
- acquisition
- disposal
- financing agreement
- scheduled financial obligation
- invoice
- payment
- bank transaction
- reconciliation
- cost responsibility
- internal settlement
- reserve fund
- insurance policy
- insurance claim
- valuation
- depreciation

A vehicle remains the same vehicle when its owner, operator, driver, financing agreement, or operational assignment changes.

Ownership does not prove operational responsibility.

Operational responsibility does not prove ownership.

Default assignment does not prove actual usage.

A financing agreement does not prove that a payment occurred.

An invoice does not prove that it was paid.

A bank transaction does not automatically prove which obligation it settles.


## 3. Vehicle Identity and Lifecycle

Vehicle identity must remain stable throughout the vehicle lifecycle.

A vehicle record may contain:

- internal vehicle identifier
- registration number
- VIN
- manufacturer
- model
- production year
- first-registration date
- vehicle category
- fuel or energy type
- technical parameters
- odometer unit
- lifecycle status
- creation date and time
- archival date and time when applicable

Vehicle lifecycle states may include:

- DRAFT
- ACTIVE
- TEMPORARILY_INACTIVE
- RESTRICTED
- DISPOSED
- WRITTEN_OFF
- ARCHIVED

A change of registration number must not create a new vehicle identity.

A change of owner must not create a new vehicle identity.

A vehicle must not be deleted merely because it has been sold, returned, transferred, or written off.


## 4. Ownership and Legal Title

Legal ownership must be represented by time-bounded ownership records.

An ownership record must identify:

- vehicle
- owner party
- owner party type
- ownership share when applicable
- valid-from date and time
- valid-to date and time when applicable
- acquisition basis
- supporting document
- verification status
- recording user
- organization context
- change reason

An owner party may be:

- individual
- organization
- external legal entity
- financing provider
- other legally recognized party

Ownership records must support:

- sole ownership
- joint ownership
- ownership transfer
- temporary uncertainty
- disputed ownership
- verified historical ownership

No financing product, lease, rental, custody, or usage relationship is itself proof of ownership.

A financing provider may be the legal owner while another organization operates the vehicle.

A future ownership transfer must not be recorded as current ownership before its effective date.


## 5. Operator, Custody, Assignment and Actual Usage

The following relationships must remain separate:

- registered operator
- operationally responsible organization
- custodian
- authorized user
- default driver assignment
- planned driver assignment
- planned vehicle assignment
- actual driver
- actual vehicle usage

A registered operator is the party recorded as the vehicle operator where applicable.

An operationally responsible organization manages the vehicle for TMS operational purposes.

A custodian has physical or administrative custody of the vehicle.

An authorized user may be permitted to use the vehicle.

A default assignment provides a planning preference.

A planned assignment identifies intended usage.

Actual usage records what occurred.

Each relationship must support:

- valid-from date and time
- valid-to date and time
- responsible organization
- source
- creating user
- status
- reason
- audit history

A driver may use several vehicles over time.

A vehicle may be used by several drivers over time.

Actual route usage must not be inferred solely from a default or planned assignment.


## 6. Acquisition, Transfer and Disposal

Vehicle acquisition may occur through:

- direct purchase
- financed purchase
- ownership transfer
- contribution to an organization
- inheritance or other legal transfer
- purchase after lease termination
- other documented acquisition

An acquisition record must identify:

- vehicle
- acquiring party
- transferring party when applicable
- acquisition type
- agreement date
- effective ownership date
- agreed price
- currency
- tax or VAT treatment
- supplier
- source documents
- responsible organization
- status

Acquisition agreement, ownership transfer, supplier invoice, and payment must remain separate records.

Vehicle disposal may occur through:

- sale
- return to financing provider
- transfer to another party
- scrapping
- total-loss settlement
- write-off
- donation
- other documented disposal

A disposal record must identify:

- disposal type
- agreement date
- effective date
- receiving party
- sale or settlement amount
- currency
- tax or VAT treatment
- reason
- source documents
- approval
- status

Disposal must close applicable operational relationships without deleting historical records.


## 7. Financing Agreements

Vehicle financing must be represented independently from legal ownership.

Financing agreement types may include:

- loan
- finance lease
- operating lease
- rental
- installment purchase
- internal organization financing
- other contractual financing

A financing agreement must identify:

- agreement identifier
- agreement type
- vehicle
- provider
- customer or debtor
- payer
- guarantor when applicable
- responsible organization
- contract number
- signing date
- valid-from date
- valid-to date
- currency
- financed amount
- initial payment or deposit
- principal
- interest method
- interest rate when applicable
- fees
- tax or VAT treatment
- residual value
- purchase option
- termination conditions
- lifecycle status
- source documents

Financing agreement states may include:

- DRAFT
- ACTIVE
- SUSPENDED
- TERMINATED
- COMPLETED
- DEFAULTED
- ARCHIVED

The payer may differ from the debtor, operator, owner, and user.

A financing agreement does not itself prove payment or ownership transfer.


## 8. Financial Obligations and Payment Schedules

A financing agreement may generate scheduled financial obligations.

A scheduled obligation must identify:

- financing agreement
- obligation type
- sequence number
- period
- issue date when applicable
- due date
- currency
- principal amount
- interest amount
- fee amount
- tax or VAT amount
- total amount
- rounding rule
- expected payer
- expected recipient
- obligation status
- source schedule version

Possible obligation types include:

- initial payment
- regular installment
- lease payment
- rental payment
- interest
- contractual fee
- insurance charge
- purchase-option payment
- termination charge
- final settlement

Possible obligation states include:

- PLANNED
- ISSUED
- DUE
- PARTIALLY_SETTLED
- SETTLED
- OVERDUE
- CANCELLED
- DISPUTED

A scheduled obligation is not a payment.

Changing a payment schedule must create a new version or explicit adjustment.

Historical obligations must retain the schedule version from which they were created.


## 9. Invoices, Payments and Reconciliation

Invoice, payment, bank transaction, and reconciliation must remain separate.

An invoice documents or requests settlement of a financial obligation.

A payment settles all or part of a financial obligation.

A bank transaction is external evidence of money movement.

Reconciliation connects financial records through a controlled matching decision.

An invoice may identify:

- supplier
- customer
- invoice number
- issue date
- taxable date
- due date
- currency
- net amount
- tax or VAT amount
- gross amount
- referenced obligations
- source document
- status

A payment may identify:

- payer
- recipient
- amount
- currency
- payment date
- payment method
- reference
- source
- allocation status

Reconciliation must support:

- exact matching
- partial payment
- combined payment
- split allocation
- overpayment
- underpayment
- fee difference
- currency difference
- manual review
- unmatched transaction

Payment allocation must not overwrite the original invoice, obligation, or bank transaction.

Settlement status must be derived from allocations rather than manually replacing financial history.


## 10. Cost Responsibility and Internal Settlement

Cost responsibility must remain separate from:

- vehicle ownership
- financing debtor
- contractual payer
- bank payer
- vehicle operator
- vehicle driver
- driver compensation

Vehicle-related costs may be allocated to:

- owning organization
- operating organization
- responsible organization
- commercial partner
- driver
- department
- route
- contract
- cost centre
- another authorized party

An internal cost allocation must identify:

- source cost
- allocated party
- allocation type
- amount
- currency
- allocation rule
- agreement reference
- valid period
- creating user or service
- approval
- status
- audit history

Internal settlement may use:

- invoice
- debit note
- credit note
- account balance
- compensation adjustment
- authorized deduction
- direct payment

A cost allocation is not itself a payment.

A proposed deduction from driver compensation must remain separate from the approved compensation calculation.

No deduction may be treated as authorized merely because the driver used the vehicle.

Any deduction or personal financial responsibility must reference an applicable agreement, authorization, or approved decision.


## 11. Vehicle Reserve Funds

A vehicle reserve fund represents an internal controlled financial ledger.

A reserve fund must identify:

- fund identifier
- vehicle or vehicle group
- responsible organization
- owner
- currency
- funding rule
- permitted purposes
- lifecycle status
- valid-from date
- valid-to date when applicable

Reserve contributions may be based on:

- fixed periodic amount
- amount per kilometre
- percentage of revenue
- percentage of compensation
- manual contribution
- custom approved formula

Reserve usage may support:

- maintenance
- repairs
- tyres
- insurance deductible
- replacement vehicle costs
- future acquisition
- other approved vehicle expenses

Each reserve entry must record:

- entry type
- source reference
- amount
- currency
- effective date
- creating user or service
- approval
- running balance
- correction reference when applicable

A reserve-fund balance is not automatically a bank-account balance.

Reserve adjustments must not overwrite original contributions or withdrawals.


## 12. Valuation, Depreciation and Total Cost

Operational vehicle records must remain separate from accounting and valuation records.

Valuation records may include:

- acquisition cost
- initial recognized value
- market valuation
- insurance valuation
- residual value
- estimated sale value
- impairment
- current book value

Depreciation records may include:

- depreciation method
- depreciation period
- start date
- periodic depreciation
- accumulated depreciation
- residual value
- accounting organization
- source
- schedule version

A new valuation must not overwrite earlier valuations.

Operational availability does not determine accounting value.

Accounting value does not determine operational eligibility.

Total cost of ownership may aggregate:

- acquisition cost
- financing cost
- lease or rental cost
- insurance
- fuel
- maintenance
- repairs
- tyres
- taxes and fees
- depreciation
- disposal proceeds

Aggregated cost indicators must remain traceable to their source records.


## 13. Insurance Policies

Insurance policy records must remain separate from vehicle ownership and operational status.

An insurance policy may identify:

- policy identifier
- insurer
- policyholder
- insured party
- beneficiary
- covered vehicles
- insurance type
- contract number
- valid-from date
- valid-to date
- coverage limits
- deductible
- premium
- premium schedule
- currency
- renewal rules
- policy status
- source documents

Insurance types may include:

- mandatory liability insurance
- comprehensive insurance
- assistance
- gap insurance
- cargo-related vehicle coverage
- other insurance products

A stored policy does not by itself prove that coverage is currently valid.

Coverage validity must be evaluated from policy status, effective dates, and applicable verification data.


## 14. Damage, Claims and Repair Events

Vehicle damage, insurance claims, repairs, invoices, payments, and reimbursements must remain separate records.

A damage event may identify:

- vehicle
- occurrence date and time
- reporting date and time
- location
- actual driver
- description
- damage category
- severity
- operational restriction
- responsible organization
- reporting user
- evidence
- status

An insurance claim may identify:

- damage event
- insurance policy
- claim number
- filing date
- insurer
- claimant
- claimed amount
- approved amount
- rejected amount
- deductible
- claim status
- claim decision date when applicable
- claim closure date when applicable

Claim approval, claim closure, reimbursement entitlement, and reimbursement payment must remain separate.

An approved claim amount does not prove that reimbursement was received.

A repair record may identify:

- damage or maintenance reference
- repair provider
- work order
- repair period
- repair scope
- parts
- labour
- expected cost
- actual cost
- invoice references
- completion status

Insurance reimbursement does not erase repair cost.

Deductible allocation must remain separate from the claim, invoice, payment, and internal settlement.


## 15. Documents, Compliance and Restrictions

Vehicle asset records may reference:

- registration documents
- ownership documents
- purchase agreements
- sale agreements
- financing agreements
- lease agreements
- rental agreements
- invoices
- insurance policies
- inspection documents
- permits
- service records
- damage evidence
- disposal documents

A document reference must identify:

- document type
- owner
- related vehicle
- related agreement or event
- issue date
- validity period
- storage reference
- verification status
- access classification
- uploading user
- audit history

Expired, missing, or invalid documents may create operational restrictions.

A restriction must remain separate from the source document and from vehicle availability.

Sensitive financial and ownership documents require controlled access.


## 16. Cross-Organization Visibility and Privacy

Vehicle information may be shared across organizations only through an applicable:

- responsibility relationship
- commercial relationship
- operational assignment
- financing relationship
- ownership relationship
- delegated authority
- data-sharing rule
- contractual requirement

Operationally required shared information may include:

- vehicle identity
- registration number
- vehicle category
- operational status
- applicable restrictions
- current assignment
- availability
- insurance-validity indicator

Shared information does not automatically include:

- acquisition price
- financing balance
- interest rate
- internal reserve-fund balance
- private ownership documents
- internal cost allocations
- driver deductions
- accounting values
- unrelated insurance details

Higher organizational position does not automatically grant access to another organization's vehicle finances.

Ownership does not automatically grant visibility into unrelated operational or personal records.


## 17. Audit, Corrections and Historical Integrity

Sensitive vehicle asset actions must be audited, including:

- vehicle creation
- identity correction
- ownership creation and transfer
- operator change
- custody change
- assignment change
- acquisition
- disposal
- financing creation
- financing amendment
- schedule replacement
- obligation adjustment
- payment allocation
- reconciliation
- internal cost allocation
- reserve-fund entry
- valuation
- depreciation adjustment
- insurance-policy change
- claim update
- document verification
- restriction creation
- manual override

Audit records must identify:

- acting user or service
- organization context
- action
- affected record
- previous values
- new values
- date and time
- reason
- authorization reference
- correlation identifier

Corrections must not silently overwrite historical ownership, financing, payment, or allocation records.

Financial corrections should use reversals, adjustments, or replacement versions.

The original record and correction relationship must remain traceable.


## 18. Integrations and Final Design Rule

The vehicle asset and financing model supports integration with:

- organizations
- users and permissions
- drivers
- vehicle operations
- availability and resource planning
- route executions
- daily reports
- fuel management
- maintenance
- pricing and compensation
- banking and reconciliation
- notifications and rule engine
- audit and data integrity
- document storage
- reporting and KPI modules
- future accounting integrations
- future leasing-provider integrations
- future insurance integrations

The vehicle asset module must not silently approve, settle, reconcile, deduct, or transfer records owned by another module.

Final design rule:

Vehicle identity, ownership, operator, custody, assignment, actual usage, acquisition, disposal, financing agreement, scheduled obligation, invoice, payment, bank transaction, reconciliation, cost allocation, reserve fund, insurance policy, claim, valuation, and depreciation must remain separate controlled and auditable records.
