# TMS Pricing and Compensation Model v1.1

## 1. Purpose

This document defines the pricing, compensation, calculation, and commercial settlement architecture of the TMS platform.

The model supports:

- individual price lists for commercial partners
- carrier organization settlements
- sub-carrier organization settlements
- employee driver compensation
- external driver compensation
- delivered parcel pricing
- redirected parcel pricing
- undelivered parcel pricing
- kilometre pricing
- route-based pricing
- bonuses and penalties
- time-bounded pricing rules
- controlled recalculation
- revenue, cost, and margin reporting
- future invoicing and banking reconciliation

The model must remain consistent with:

- TMS Business Principles
- TMS Core Organization Model
- TMS User Roles and Permissions
- TMS Daily Report Process
- TMS Fuel Management
- financial responsibility rules
- audit and data integrity rules


## 2. Core Separation Principle

The following concepts must remain separate:

- commercial relationship
- contract
- price list
- price list version
- pricing item
- operational source record
- calculation input snapshot
- calculated result
- approval
- compensation obligation
- invoice
- payment
- reconciliation
- accounting classification

A price list defines calculation rules.

A calculation applies those rules to a fixed input snapshot.

An approved calculation creates a controlled financial result.

An invoice documents or requests settlement of the financial result.

A payment settles the financial obligation.

Neither an invoice nor a payment replaces the calculation history.


## 3. Direct Commercial Relationship

Every price list belongs to one defined commercial relationship between two parties.

The relationship must identify:

- service customer
- service provider
- paying party
- receiving party
- responsible organizations
- contract reference
- relationship type
- valid-from date
- valid-to date when applicable
- settlement currency
- tax configuration
- active status

Pricing between two parties is independent from pricing used in another relationship.

Example:

A carrier may have one price list agreed with its customer and another price list used to compensate a sub-carrier.

The customer does not automatically receive visibility into the internal sub-carrier price list.


## 4. Partner-Specific Price Lists

TMS does not use one global price list for all parties.

Each direct commercial relationship may have its own price list.

A price list must identify:

- internal identifier
- name
- commercial relationship
- price list owner
- customer party
- provider party
- currency
- version
- lifecycle status
- valid-from date and time
- valid-to date and time when applicable
- approval information
- contract reference
- description

Different partners may receive different rates for the same operational activity.

A price list must never be inferred only from organizational hierarchy.


## 5. Price List Lifecycle and Versioning

A price list version may use lifecycle states such as:

- DRAFT
- UNDER_REVIEW
- APPROVED
- ACTIVE
- EXPIRED
- REPLACED
- ARCHIVED

Only an approved and applicable version may be used for an official calculation.

Each version must preserve:

- version number
- effective period
- creation date and time
- creating user
- approving user
- approval date and time
- change reason
- predecessor version
- complete pricing items

Activating a new version must not modify calculations created from an older version.

Overlapping active versions for the same relationship and scope must be prevented or explicitly resolved.


## 6. Pricing Scope and Applicability

A price list or pricing item may be limited by scope.

Scope conditions may include:

- organization
- commercial relationship
- driver classification
- depot
- route
- service type
- parcel type
- vehicle category
- day of week
- weekend or holiday
- geographic area
- contract
- operational period
- minimum or maximum quantity
- quality condition

The system must evaluate whether a pricing item is applicable before using it.

The calculation result must record which applicability conditions were satisfied.


## 7. Standard Pricing Items

Standard pricing items include:

- delivered_parcels
- redirected_parcels
- undelivered_parcels
- actual_km
- planned_km
- approved_km
- fixed_route
- working_time
- waiting_time
- bonus
- penalty
- reimbursement
- fuel_adjustment
- manual_adjustment

Each item must define:

- pricing code
- description
- calculation method
- unit
- unit rate
- currency
- quantity source
- rounding rule
- applicability conditions
- valid-from date
- valid-to date when applicable

Delivered, redirected, and undelivered parcels must remain separate pricing quantities.

A zero rate is permitted, but it must be explicitly defined when the item participates in the agreement.


## 8. Kilometre Pricing Basis

A kilometre pricing item must explicitly define its kilometre basis.

Supported bases may include:

- actual_km
- planned_km
- approved_km
- contractual_fixed_km

For standard route compensation, actual_km may be used when this is required by the commercial agreement.

The basis must not be selected silently during calculation.

The calculation must preserve:

- kilometre basis
- source daily report
- source report version
- source value
- applied rate
- calculated amount
- any authorized override

A kilometre deviation warning does not automatically change the pricing basis.

An override must be authorized, justified, and audited.


## 9. Custom Pricing Items and Formulas

Organizations may define custom pricing items for their own direct commercial relationships.

Examples include:

- weekend bonus
- holiday bonus
- special area surcharge
- vehicle category surcharge
- quality bonus
- complaint penalty
- waiting-time compensation
- minimum route guarantee
- performance threshold
- individual contractual adjustment

A custom item must define:

- unique code
- display name
- calculation method
- input variables
- unit
- currency
- conditions
- priority
- effective period
- owner organization
- approval status

Custom formulas must be versioned.

Changing a formula must not rewrite earlier calculations.


