# TMS Pricing and Settlement Architecture v1.0

## 1. Purpose

This document defines the implementation architecture for the first pricing and settlement foundation of the TMS backend.

It translates the business rules from:

- TMS Pricing and Compensation Model
- TMS Daily Report Process
- TMS User Roles and Permissions
- TMS Core Organization Model
- TMS Audit and Data Integrity Model
- TMS Reporting and KPI Model
- TMS Daily Operations Architecture

into a concrete Laravel module design.

The first implementation supports organization-to-organization pricing and deterministic financial calculation based on approved or closed daily reports.

Employee-driver compensation, invoicing, payment, banking reconciliation, tax calculation, currency conversion and custom formulas remain deferred.

## 2. Sprint 004 Scope

Sprint 004 includes:

- direct organization-relationship pricing
- partner-specific price lists
- immutable price-list versions
- standard pricing items
- delivered parcel pricing
- redirected parcel pricing
- undelivered parcel pricing
- actual kilometre pricing
- fixed operational input snapshots
- deterministic financial calculations
- immutable calculation lines
- calculation lifecycle
- organization-scoped financial visibility
- immutable financial events
- API foundation
- automated tests
- PostgreSQL and SQLite compatibility

Sprint 004 does not include:

- invoices
- payments
- bank statements
- reconciliation
- accounting exports
- tax calculation
- currency conversion
- employee payroll
- direct driver compensation
- bonuses
- penalties
- custom formulas
- route-specific pricing
- depot-specific pricing
- holiday pricing
- weekend pricing
- fuel adjustments
- manual financial adjustments
- frontend user interface

## 3. Core Separation Rule

The implementation preserves separate records for:

- organization relationship
- price list
- price-list version
- price-list item
- daily report
- operational input snapshot
- financial calculation
- financial calculation line
- financial calculation event
- approval
- future settlement
- future invoice
- future payment

No entity may silently replace another entity.

A price list defines the commercial pricing identity.

A price-list version defines one effective and immutable set of rules.

A price-list item defines one priced operational quantity.

A financial calculation applies one price-list version to one fixed daily-report snapshot.

A calculation line preserves one quantity, rate and calculated amount.

An event preserves one controlled lifecycle or audit action.

## 4. Module Boundary

The backend module is:

~~~text
backend/app/Modules/Pricing
~~~

Planned structure:

~~~text
Pricing
├── Controllers
├── Exceptions
├── Models
├── Requests
├── Resources
├── Routes
└── Services
~~~

Initial models:

~~~text
PriceList
PriceListVersion
PriceListItem
FinancialCalculation
FinancialCalculationLine
FinancialCalculationEvent
~~~

Initial services:

~~~text
PriceListQueryService
PriceListWriteService
PriceListVersionService
PricingApplicabilityService
FinancialCalculationService
FinancialCalculationQueryService
FinancialCalculationWorkflow
FinancialCalculationSnapshotBuilder
FinancialCalculationEventPayloadBuilder
~~~

The module follows existing DailyReports conventions where they are applicable.

## 5. Commercial Relationship

Every price list references one existing `organization_relationships` row.

The relationship provides:

- source organization
- target organization
- relationship type
- relationship status
- valid-from time
- valid-until time

For Sprint 004:

- source organization is the service customer
- source organization is the paying party
- target organization is the service provider
- target organization is the receiving party
- the relationship direction is explicit
- the relationship must not be silently reversed
- the two organizations must be distinct
- the relationship must be active for the applicable service date
- pricing must not be inferred only from organization hierarchy

The existing `OrganizationRelationship::isActiveAt()` behavior is the primary validity contract.

## 6. Price List Aggregate

Database table:

~~~text
price_lists
~~~

Planned columns:

~~~text
id
public_id
organization_relationship_id
owner_organization_id
customer_organization_id
provider_organization_id
name
description
currency
status
current_version
created_by_user_id
created_at
updated_at
~~~

Status values:

~~~text
draft
active
archived
~~~

Rules:

- `public_id` is a UUID used by the public API
- the organization relationship is mandatory
- owner organization manages the price list
- customer and provider are stored explicitly
- customer and provider must match relationship direction
- customer and provider must be distinct
- currency is an uppercase three-letter code
- `current_version` starts at 1
- archived price lists cannot be ordinarily modified
- unrelated organizations cannot access the aggregate
- financial history uses restrictive delete behavior

