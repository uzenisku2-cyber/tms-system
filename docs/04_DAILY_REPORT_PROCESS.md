# TMS Daily Report Process v1.1

## 1. Purpose

This document defines the daily operational reporting process of the TMS platform.

The daily report provides controlled evidence of completed transport work.

The process supports:

- employee drivers
- drivers of external carrier organizations
- drivers of sub-carrier organizations
- multiple routes performed by one driver on one day
- delegated data entry
- dispatcher review
- correction requests
- controlled approval
- compensation calculation
- reconciliation with external source data
- replacement of manual Excel reporting

The process must remain consistent with:

- TMS Business Principles
- TMS Core Organization Model
- TMS User Roles and Permissions
- TMS Driver and Vehicle Model
- audit and data integrity rules


## 2. Core Process Principle

The daily report records completed operational work.

A route does not have to be assigned in TMS before the driver begins work.

Planning information may exist before the route starts, but it is optional.

The driver records the operational result after completing the route.

Planning data and actual performance data must remain separate.

A planning record must never be treated as proof that the route was completed.


## 3. Route Execution Identity

Each completed route is represented by one route execution record.

Every route execution must have:

- globally unique internal identifier
- route number
- service date
- responsible organization
- actual driver
- report status

The route number is the business identifier of the route.

The system must prevent duplicate reports for the same route execution.

At minimum, the combination of:

- route number
- service date
- responsible organization

must not identify more than one active route execution.

A correction creates a new version of the same report.

A correction must not create a duplicate route execution.


## 4. Multiple Routes Per Driver and Day

A driver may perform more than one route during the same day.

Each route has its own daily report.

Example:

Driver A may complete:

- route 101 in the morning
- route 205 in the afternoon
- route 310 in the evening

These are three separate route execution records.

Daily totals may be calculated from the individual reports.

A daily aggregate must not replace the underlying route-level records.


## 5. Planning Data

Planning data may be created or imported before the route starts.

Planning information may include:

- route number
- service date
- planned driver
- planned vehicle
- planned kilometres
- depot
- expected start time
- expected end time
- planned operational notes

Planned kilometres are known before the route begins.

Planning data may be entered by:

- authorized dispatcher
- organization administrator
- authorized external integration
- authorized driver
- imported source file

Planning data may provide defaults for the final report.

The actual result must still be recorded independently.


## 6. Actual Performance Data

Actual performance data describes what really occurred.

It may include:

- actual driver
- actual vehicle
- delivered parcel count
- redirected parcel count
- undelivered parcel count
- actual kilometres
- actual start time
- actual end time
- operational notes
- exception information
- source of actual kilometre data

Actual performance data is entered after completion of the route.

Later changes to planning data must not rewrite an existing operational result.


## 7. Performer, Author and Entry Actor

The system must distinguish between:

- performed_by_driver
- entered_by_user
- responsible_organization
- reviewed_by_user
- approved_by_user

The performed_by_driver identifies the driver who physically performed the route.

The entered_by_user identifies the user who entered the report into TMS.

Normally, the driver enters their own report.

An authorized organization owner, administrator, dispatcher, or external carrier representative may enter a report on behalf of a driver when explicitly permitted.

Delegated entry must:

- remain within the authorized organization scope
- identify the actual driver
- identify the entering user
- record that the entry was made on behalf of the driver
- preserve a complete audit history
- not transfer authorship of the performed work

The dispatcher reviewing the report must remain distinguishable from the person who entered it.


## 8. Required Report Data

A report cannot be submitted until all mandatory data is present.

Required data includes:

- route number
- service date
- responsible organization
- actual driver
- delivered_parcels
- redirected_parcels
- undelivered_parcels
- planned_km
- actual_km
- kilometre data source
- entry actor
- completion confirmation

Vehicle information is required when vehicle identification is part of the configured operational process.

Parcel counts must be non-negative whole numbers.

Kilometre values must be non-negative numeric values.

The report must identify whether the information was:

- entered by the driver
- entered on behalf of the driver
- imported from an authorized source


## 9. Parcel Result Rules

The report stores three separate parcel result values:

- delivered_parcels
- redirected_parcels
- undelivered_parcels

These values must not be silently merged or reclassified.

The system may calculate:

total_processed_parcels =
delivered_parcels +
redirected_parcels +
undelivered_parcels

Delivered, redirected, and undelivered counts may have different financial consequences.

Every correction to parcel counts must preserve:

- previous value
- new value
- acting user
- date and time
- correction reason


## 10. Kilometre Management

Every report contains:

- planned_km
- actual_km

The planned kilometres represent the expected route distance known before departure.

The actual kilometres represent the distance obtained after route completion.

The preferred actual kilometre source is the delivery application or another approved operational source.

When actual kilometres are entered manually, the source must be recorded.

