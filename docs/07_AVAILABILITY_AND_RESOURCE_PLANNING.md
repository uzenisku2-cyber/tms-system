# TMS Availability and Resource Planning v1.1

## 1. Purpose

This document defines availability, operational eligibility, capacity, reservation, and resource planning rules of the TMS platform.

The model supports:

- employee drivers
- external carrier drivers
- sub-carrier drivers
- organization owners who also drive
- driver shifts
- vacation and sickness
- temporary restrictions
- vehicle availability
- vehicle maintenance and damage restrictions
- depots and operating areas
- organization capacity
- tentative planning
- confirmed reservations
- multiple routes per driver and day
- conflict detection
- controlled overrides
- customer capacity communication
- future automatic planning

The model must remain consistent with:

- TMS Business Principles
- TMS Core Organization Model
- TMS User Roles and Permissions
- TMS Driver and Vehicle Model
- TMS Daily Report Process
- audit and data integrity rules


## 2. Core Separation Principle

The following concepts must remain separate:

- resource identity
- availability
- operational eligibility
- capacity
- reservation
- assignment
- planned usage
- actual usage
- organization responsibility
- permission to manage the resource

Availability answers whether a resource is expected to be free during a defined period.

Operational eligibility answers whether the resource is permitted and capable of performing the work.

A reservation blocks or proposes use of a resource.

An assignment connects a resource to planned work.

Actual usage records what really occurred.

Availability and operational eligibility are different concepts.

Planning does not prove actual execution.


## 3. Resource Types

Availability and planning may apply to:

- driver
- vehicle
- organization
- depot
- operational team
- vehicle category
- driver category
- route capacity
- temporary shared resource
- externally provided resource

Each resource must have:

- stable internal identifier
- resource type
- responsible organization
- lifecycle status
- applicable visibility scope
- availability history
- planning history

A resource may participate in several relationships without losing its independent identity.


## 4. Availability Records

Availability must be represented through time-bounded records.

An availability record must define:

- resource
- availability type
- valid-from date and time
- valid-to date and time
- timezone
- source
- creating user or service
- responsible organization
- status
- reason when applicable
- recurrence reference when applicable
- audit information

Possible availability types include:

- AVAILABLE
- UNAVAILABLE
- LIMITED
- UNKNOWN
- ON_CALL
- TEMPORARILY_BLOCKED

Reservation state must be represented by a separate reservation record and must not be stored as an availability type.

Missing availability must not be interpreted as available by default.

The applicable organization may explicitly configure how UNKNOWN availability is handled.


## 5. Driver Availability

Driver availability represents whether a driver is expected to be free for operational work.

Driver availability reasons may include:

- scheduled shift
- vacation
- sickness
- personal absence
- training
- administrative work
- legal rest period
- temporary operational restriction
- other approved limitation

A driver may have:

- recurring working patterns
- individual availability exceptions
- full-day availability
- partial-day availability
- several available periods during one day
- several unavailable periods during one day

Availability must support date and time precision.

A full-day status must not prevent the system from later supporting precise shift intervals.


## 6. Driver Eligibility

Driver operational eligibility must be evaluated independently from availability.

Eligibility may depend on:

- active user account
- active driver profile
- active organization relationship
- valid driving licence
- required licence category
- accepted terms
- required qualifications
- absence of suspension
- absence of a blocking restriction
- legal rest requirements
- organization-specific rules

A driver may be available but not eligible.

Example:

A driver may have no planned work but may have an expired driving licence.

A driver may also be eligible but unavailable because of vacation or sickness.

Both availability and eligibility must pass before ordinary assignment is confirmed.


## 7. Vehicle Availability

Vehicle availability represents whether a vehicle is expected to be free during a defined period.

Vehicle availability may be affected by:

- existing reservation
- planned route
- maintenance
- repair
- inspection
- damage
- cleaning
- depot transfer
- temporary loan
- administrative block
- fuel or charging requirement
- other operational restriction

Vehicle availability does not identify the actual driver.

A vehicle may be available without having an assigned driver.

A vehicle may also have a default driver assignment while being unavailable for operational work.


## 8. Vehicle Eligibility and Usability

Vehicle operational eligibility must be evaluated separately from availability.

Eligibility may depend on:

- active vehicle status
- required technical inspection
- insurance validity
- registration validity
- required permits
- correct vehicle category
- suitable capacity
- compatible fuel or charging status
- absence of blocking maintenance
- absence of critical damage
- organization-specific restrictions

A vehicle may be available but not operationally eligible.

A vehicle may be eligible but already reserved.

Availability, eligibility, and reservation must all be evaluated before ordinary confirmation.


## 9. Organization Capacity

Organization capacity represents the amount of operational work an organization can reasonably provide during a defined period.

Capacity may be calculated from:

- available eligible drivers
- available eligible vehicles
- driver categories
- vehicle categories
- depot capacity
- shift coverage
- contractual commitments
- reserved resources
- expected absences
- maintenance plans
- operational buffers

Capacity may be represented as:

- available driver count
- available vehicle count
- route count
- parcel capacity
- time capacity
- geographic capacity
- service-category capacity

Organization capacity is derived information.

It must not replace the underlying driver, vehicle, availability, and reservation records.


## 10. Reservations and Assignments

A reservation temporarily blocks or proposes use of a resource.