## 7. Price List Version

Database table:

~~~text
price_list_versions
~~~

Planned columns:

~~~text
id
price_list_id
version_number
status
valid_from
valid_until
change_reason
created_by_user_id
approved_by_user_id
approved_at
activated_at
created_at
~~~

Status values:

~~~text
draft
approved
active
replaced
expired
~~~

Rules:

- every version belongs to one price list
- version numbers start at 1
- version numbers are sequential
- draft versions may be edited
- approved versions are immutable
- active versions are immutable
- active versions contain approval metadata
- `valid_from` is required before activation
- `valid_until` may be null
- `valid_until` must not precede `valid_from`
- active periods for one price list must not overlap
- activating a new version does not modify old calculations
- replacing a version preserves the original row and items
- an initial official calculation uses only an active version
- the selected version must be applicable to `service_date`
- `valid_from` and `valid_until` boundaries are inclusive

## 8. Price List Item

Database table:

~~~text
price_list_items
~~~

Planned columns:

~~~text
id
price_list_version_id
code
description
calculation_method
unit
unit_rate
currency
quantity_source
rounding_scale
rounding_method
position
created_at
~~~

Supported pricing codes:

~~~text
delivered_parcels
redirected_parcels
undelivered_parcels
actual_km
~~~

Supported calculation method:

~~~text
quantity_times_rate
~~~

Supported units:

~~~text
parcel
km
~~~

Supported quantity sources:

~~~text
delivered_parcels
redirected_parcels
undelivered_parcels
actual_km
~~~

Rules:

- codes are unique inside one price-list version
- a zero rate is valid
- negative rates are not valid in Sprint 004
- item currency must match price-list currency
- quantity source must correspond to pricing code
- actual kilometre pricing explicitly uses `actual_km`
- planned kilometre pricing remains deferred
- approved-version items are immutable
- item order is deterministic
- no item is silently inherited from another version

Recommended database precision:

~~~text
unit_rate      decimal(14, 4)
~~~

## 9. Daily Report Eligibility

A financial calculation may be created only from a daily report that:

- belongs to an authorized organization context
- has status `approved` or `closed`
- has an approved report version
- has delivered parcel count
- has redirected parcel count
- has undelivered parcel count
- has actual kilometres
- is compatible with the selected commercial relationship
- has not already produced an equivalent calculation for the same source identity

A daily report remains an operational record.

Pricing must not write financial values into `daily_reports`.

Draft, submitted, under-review, correction-requested and corrected reports are not eligible.

## 10. Operational Input Snapshot

Each financial calculation stores an immutable JSON snapshot.

Minimum snapshot fields:

~~~text
daily_report_id
daily_report_public_id
daily_report_version
organization_id
route_number
service_date
performed_by_driver_id
delivered_parcels
redirected_parcels
undelivered_parcels
planned_km
actual_km
actual_km_source
approved_at
approved_by_user_id
closed_at
captured_at
~~~

Rules:

- snapshot values come from the approved report version
- current mutable values must not replace stored snapshot values
- snapshot creation occurs in the calculation transaction
- snapshot content is never edited
- later report amendments require a new calculation or adjustment process
- original financial calculations remain preserved
- snapshot serialization is deterministic
- internal data not needed for financial calculation is excluded

## 11. Financial Calculation

Database table:

~~~text
financial_calculations
~~~

Planned columns:

~~~text
id
public_id
organization_id
organization_relationship_id
price_list_id
price_list_version_id
daily_report_id
daily_report_version
calculation_version
status
currency
input_snapshot
subtotal_amount
total_amount
calculated_by_user_id
calculated_at
approved_by_user_id
approved_at
closed_at
supersedes_calculation_id
created_at
updated_at
~~~

Status values:

~~~text
calculated
under_review
approved
closed
cancelled
~~~

Initial workflow:

~~~text
calculated
    ↓
under_review
    ↓
approved
    ↓
closed
~~~

Cancellation is allowed only before approval.

Rules:

- one calculation uses one price-list version
- one calculation uses one daily-report snapshot
- calculation version starts at 1
- currency matches the selected price list
- subtotal and total are stored decimal values
- total equals the sum of lines in Sprint 004
- approved calculations are locked
- closed calculations are final
- approval does not mean payment
- equivalent duplicate calculations are rejected
- recalculation preserves the original calculation
- `supersedes_calculation_id` supports controlled future recalculation