The system calculates:

difference_km = actual_km - planned_km

When planned_km is greater than zero:

deviation_percentage =
absolute_value(difference_km) /
planned_km *
100

The attention rule is:

deviation_percentage > 10

A deviation exactly equal to 10 percent is not marked as above the threshold.

A deviation greater than 10 percent requires attention.

When planned_km is zero or unavailable, the percentage cannot be calculated and the report requires explicit review.

The deviation warning does not automatically reject the report.

It requires the reviewer to evaluate the difference.


## 11. Daily Report Lifecycle

The standard lifecycle is:

DRAFT

↓

SUBMITTED

↓

UNDER_REVIEW

↓

APPROVED

↓

CLOSED

A correction loop may occur:

UNDER_REVIEW

↓

CORRECTION_REQUESTED

↓

CORRECTED

↓

SUBMITTED

A report may return through the correction loop more than once.

Every status transition must be validated and audited.


## 12. Driver and Delegated Entry Responsibilities

The driver may:

- create a report after completing a route
- enter actual route results
- review entered data
- submit the report
- respond to a correction request
- correct an editable returned report
- view the report status

An explicitly authorized representative may perform permitted entry actions on behalf of the driver.

The driver or delegated entry user must not:

- change another route without permission
- approve the same report
- overwrite approved data
- conceal the identity of the actual driver
- conceal delegated entry
- create a duplicate route report

The actual driver remains attached to the route execution regardless of who entered the data.


## 13. Dispatcher Review

A dispatcher with the required permission may:

- view reports within an authorized scope
- compare planning and actual data
- review parcel counts
- review kilometre deviation
- review operational notes
- identify missing information
- request corrections
- approve valid reports
- record review notes

The dispatcher does not need to assign the route before work begins.

The primary dispatcher responsibility is verification of completed operational data.

A dispatcher must not silently replace:

- actual driver
- delivered parcel count
- redirected parcel count
- undelivered parcel count
- actual kilometres
- factual operational notes

A required change must be handled through a correction request or controlled amendment.


## 14. Correction Request

A correction request must identify:

- requesting user
- request date and time
- correction reason
- affected fields
- current report version
- responsible driver
- responsible organization

The report must return to an editable correction state.

The corrected version must preserve:

- original submitted values
- corrected values
- correcting user
- correction date and time
- correction reason
- resubmission date and time

The original history must never be deleted.


## 15. Approval, Closure and Amendments

Approval confirms that an authorized reviewer accepted the report.

Approval must record:

- approving user
- organization context
- approval date and time
- approved report version
- review notes when applicable

An approved report becomes locked against ordinary editing.

A closed report represents a completed controlled process.

Changes after approval require a controlled amendment.

An amendment must:

- reference the original report
- explain the reason
- identify the authorizing user
- preserve the previous approved version
- create a new auditable version
- trigger recalculation when financial values are affected

Approved history must not be overwritten.


## 16. Organization and Cross-Organization Rules

The responsible organization controls the operational process within its authorized scope.

An external carrier organization may allow authorized users to enter reports for its own drivers.

A carrier organization may receive visibility into operational results of a connected sub-carrier when the responsibility relationship permits it.

Cross-organization visibility may include:

- route number
- actual driver identity when authorized
- parcel results
- kilometre results
- report status
- approved operational notes
- settlement-related data

Cross-organization visibility does not automatically include:

- internal salaries
- private contracts
- unrelated financial information
- internal organization settings
- private personnel records

Visibility must follow permissions, scope, responsibility relationships, and data-sharing rules.


## 17. Audit and Data Integrity

The system must record all important report actions.

Audited events include:

- report creation
- delegated entry
- route identification
- data modification
- report submission
- review start
- correction request
- correction
- resubmission
- approval
- closure
- amendment
- administrative override

The audit history must identify:

- acting user
- actual driver
- organization context
- action
- report version
- previous value
- new value
- date and time
- reason when required
- source of imported data

The system must preserve who:

- performed the route
- entered the report
- submitted the report
- reviewed the report
- requested a correction
- corrected the report
- approved the report
- authorized a later amendment


## 18. Integrations and Final Design Rule

The daily report process supports future integration with:

- mobile driver application
- delivery application kilometre data
- GPS and telematics
- monthly depot Excel files
- OCR
- parcel source systems
- pricing and compensation
- fuel management
- financial processing
- banking and reconciliation
- notifications and rule engine
- reporting and KPI modules

Imported data must not silently overwrite approved operational records.

External source data may be used for:

- validation
- reconciliation
- correction proposals
- exception detection
- financial calculation

Final design rule:

Planning, actual execution, data entry, review, approval, financial calculation, and external reconciliation must remain separate controlled stages connected through immutable identifiers and auditable versions.
