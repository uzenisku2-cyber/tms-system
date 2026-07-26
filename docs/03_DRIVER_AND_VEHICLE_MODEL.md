# TMS Driver and Vehicle Model v1.1

## 1. Purpose

This document defines the driver and vehicle architecture of the TMS platform.

The model supports:

- employee drivers
- drivers working for external carrier organizations
- drivers working for sub-carrier organizations
- self-employed drivers
- organization owners who also perform deliveries
- delegated operational management
- organization-owned vehicles
- user-owned vehicles
- externally owned vehicles
- temporary and long-term vehicle assignments
- historical responsibility tracking
- future fleet and telematics integrations

The model must remain consistent with:

- TMS Business Principles
- TMS Core Organization Model
- TMS User Roles and Permissions
- resource ownership rules
- organizational responsibility relationships
- audit and data integrity rules


## 2. Core Separation Principle

The following concepts must remain separate:

- user identity
- driver profile
- organization membership
- operational role
- permission assignment
- contractual relationship
- vehicle identity
- vehicle ownership
- vehicle operating responsibility
- vehicle custody
- driver assignment
- route or trip usage

These concepts may be related, but they must not be stored as one combined relationship.

A change in one relationship must not silently modify another relationship.


## 3. User Identity and Driver Profile

A driver is a full TMS user with an associated driver profile.

The user identity represents:

- authentication
- personal account
- communication identity
- security settings
- terms acceptance
- platform access

The driver profile represents operational driver information.

Possible driver profile data includes:

- driving eligibility
- licence information
- operational status
- employment or contractual classification
- assigned depot
- driver-specific identifiers
- application access status
- relevant qualification information

A user may exist without being a driver.

A driver profile must reference a valid user identity.


## 4. Driver Organization Relationships

A driver may have relationships with one or more organizations.

Examples include:

- employee of a carrier organization
- contractor of a carrier organization
- driver of a sub-carrier organization
- owner of an organization
- member of an organization
- temporarily delegated driver
- externally supplied driver

An organization relationship must define:

- organization
- user or driver
- relationship type
- effective date
- expiration date when applicable
- active status
- responsibility scope
- contractual reference when applicable

Organization membership does not automatically determine all permissions.

Permissions and visibility must be evaluated separately.


## 5. Driver Operational Roles

A driver may perform more than one operational role.

Examples:

- driver only
- driver and dispatcher
- driver and organization owner
- driver and organization administrator
- driver and financial user
- driver with delegated review permissions

Multiple business functions are represented through:

- organization memberships
- role assignments
- permission grants
- delegated responsibility
- operational assignments

A user performing several functions still has one user identity.


## 6. No Driver-to-Driver Hierarchy

TMS does not use a driver-to-driver ownership hierarchy.

One driver does not become the owner of another driver.

Operational supervision is represented through:

- organization responsibility
- dispatcher assignment
- delegated management
- team or operational scope
- explicit permissions
- valid responsibility relationships

Example:

Driver A may also have dispatcher permissions for an authorized group.

Driver B may have driver permissions only.

This does not make Driver B subordinate property or data of Driver A.

The hierarchy represents business responsibility, not employment ownership.


## 7. Driver Status and Eligibility

A driver profile may have lifecycle states such as:

- invited
- active
- temporarily unavailable
- suspended
- inactive
- archived

Operational eligibility may depend on:

- active user account
- active organization relationship
- valid driver profile
- required licence validity
- accepted terms
- required permissions
- organization rules
- absence of blocking restrictions

An inactive or suspended driver must not receive new operational assignments unless an authorized exception exists.

Historical records must remain preserved after status changes.


## 8. Vehicle Core Principle

A vehicle is an independent business resource.

Vehicle identity must remain separate from:

- its current owner
- its current operator
- its current custodian
- its assigned organization
- its assigned driver
- its current route or trip
- its financing arrangement
- its fuel card assignment

Vehicle ownership and vehicle usage are different concepts.


## 9. Vehicle Identity

A vehicle record represents the persistent identity of a physical vehicle.

Possible identity and descriptive data includes:

- registration number
- VIN
- manufacturer
- model
- production year
- fuel type
- current mileage
- operational status
- internal fleet number
- vehicle category
- capacity information
- visual attributes
- technical identifiers

Registration number may change during the life of a vehicle.

VIN should therefore be treated as the strongest physical identity where available.

Changes to identifying information must be auditable.


## 10. Vehicle Ownership