Recommended database precision:

~~~text
subtotal_amount decimal(16, 2)
total_amount    decimal(16, 2)
~~~

## 12. Financial Calculation Line

Database table:

~~~text
financial_calculation_lines
~~~

Planned columns:

~~~text
id
financial_calculation_id
price_list_item_id
pricing_code
description
quantity
unit
unit_rate
currency
line_amount
source_field
rounding_scale
rounding_method
position
created_at
~~~

Rules:

- every line belongs to one calculation
- lines are immutable
- pricing values are copied from the selected item
- stored lines do not dynamically read later price-list changes
- quantities come from the calculation snapshot
- currency matches the parent calculation
- positions are deterministic
- one calculation contains at most one line per supported pricing code
- explicit zero-value lines may be preserved
- negative quantities and amounts are rejected in Sprint 004

Recommended precision:

~~~text
quantity    decimal(14, 3)
unit_rate   decimal(14, 4)
line_amount decimal(16, 2)
~~~

## 13. Financial Calculation Event

Database table:

~~~text
financial_calculation_events
~~~

Planned columns:

~~~text
id
financial_calculation_id
organization_id
event_type
from_status
to_status
acted_by_user_id
reason
metadata
created_at
~~~

Event types:

~~~text
calculated
review_started
approved
closed
cancelled
~~~

Rules:

- events are append-only
- event rows are never updated
- every event records actor and organization context
- workflow events preserve previous and new statuses
- initial calculation creates a `calculated` event
- event creation occurs in the same transaction as its controlled action
- metadata must not expose unrelated commercial data

## 14. Calculation Formula

For each supported price-list item:

~~~text
line_amount = round(quantity × unit_rate)
~~~

Initial line order:

~~~text
1. delivered_parcels
2. redirected_parcels
3. undelivered_parcels
4. actual_km
~~~

Initial total:

~~~text
total_amount = sum(line_amount)
~~~

Sprint 004 does not calculate:

- tax
- bonuses
- penalties
- fuel adjustments
- manual adjustments
- currency conversion

Decimal calculations must avoid binary floating-point arithmetic.

Calculation services must use deterministic decimal handling and explicit rounding.

## 15. Organization Scope and Visibility

Pricing access requires:

- authenticated user
- active organization membership
- verified organization context
- explicit financial permission
- access to the direct commercial relationship

Initial permissions:

~~~text
pricing.view
pricing.manage
compensation.view
compensation.manage
~~~

Rules:

- `pricing.view` permits authorized price-list reads
- `pricing.manage` permits authorized draft management
- `compensation.view` permits authorized calculation reads
- `compensation.manage` permits calculation and workflow actions
- operational access does not automatically grant financial access
- unrelated partner price lists are not exposed
- internal pricing of another organization is not exposed automatically
- route model binding must not bypass organization scope
- source and target parties see only data allowed by their direct relationship and permissions

## 16. API Foundation

Planned price-list read endpoints:

~~~text
GET /price-lists
GET /price-lists/{priceList}
GET /price-lists/{priceList}/versions
GET /price-lists/{priceList}/versions/{version}
~~~

Planned price-list write endpoints:

~~~text
POST /price-lists
POST /price-lists/{priceList}/versions
PUT /price-lists/{priceList}/versions/{version}
POST /price-lists/{priceList}/versions/{version}/approve
POST /price-lists/{priceList}/versions/{version}/activate
~~~

Planned calculation read endpoints:

~~~text
GET /financial-calculations
GET /financial-calculations/{financialCalculation}
GET /financial-calculations/{financialCalculation}/events
~~~

Planned calculation write endpoints:

~~~text
POST /financial-calculations
POST /financial-calculations/{financialCalculation}/review
POST /financial-calculations/{financialCalculation}/approve
POST /financial-calculations/{financialCalculation}/close
POST /financial-calculations/{financialCalculation}/cancel
~~~

The API may be implemented in smaller validated blocks.

Public aggregate binding uses `public_id`.

Internal numeric identifiers must not be exposed unnecessarily.

### 16.1 Sprint 005 Price-List Read API

The first Sprint 005 API unit implements:

~~~text
GET /api/v1/price-lists
GET /api/v1/price-lists/{priceList}
GET /api/v1/price-lists/{priceList}/versions
GET /api/v1/price-lists/{priceList}/versions/{version}
~~~