An assignment connects a selected resource with planned operational work.

A reservation or assignment must define:

- resource
- planned work reference
- responsible organization
- reservation or assignment type
- valid-from date and time
- valid-to date and time
- status
- priority
- creating user
- confirming user when applicable
- reason
- override reference when applicable

Possible reservation states include:

- TENTATIVE
- HELD
- CONFIRMED
- RELEASED
- CANCELLED
- EXPIRED

A tentative reservation must be distinguishable from a confirmed assignment.

Reservations must not rewrite actual route usage.


## 11. Conflict and Overlap Detection

The system must detect conflicting time intervals.

Possible conflicts include:

- one driver reserved for overlapping routes
- one vehicle reserved for overlapping routes
- reservation during driver absence
- reservation during vehicle maintenance
- assignment outside an eligible period
- conflicting depot location
- insufficient travel time between routes
- incompatible vehicle category
- incompatible driver qualification
- organization capacity exceeded

Conflict detection must consider:

- interval start
- interval end
- preparation time
- expected travel time
- return time
- configured operational buffer
- resource location
- reservation priority

A time overlap is not the only possible planning conflict.

Location and transition feasibility must also be considered.


## 12. Planning Lifecycle

A planning record may use lifecycle states such as:

- DRAFT
- TENTATIVE
- RESERVED
- CONFIRMED
- IN_PROGRESS
- COMPLETED
- CANCELLED
- EXPIRED

Planning may be created by:

- dispatcher
- organization administrator
- authorized driver
- authorized external carrier representative
- automated planning service
- external integration

TMS does not require a route to be assigned before the driver starts work.

Planning is therefore optional for recording a completed daily report.

When planning exists, it may provide default values but must not replace actual operational data.


## 13. Multiple Routes and Time Windows

A driver may perform more than one route during the same day.

A vehicle may also be used for more than one route during the same day.

Multiple assignments are valid when:

- time windows do not conflict
- operational buffers are satisfied
- location transitions are feasible
- driver eligibility remains valid
- vehicle eligibility remains valid
- legal and organization rules are satisfied

Each route must retain its own:

- planned interval
- actual interval
- planned driver
- actual driver
- planned vehicle
- actual vehicle
- reservation status
- route execution identifier

Daily capacity may be aggregated from route-level records.

The daily aggregate must not replace individual route planning and execution records.


## 14. Missing Availability and Planning Assumptions

Missing data must be handled through explicit rules.

Possible organization policies include:

- block confirmation
- allow tentative planning only
- require manual review
- treat the resource as UNKNOWN
- notify responsible users
- request availability completion
- allow an authorized override

The system must not silently convert missing data to AVAILABLE.

Planning assumptions must record:

- assumption type
- acting user or service
- organization context
- affected resource
- affected period
- reason
- expiration
- review status

Assumptions must remain visible until confirmed, replaced, or expired.


## 15. Overrides and Exceptions

An authorized user may override selected availability or conflict rules.

An override must define:

- affected resource
- affected planning record
- detected conflict
- authorizing user
- organization context
- override date and time
- reason
- validity period
- risk or warning acknowledgement
- resulting status

An override does not delete the original conflict.

The system must preserve:

- original availability
- original eligibility result
- original conflict
- override decision
- acting user
- justification
- resulting assignment

Certain legal or technical restrictions may be configured as non-overridable.


## 16. Cross-Organization Planning and Visibility

A resource remains associated with its responsible organization.

Cross-organization planning may occur when supported by:

- commercial relationship
- responsibility relationship
- delegated authority
- explicit resource-sharing agreement
- permission scope
- data-sharing rule

A carrier may receive limited availability information from a connected external carrier or sub-carrier.

Shared information may include:

- capacity count
- available period
- driver category
- vehicle category
- reservation status
- confirmed commitment

Shared information does not automatically include:

- internal employee details
- internal schedules unrelated to the agreement
- private absence reasons
- unrelated vehicle information
- internal compensation
- unrelated customer commitments

An organization does not automatically receive visibility into another organization's internal availability.


## 17. Notifications, Communication and Audit

Availability and planning events may trigger notifications for:

- missing availability
- expiring availability
- driver absence
- vehicle maintenance
- newly detected conflict
- changed reservation
- released capacity
- planning confirmation required
- failed customer communication
- authorized override

Customer availability communication may report:

- agreed capacity period
- committed route capacity
- resource-category availability
- unknown or incomplete capacity
- confirmed changes

External communication must use an approved snapshot of the shared availability data.

Communication history must record:

- sender
- recipient
- organization context
- reporting period
- data snapshot
- communication channel
- send date and time
- delivery result
- failure reason

Sensitive planning actions must be audited, including:

- availability creation
- availability modification
- reservation
- assignment
- conflict detection
- confirmation
- cancellation
- override
- resource replacement
- external communication


## 18. Integrations and Final Design Rule

Availability and resource planning supports integration with:

- drivers
- vehicles
- organizations
- depots
- daily reports
- route executions
- maintenance
- qualifications
- legal rest rules
- calendar systems
- notifications and rule engine
- customer communication
- reporting and KPI modules
- future automatic planning
- future route optimization

Planning changes must not silently overwrite actual route execution records.

Final design rule:

Availability, eligibility, reservation, assignment, capacity, and actual usage must remain separate controlled records connected through time-bounded and auditable relationships.