Vehicle ownership identifies the legal or economic owner of the vehicle.

A vehicle owner may be:

- carrier organization
- sub-carrier organization
- individual user
- self-employed driver
- leasing company
- rental company
- another external legal entity

Ownership must define:

- owner type
- owner reference
- valid-from date
- valid-to date when applicable
- ownership classification
- supporting agreement when applicable

Ownership does not automatically grant operational access to all users of the owning organization.

Historical ownership relationships must be preserved.


## 11. Vehicle Operating Responsibility

The vehicle operator is the organization responsible for operating the vehicle within a defined period.

The operator may be different from the owner.

Example:

- a leasing company owns the vehicle,
- a carrier organization operates the vehicle,
- a driver uses the vehicle,
- another organization finances selected costs.

Operating responsibility may include:

- operational availability
- maintenance coordination
- insurance administration
- legal operating compliance
- vehicle assignment
- damage reporting
- operational cost responsibility

The exact responsibilities must be configurable by agreement and organization rules.


## 12. Vehicle Custody and Assignment

Vehicle custody identifies who currently controls or stores the vehicle.

Custody may belong to:

- an organization
- a depot
- a fleet manager
- a driver
- an external service provider

Custody is not the same as ownership or operating responsibility.

A vehicle assignment must define:

- vehicle
- receiving organization or user
- assignment purpose
- valid-from date and time
- valid-to date and time when applicable
- assigning authority
- return status
- assignment notes
- audit information

Overlapping assignments must be validated according to the assignment type.


## 13. Driver-Vehicle Assignment

A driver may be assigned to a vehicle for:

- a long-term period
- a work shift
- a specific day
- a specific route
- a specific trip
- temporary replacement
- emergency use

The assignment must define:

- vehicle
- driver
- responsible organization
- assignment type
- valid-from date and time
- valid-to date and time
- assignment status
- assigning user
- reason when required

A long-term assigned driver is not necessarily the driver who used the vehicle on every route.

Actual usage must be recorded separately.


## 14. Route and Trip Usage

Actual vehicle usage belongs to a route, trip, or operational execution record.

The operational record should identify:

- actual driver
- actual vehicle
- responsible organization
- route or trip identifier
- start date and time
- end date and time
- planned kilometres
- actual kilometres
- starting mileage when available
- ending mileage when available
- source of mileage data
- relevant operational exceptions

A vehicle assignment may provide a default suggestion.

The completed operational record must preserve the vehicle and driver that were actually used.

Later assignment changes must not rewrite historical route records.


## 15. Vehicle State and Availability

Vehicle state and vehicle availability are separate but related concepts.

Possible vehicle states include:

- active
- inactive
- under maintenance
- damaged
- unavailable
- reserved
- retired
- sold
- archived

Availability may additionally depend on:

- existing assignments
- maintenance schedule
- technical restrictions
- insurance validity
- required inspections
- organization restrictions
- operational reservations

An unavailable vehicle must not be assigned to conflicting operational work without an authorized override.


## 16. History and Audit

The system must preserve history of:

- vehicle identity changes
- registration number changes
- ownership changes
- operator changes
- custody changes
- organization assignments
- driver assignments
- route and trip usage
- mileage changes
- status changes
- availability changes
- maintenance restrictions
- administrative overrides

Each sensitive change should record:

- acting user
- organization context
- affected vehicle or driver
- performed action
- previous value
- new value
- date and time
- reason when required

Historical records must remain stable even when current relationships change.


## 17. Authorization and Visibility

Driver and vehicle access must follow:

- organization context
- explicit permissions
- permission scope
- ownership rules
- operating responsibility
- delegated authority
- data visibility rules
- record status
- valid assignments

A user may be allowed to use or view a vehicle without owning it.

A parent organization does not automatically receive access to all internal fleet data of another organization.

Cross-organization visibility must have:

- a valid business purpose
- a responsibility relationship
- an explicit permission
- an authorized scope
- an applicable visibility rule


## 18. Future Extensions and Design Rule

This model supports future integration with:

- GPS tracking
- telematics
- mobile applications
- fuel management
- maintenance management
- insurance management
- financing and leasing
- vehicle cost allocation
- damage reporting
- technical inspections
- external fleet systems
- banking and reconciliation
- environmental reporting

Final design rule:

Vehicle ownership, vehicle operation, vehicle custody, driver identity, driver assignment, organization responsibility, and actual route usage must remain separate entities or relationships.

They may be connected through controlled, time-bounded, auditable references.