The unit follows these rules:

- all routes require Sanctum authentication
- all routes require a verified `X-Organization-ID` context
- all routes require the organization-scoped `pricing.view` permission
- a price list is visible only when the verified organization is its customer or provider
- the related commercial relationship must be active and valid at request time
- price-list aggregate lookup uses `public_id`
- a version is resolved only through its already-scoped parent price list
- nested version lookup uses `version_number`
- API Resources do not expose database primary keys or foreign keys
- price-list write operations and calculation endpoints remain deferred

## 17. Transaction and Concurrency Rules

Controlled writes use database transactions.

Calculation creation atomically:

- locks or consistently reads the selected daily report
- verifies organization access
- verifies report eligibility
- verifies that the selected immutable daily-report version equals `daily_reports.current_version`
- verifies relationship validity for `service_date` through `OrganizationRelationship::isActiveAt()`
- verifies that the active price-list version includes `service_date` in its effective period
- builds the immutable snapshot
- creates the calculation
- creates calculation lines
- stores totals
- creates the initial event

Version activation atomically:

- verifies draft ownership
- verifies approval
- verifies effective period
- rejects overlapping active versions
- marks a previous active version as replaced when applicable
- activates the selected version

Optimistic concurrency is required where a current version can change between read and write.

Ambiguous pricing fails instead of selecting a rate silently.

## 18. Database Integrity

Database constraints should enforce:

- UUID uniqueness
- required foreign keys
- distinct customer and provider organizations
- positive version numbers
- non-negative rates
- non-negative quantities
- non-negative amounts
- valid status values
- valid pricing codes
- valid calculation methods
- valid units
- valid quantity sources
- valid effective periods
- unique item code per version
- unique calculation-line code per calculation
- unique equivalent source calculation
- valid superseded-calculation reference

Financial history uses `restrictOnDelete` unless a documented exception is required.

PostgreSQL check constraints are used where supported.

SQLite-compatible validation and tests provide equivalent behavioral protection.

## 19. Testing Strategy

Tests cover:

- price-list creation
- organization scope
- relationship direction
- inactive relationship rejection
- relationship service-date validity rejection
- version creation
- version approval
- version activation
- version immutability
- overlapping period rejection
- pricing-item validation
- zero-rate acceptance
- negative-rate rejection
- daily-report eligibility
- draft report rejection
- submitted report rejection
- approved report acceptance
- closed report acceptance
- stale daily-report version rejection
- immutable snapshot creation
- parcel quantities
- actual-kilometre quantity
- decimal line calculation
- deterministic rounding
- total calculation
- duplicate calculation prevention
- price-list service-date applicability rejection
- cross-organization rejection
- unrelated relationship rejection
- calculation workflow
- approved-calculation locking
- event creation
- transaction rollback
- database constraints
- API validation
- API Resources
- Larastan
- Pint
- full backend regression

Tests verify both application outcomes and database state.

## 20. Deferred Scope

Deferred to later stages:

- employee compensation
- external-driver compensation
- self-employed-driver compensation
- payroll
- tax and VAT
- multiple currencies
- exchange rates
- invoices
- payments
- bank reconciliation
- accounting classification
- custom formulas
- bonuses
- penalties
- manual adjustments
- fuel adjustments
- minimum route guarantees
- depot-specific pricing
- route-specific pricing
- holiday pricing
- weekend pricing
- financial disputes
- invoiced recalculation
- debit and credit documents
- frontend administration

## 21. Acceptance Criteria

Sprint 004 foundation is complete when:

- the Pricing module exists
- direct-relationship price lists are organization-scoped
- customer and provider direction is explicit
- price-list versions are immutable after approval
- active-version overlap is prevented
- standard pricing items are validated
- approved or closed daily reports can be calculated
- operational input is preserved as an immutable snapshot
- calculation lines preserve quantity and rate
- totals use deterministic decimal arithmetic
- lifecycle actions create immutable events
- approved calculations are locked
- unrelated organizations cannot read financial data
- pricing and compensation permissions are enforced
- PostgreSQL tests pass
- SQLite tests pass
- Larastan passes
- Pint passes
- full backend regression passes
- documentation matches implementation
- no existing DailyReports behavior is unintentionally changed
- no existing Organizations behavior is unintentionally changed
- no existing Identity behavior is unintentionally changed
