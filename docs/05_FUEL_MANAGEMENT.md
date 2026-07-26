# TMS Fuel Management v1.1

## 1. Purpose

This document defines the fuel management architecture and business rules of the TMS platform.

The model supports:

- company fuel cards
- cards used by employee drivers
- cards used by external carrier drivers
- cards used by sub-carrier drivers
- organization-owned fuel cards
- externally provided fuel cards
- ORLEN transaction imports
- MOL transaction imports
- future fuel providers
- card assignment history
- transaction matching
- fuel cost allocation
- invoice reconciliation
- fuel consumption analysis
- anomaly detection

The model must remain consistent with:

- TMS Business Principles
- TMS Core Organization Model
- TMS User Roles and Permissions
- TMS Driver and Vehicle Model
- TMS Daily Report Process
- financial responsibility rules
- audit and data integrity rules


## 2. Core Separation Principle

The following concepts must remain separate:

- fuel card identity
- fuel card provider
- fuel card owner
- responsible organization
- physical card custodian
- authorized card user
- assigned vehicle
- imported provider transaction
- accounting cost
- internal allocation
- settlement responsibility
- invoice reconciliation

These relationships may be connected but must not be stored as one combined relationship.

Assignment of a card does not automatically transfer:

- legal ownership
- unrestricted usage rights
- all financial responsibility
- ownership of imported transactions
- visibility into unrelated organization data

Every transfer of responsibility must be explicit, time-bounded, and auditable.


## 3. Fuel Card Identity

A fuel card is an independent business resource.

A fuel card record may contain:

- internal identifier
- provider
- masked card number
- provider card identifier
- card type
- active status
- valid-from date
- expiration date
- currency
- purchase restrictions
- daily or monthly limits
- allowed fuel products
- provider account reference
- organization-specific label

Full sensitive card numbers must not be exposed unnecessarily.

Card identifiers used for imports must support reliable matching without weakening security.


## 4. Fuel Card Ownership

Fuel card ownership identifies the party that legally or economically controls the card contract.

The owner may be:

- carrier organization
- sub-carrier organization
- another business organization
- external contractual partner
- card provider account holder

Ownership must define:

- owner reference
- ownership type
- valid-from date
- valid-to date when applicable
- provider contract reference
- financial account reference when applicable

The owner may be different from:

- responsible organization
- card custodian
- authorized driver
- assigned vehicle
- organization receiving an internal charge

Ownership history must be preserved.


## 5. Responsibility, Custody and Authorized Use

Fuel card responsibility identifies the organization responsible for controlled use of the card during a defined period.

Fuel card custody identifies the organization or user physically holding the card.

Authorized use identifies who may use the card and under which conditions.

These concepts must remain separate.

A responsibility relationship must define:

- responsible organization
- valid-from date and time
- valid-to date and time when applicable
- permitted scope
- cost responsibility
- monitoring responsibility
- applicable limits
- assigning authority
- agreement reference when applicable

A custody relationship must define:

- current custodian
- handover date and time
- expected return date
- actual return date
- handover condition
- return condition
- handover confirmation

Responsibility and custody do not transfer legal ownership unless a separate ownership change exists.


## 6. Time-Bounded Card Assignments

A fuel card may be assigned to:

- organization
- driver
- vehicle
- operational team
- depot
- temporary replacement user
- shared card pool

An assignment must define:

- fuel card
- assignment target
- assignment type
- responsible organization
- valid-from date and time
- valid-to date and time when applicable
- assigning user
- usage restrictions
- assignment status
- reason when required

A card may be assigned to both a driver and a vehicle when the business process requires both relationships.

The system must validate conflicting or overlapping assignments.

A later assignment must not rewrite the context of historical transactions.


## 7. Providers and Import Sources

The initial supported fuel providers include:

- ORLEN
- MOL

The architecture must support additional providers without changing the core transaction model.

Each provider may have:

- one or more source formats
- organization-specific account identifiers
- provider-specific card identifiers
- different column names
- different date and number formats
- different tax representations
- different currencies
- different transaction identifiers

Provider-specific data must be normalized into a common TMS transaction model.

The original provider values must remain preserved.


## 8. Monthly Import Batches

ORLEN and MOL transaction data may be received as monthly Excel files.

Each imported file creates one import batch.

An import batch must record:

- provider
- source organization
- original file name
- file hash
- imported file period
- import date and time
- importing user
- import template version
- total source rows
- accepted rows
- duplicate rows
- rejected rows
- warning rows
- batch status

The original file must remain traceable through its hash and import metadata.

Reimporting the same file must not silently create duplicate transactions.


## 9. Source Preservation and Normalization

Each imported row must preserve:

- original batch reference
- original row number
- original provider values
- normalized values
- validation result
- matching result
- processing status
- error or warning details

Normalization may convert:

- date and time formats
- decimal separators
- currency formats
- litre units
- card identifiers
- station identifiers
- tax fields
- vehicle registration formats

Normalization must not destroy or overwrite the original source values.

Corrections must be stored as controlled enrichments or amendments.


## 10. Fuel Transaction Identity and Deduplication

Every normalized fuel transaction must have a unique internal identifier.

Where provided, the external provider transaction identifier must be preserved.

Duplicate detection may use:

- provider
- provider transaction identifier
- provider account
- card identifier
- transaction date and time
- station
- quantity
- amount
- currency
- source batch
- source row

Provider transaction identifiers should be preferred when they are reliable.