## 10. Pricing Priority and Conflict Resolution

More than one pricing rule may appear applicable to the same operational quantity.

The system must use deterministic priority rules.

Possible precedence levels include:

1. explicit contract-specific item
2. partner-specific item
3. depot or route-specific item
4. organization default item
5. platform fallback item

The applicable priority model must be configurable.

The calculation must record:

- all evaluated rules
- selected rule
- rejected conflicting rules
- selection reason
- applied priority

Ambiguous unresolved pricing must create an exception instead of silently choosing a rate.


## 11. Operational Input Snapshot

Financial calculation must use a fixed snapshot of approved operational data.

The snapshot may include:

- route execution identifier
- daily report identifier
- approved report version
- service date
- responsible organization
- actual driver
- delivered_parcels
- redirected_parcels
- undelivered_parcels
- planned_km
- actual_km
- approved_km when applicable
- actual vehicle
- operational classifications
- approval date and time

The snapshot must remain unchanged after calculation.

Later changes to the operational record require a new calculation or adjustment.

A calculation must never rely only on current mutable values.


## 12. Calculation Model

A calculation applies one approved price list version to one defined input snapshot.

Each calculation must identify:

- calculation identifier
- commercial relationship
- price list identifier
- price list version
- operational input snapshot
- calculation date and time
- calculating user or service
- calculation status
- currency
- calculation lines
- total amount

A standard calculation line may use:

line_amount = quantity * unit_rate

The final result may include:

- subtotal
- bonuses
- penalties
- adjustments
- tax basis
- tax amount
- total amount

Every calculation line must preserve:

- pricing item
- quantity
- unit
- rate
- currency
- formula version
- source field
- line amount
- rounding result


## 13. Compensation and Settlement Parties

Compensation identifies an amount owed to a defined receiving party.

The receiving party may be:

- carrier organization
- sub-carrier organization
- employee driver
- external driver
- self-employed driver
- another contractual provider

Driver compensation and organization settlement are separate financial relationships.

A driver performing a route does not automatically determine who receives the commercial payment.

The receiving party must be determined by:

- commercial relationship
- contract
- active price list
- compensation rule
- organization responsibility
- service date

A compensation record must reference the calculation that created it.


## 14. Review, Approval and Closure

Calculation lifecycle states may include:

- DRAFT
- CALCULATED
- UNDER_REVIEW
- APPROVED
- DISPUTED
- ADJUSTED
- CLOSED
- CANCELLED

Approval must record:

- approving user
- organization context
- calculation version
- approval date and time
- approved amount
- currency
- review notes when applicable

An approved calculation becomes locked against ordinary editing.

Closure indicates that the controlled financial process has reached its final operational state.

Approval does not prove that payment has occurred.


## 15. Corrections, Recalculation and Adjustments

An approved daily report may later receive a controlled amendment.

When financial input changes, the system must determine which calculations are affected.

A recalculation must:

- reference the original calculation
- use the amended report version
- use the price list version applicable to the service date
- preserve the original calculation
- create a new calculation version
- record the recalculation reason
- identify changed input values
- calculate the financial difference

The original approved result must not be overwritten.

When the original result has already been invoiced or paid, the difference must be represented through:

- adjustment
- credit
- debit
- correction settlement
- future-period compensation item

Every correction path must remain auditable.


## 16. Currency, Tax and Rounding

Every price list and calculation must define its currency.

Amounts in different currencies must not be combined without an explicit conversion process.

Currency conversion must preserve:

- source currency
- target currency
- exchange rate
- exchange-rate source
- exchange-rate date
- converted amount
- rounding result

Tax configuration may include:

- tax payer status
- tax rate
- tax-inclusive pricing
- tax-exclusive pricing
- reverse-charge or other configured treatment
- effective period

Rounding rules must define:

- decimal precision
- line-level or total-level rounding
- rounding method
- currency precision

Tax and rounding configuration must be versioned with the applicable financial rules.


## 17. Financial Visibility and Audit

Financial visibility must follow:

- commercial relationship
- organization context
- explicit permission
- permission scope
- price list ownership
- financial responsibility
- data-sharing rules

An organization may see:

- its direct revenue
- its direct costs
- its direct settlements
- its authorized margins
- its own compensation rules

An organization does not automatically see:

- internal driver compensation of another organization
- unrelated partner price lists
- internal profit calculations of another organization
- private contracts outside its direct relationship

Sensitive actions must be audited, including:

- price list creation
- price list approval
- rate changes
- version activation
- calculation
- manual override
- approval
- dispute
- recalculation
- adjustment
- cancellation
- closure

Audit records must preserve previous and new values.


## 18. Integrations and Final Design Rule

Pricing and compensation supports integration with:

- daily reports
- route executions
- drivers
- organizations
- fuel management
- invoices
- accounting
- bank statements
- reconciliation
- notifications and rule engine
- reporting and KPI modules
- external commercial systems

Imported or recalculated data must not silently overwrite approved financial results.

Final design rule:

Commercial relationship, price list, price list version, operational snapshot, calculation, approval, compensation obligation, invoice, payment, and reconciliation must remain separate controlled records connected through immutable identifiers and auditable versions.