A duplicate candidate must be:

- rejected,
- linked to the existing transaction,
- or explicitly resolved by an authorized user.

Duplicate resolution must be auditable.


## 11. Fuel Transaction Data

A fuel transaction may include:

- provider
- provider transaction identifier
- transaction date
- transaction time
- posting date
- card identifier
- station name
- station identifier
- station country
- product
- quantity
- unit of measure
- unit price
- net amount
- tax amount
- gross amount
- currency
- discount
- vehicle registration
- odometer value
- invoice reference
- original source description

Fuel quantity should be stored in litres when the product is liquid fuel.

The original unit must also be preserved when conversion is required.

Amounts must always include their currency.


## 12. Transaction Matching

A fuel transaction may be matched with:

- fuel card
- card owner
- responsible organization
- card custodian
- authorized driver
- assigned vehicle
- route execution
- daily report
- operational period
- invoice
- internal allocation record

Matching must consider the transaction date and time.

A current card assignment must not be applied automatically to an older transaction when the assignment was not valid at the transaction time.

Matching may use:

- provider card identifier
- assignment history
- vehicle registration
- driver assignment
- transaction time
- route or trip usage
- organization responsibility
- odometer information
- provider account

Every automatic match must store its matching method and confidence or rule result.


## 13. Manual Resolution and Exceptions

Transactions that cannot be matched reliably must enter an exception workflow.

Possible exception reasons include:

- unknown card
- inactive card
- no valid assignment
- conflicting assignments
- unknown vehicle
- invalid amount
- invalid quantity
- duplicate candidate
- transaction outside card validity
- transaction outside responsibility period
- unexpected product
- odometer inconsistency
- missing invoice reference

An authorized user may resolve an exception manually.

Manual resolution must record:

- acting user
- organization context
- previous matching state
- selected card
- selected organization
- selected driver or vehicle
- resolution date and time
- resolution reason
- supporting note

Imported provider values must remain unchanged.


## 14. External Cost and Financial Responsibility

The provider transaction represents the external fuel purchase.

The accounting cost represents how the purchase is recognized financially.

Financial responsibility identifies the party responsible for settlement.

These concepts must remain separate.

The external transaction may contain:

- gross provider amount
- net provider amount
- tax amount
- currency
- discount
- provider invoice reference

The financially responsible organization may be determined by:

- card ownership
- explicit responsibility agreement
- valid responsibility assignment
- provider contract
- organization rules

A user or vehicle assignment alone must not silently transfer the entire financial obligation.


## 15. Internal Allocation and Settlement

An external fuel cost may be allocated internally to one or more targets.

Allocation targets may include:

- organization
- sub-carrier organization
- driver
- vehicle
- route
- trip
- cost centre
- contract
- settlement account

Internal allocation must not modify the original provider transaction.

An allocation record must identify:

- source fuel transaction
- allocation target
- allocated quantity when applicable
- allocated amount
- currency
- allocation rule
- responsible organization
- creating user
- calculation version
- status
- date and time

The sum of allocations must be validated against the allocatable source amount.

Partial allocation and unallocated balances must remain visible.

Internal allocation may later support:

- driver charge
- vehicle cost
- carrier settlement
- sub-carrier settlement
- reimbursement
- compensation deduction
- management reporting


## 16. Consumption, Mileage and Anomaly Rules

Fuel consumption analysis may connect transactions with:

- vehicle
- actual kilometres
- odometer values
- route executions
- daily reports
- fuel type
- vehicle characteristics
- historical consumption

Possible calculations include:

consumption_per_100_km =
fuel_litres /
distance_km *
100

The calculation requires a valid distance greater than zero.

Anomaly rules may identify:

- unusually high consumption
- fuel purchase without vehicle assignment
- card use outside the authorized period
- card use outside allowed countries
- unexpected fuel product
- excessive transaction quantity
- repeated transactions
- decreasing odometer values
- transaction during driver inactivity
- transaction without related operational activity

An anomaly is a review signal and does not automatically prove misuse.


## 17. Invoice Reconciliation, Visibility and Audit

Fuel transactions may be reconciled with provider invoices.

Reconciliation may compare:

- transaction count
- transaction amount
- tax amount
- currency
- provider account
- invoice period
- credit notes
- discounts
- unmatched items

Reconciliation statuses may include:

- pending
- partially matched
- matched
- discrepancy
- approved
- closed

Visibility must follow:

- organization context
- card ownership
- responsibility relationship
- explicit permission
- permission scope
- financial relationship
- data-sharing rules

A higher-level organization does not automatically receive access to all internal allocations of another organization.

Sensitive actions must be audited, including:

- card creation
- ownership changes
- responsibility changes
- custody handover
- card assignment
- import
- duplicate resolution
- manual matching
- allocation
- reconciliation
- administrative override

Audit records must preserve previous and new values.


## 18. Integrations and Final Design Rule

Fuel management supports integration with:

- ORLEN files
- MOL files
- future provider APIs
- vehicles
- drivers
- organization management
- daily reports
- GPS and telematics
- maintenance
- pricing and compensation
- invoicing
- accounting
- banking and reconciliation
- notifications and rule engine
- reporting and KPI modules

Imported transactions must not silently overwrite approved financial or operational records.

Final design rule:

Fuel card identity, ownership, responsibility, custody, authorized use, provider transaction, accounting cost, internal allocation, and settlement responsibility must remain separate controlled records connected through time-bounded and auditable relationships.
