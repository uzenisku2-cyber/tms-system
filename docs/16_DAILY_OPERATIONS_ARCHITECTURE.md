# TMS Daily Operations Architecture v1.0

## 1. Purpose

This document defines the backend architecture for the Daily Operations
Foundation of the TMS platform.

The module records completed route-level operational work and replaces manual
daily Excel reporting with controlled, organization-scoped and auditable data.

The architecture is consistent with:

- TMS Business Principles
- TMS Core Organization Model
- TMS User Roles and Permissions
- TMS Driver and Vehicle Model
- TMS Daily Report Process
- TMS Audit and Data Integrity Model
- TMS Identity and Organization Architecture

## 2. Architectural Decision

Daily operations are implemented as a separate module:

`App\Modules\DailyReports`

A daily report is not an extension of the existing `Trip` aggregate.

The existing `Trip` aggregate represents planned or monitored transport
activity, including assignment, route progression, tracking and completion.

A `DailyReport` represents controlled evidence of work that was actually
performed and reported after completion of a route.

A route does not have to exist as a `Trip` before a daily report is created.

A daily report may optionally reference an existing trip through `trip_id`.

The optional reference must never make daily reporting dependent on route
planning or tracking.

## 3. Core Domain Invariants

One daily report represents one completed route execution.

Each report must identify:

- responsible organization
- service date
- route number
- actual driver
- user who entered the report
- parcel results
- planned kilometres
- actual kilometres
- kilometre data source
- current workflow status

One driver may have multiple reports on the same service date.

Each route execution remains a separate report.

Corrections create new versions of the same report.

Corrections must not create duplicate route executions.

Approved values must never be silently overwritten.

A report cannot be physically deleted through the ordinary application
workflow.

## 4. Aggregate Boundary

The aggregate root is `DailyReport`.

The aggregate owns:

- current operational values
- current workflow status
- current version number
- report versions
- workflow and audit events

The aggregate does not own:

- driver identity
- organization identity
- vehicle identity
- trip planning
- GPS locations
- proof-of-delivery records
- pricing rules
- compensation calculations
- fuel transactions

Those concepts remain references to their existing or future modules.

## 5. Actors and Identity

The architecture distinguishes the following identities:

`performed_by_driver_id`

Identifies the driver who physically performed the route.

`entered_by_user_id`

Identifies the authenticated user who entered the report.

`reviewed_by_user_id`

Identifies the user who most recently performed formal review.

`approved_by_user_id`

Identifies the user who approved the report.

The person entering a report may differ from the actual driver.

Delegated entry is allowed only when the acting user has the required
organization-scoped permission.

Delegated entry must remain visible in the report and audit history.

The actual driver must never be replaced by the delegated entry actor.

A user who entered a report must not approve the same report unless a later
explicit business rule authorizes that exception.

## 6. Organization Scope

Every daily report belongs to exactly one responsible organization.

`organization_id` is mandatory and is obtained from the verified
`OrganizationContext`.

Controllers and application services must use:

`OrganizationContext::requireId()`

Client-provided organization identifiers must not be trusted as the source of
the report scope.

Every read and write query must be constrained by the verified organization
context.

For Sprint 003, report entry and review are restricted to the responsible
organization.

Cross-organization visibility and parent-carrier review require explicit
organization relationships, permissions and data-sharing rules and are
deferred until those rules are implemented.

## 7. Database Model

### 7.1 daily_reports

The `daily_reports` table stores the current state of the aggregate.

Required identity fields:

- `id`
- `public_id`
- `organization_id`
- `performed_by_driver_id`
- `entered_by_user_id`
- `route_number`
- `service_date`

Optional references:

- `trip_id`
- `vehicle_id`

Entry metadata:

- `entry_method`
- `entered_on_behalf`
- `completion_confirmed_at`

Operational result fields:

- `delivered_parcels`
- `redirected_parcels`
- `undelivered_parcels`
- `planned_km`
- `actual_km`
- `actual_km_source`
- `operational_notes`

Workflow fields:

- `status`
- `current_version`
- `submitted_at`
- `review_started_at`
- `reviewed_by_user_id`
- `approved_at`
- `approved_by_user_id`
- `closed_at`

System fields:

- `created_at`
- `updated_at`

The internal primary key remains a database-generated bigint.

`public_id` is a unique UUID intended for external API references.

`trip_id` is nullable.

`vehicle_id` is nullable unless the responsible organization configures
vehicle identification as mandatory.

### 7.2 daily_report_versions

The `daily_report_versions` table stores immutable report versions.

Required fields:

- `id`
- `daily_report_id`
- `version_number`
- `snapshot`
- `changed_fields`
- `created_by_user_id`
- `change_reason`
- `created_at`

`snapshot` stores the complete controlled report payload for the version.

`changed_fields` stores the names of fields changed from the previous version.

The combination of `daily_report_id` and `version_number` is unique.

Existing version rows must never be updated or deleted through ordinary
application operations.

### 7.3 daily_report_events

The `daily_report_events` table stores immutable workflow and audit events.

Required fields:

- `id`
- `daily_report_id`
- `organization_id`
- `event_type`
- `from_status`
- `to_status`
- `acted_by_user_id`
- `reason`
- `affected_fields`
- `metadata`
- `created_at`

Events include:

- report created
- delegated entry recorded
- report updated
- report submitted
- review started
- correction requested
- report corrected
- report resubmitted
- report approved
- report closed

Event rows must never be updated or deleted through ordinary application
operations.

## 8. Route Execution Uniqueness

The system must prevent duplicate reports for the same route execution.

The unique business key is:

`organization_id + service_date + normalized route_number`

Route numbers are trimmed before persistence.

Uniqueness is case-insensitive.

Because corrections remain versions of the same report and reports are not
physically deleted, the unique rule applies to the complete report table.

The database constraint is the final protection against concurrent duplicate
creation.

Application validation must provide a readable conflict response before the
database constraint is reached whenever possible.

## 9. Operational Values

Parcel values are stored separately:

- `delivered_parcels`
- `redirected_parcels`
- `undelivered_parcels`

Each value must be a non-negative whole number.

The calculated total is:

`total_processed_parcels = delivered_parcels + redirected_parcels + undelivered_parcels`

The total is derived and is not manually entered.

Kilometre values use non-negative decimal values.

`planned_km` represents the expected distance known before departure.

`actual_km` represents the result available after route completion.

`actual_km_source` identifies the source of the actual value.

Supported initial sources are:

- delivery application
- manual entry
- authorized import
- other approved source

## 10. Kilometre Deviation

The module calculates:

`difference_km = actual_km - planned_km`

When `planned_km` is greater than zero:

`deviation_percentage = abs(difference_km) / planned_km * 100`

A report requires kilometre attention when:

`deviation_percentage > 10`

A deviation equal to exactly 10 percent is not above the threshold.

A deviation warning does not automatically reject the report.

It requires explicit dispatcher review.

When `planned_km` is zero, the percentage is not calculated and the report
requires explicit review.

Calculated values are initially exposed by domain methods and API resources
rather than stored as editable database columns.

## 11. Workflow

The supported statuses are:

- `draft`
- `submitted`
- `under_review`
- `correction_requested`
- `corrected`
- `approved`
- `closed`

Allowed standard transitions are:

`draft -> submitted`

`submitted -> under_review`

`under_review -> approved`

`approved -> closed`

Allowed correction transitions are:

`under_review -> correction_requested`

`correction_requested -> corrected`

`corrected -> submitted`

A report may pass through the correction loop more than once.

The `corrected -> submitted` transition is performed by the dedicated
`resubmitCorrected` application action. It creates a transition event without
creating another data version because `recordCorrection` already persisted the
corrected operational values.

Resubmission may be performed by the actual driver or by the original delegated
entry actor while that actor still holds `daily-reports.enter-for-driver` in the
verified organization.

Every transition must be performed by a dedicated application action.

Direct arbitrary status updates are forbidden.

Every transition creates a `daily_report_event`.

## 12. Editing and Locking Rules

A draft may be edited by its authorized entry actor.

A correction-requested report may be edited by an authorized driver or
delegated entry actor.

A submitted report is not editable while waiting for review.

An under-review report is not editable by the driver.

An approved or closed report is locked against ordinary editing.

Any future post-approval amendment must create a new controlled version and is
outside the initial Sprint 003 implementation.

## 13. Versioning Rules

Version 1 is created when the first controlled report state is persisted.

A new version is created whenever controlled operational values change.

Status-only transitions create events but do not require a new data version
unless operational values also change.

Each submitted, approved and closed state references the current report
version.

Previous versions remain immutable.

The current aggregate row may contain the latest values for efficient reads,
but its complete history is preserved in `daily_report_versions`.

## 14. Permissions

Authorization is permission-based and organization-scoped.

Initial permissions are:

- `daily-reports.view`
- `daily-reports.create`
- `daily-reports.update`
- `daily-reports.submit`
- `daily-reports.enter-for-driver`
- `daily-reports.review`
- `daily-reports.request-correction`
- `daily-reports.approve`
- `daily-reports.close`

Drivers may create, update and submit their own reports.

Delegated entry requires `daily-reports.enter-for-driver`.

Dispatcher actions require review permissions.

Approval requires `daily-reports.approve`.

Role names must not replace policy and permission checks inside the module.

## 15. API Boundary

The HTTP API boundary was designed in Sprint 003 and is delivered
through controlled follow-up units.

The API contract currently contains `DailyReportIndexRequest`,
`StoreDailyReportRequest`, `UpdateDailyReportRequest`,
`DailyReportTransitionRequest`,
`RecordDailyReportCorrectionRequest`, `DailyReportResource`, `DailyReportVersionResource` and `DailyReportEventResource`.
These classes define validated input and resource contracts for registered endpoints.

Request classes validate client-supplied filters, operational values,
optimistic-lock versions and optional reasons. Organization context,
authenticated actor identities, workflow status and audit identities
remain server-controlled.

The resource exposes stored report values and domain-calculated parcel
and kilometre results without adding editable calculated columns.

Version and event resources expose immutable snapshots and audit metadata
without exposing internal aggregate or organization identifiers.

The read-only `GET /daily-reports` and
`GET /daily-reports/{dailyReport}` endpoints are registered.
They require authentication, verified organization context and
the organization-scoped `daily-reports.view` permission.
Create, update, submit, review, correction, resubmission, approval and closure endpoints are registered.

The registered base path is:

`/api/v1/daily-reports`

The API delivery plan contains the following endpoints:

- `GET /daily-reports`
- `POST /daily-reports`
- `GET /daily-reports/{dailyReport}`
- `PATCH /daily-reports/{dailyReport}`
- `POST /daily-reports/{dailyReport}/submit`
- `POST /daily-reports/{dailyReport}/review`
- `POST /daily-reports/{dailyReport}/request-correction`
- `POST /daily-reports/{dailyReport}/correct`
- `POST /daily-reports/{dailyReport}/resubmit`
- `POST /daily-reports/{dailyReport}/approve`
- `POST /daily-reports/{dailyReport}/close`
- `GET /daily-reports/{dailyReport}/versions`
- `GET /daily-reports/{dailyReport}/events`

Read operations use `DailyReportQueryService`.
Write and workflow operations use `DailyReportWriteService` as the HTTP
application facade over `DailyReportPersistenceService`.
Actor-sensitive permissions are resolved inside that facade so direct and
delegated entry do not require an incorrect combination of permissions.
Version and event history endpoints are registered and require the organization-scoped `daily-reports.view` permission.

## 16. Application Services

The initial module contains focused application actions or services for:

- creating a report
- updating editable report data
- submitting a report
- starting review
- requesting correction
- recording a correction
- approving a report
- closing a report
- calculating parcel totals
- calculating kilometre deviation
- recording immutable versions
- recording immutable events

Database mutations that affect the aggregate, version and event records must
run in one transaction.

Workflow services must lock the report row during transitions that could be
executed concurrently.

## 17. Existing Trip Integration

A daily report may reference a trip when the trip already exists.

The relation is optional.

Linking a report to a trip may provide:

- planned driver
- planned vehicle
- scheduled time
- origin and destination
- calculated GPS distance
- completion status

Imported or linked trip data may provide defaults.

It must not silently overwrite report values after the report has been
submitted.

The daily report lifecycle remains independent from the trip lifecycle.

Finishing a trip does not automatically approve or close a daily report.

## 18. Validation Rules

Creation requires an authenticated active user and verified organization
context.

The selected driver must have an active driver profile.

The acting user must have access to the responsible organization.

Route number must be non-empty after trimming.

Service date must be a valid date.

Parcel counts must be non-negative integers.

Kilometre values must be non-negative numbers.

Actual kilometre source is mandatory when actual kilometres are supplied.

The current HTTP creation request prohibits `trip_id` and `vehicle_id`
until organization-scoped link validation is delivered.

Submission requires all mandatory operational values and completion
confirmation.

Review, correction, approval and closure require valid current status and the
corresponding permission.

Every referenced driver, vehicle and trip must be visible within the allowed
organization scope.

## 19. Testing Strategy

Sprint 003 must include tests for:

- report creation in verified organization context
- rejection without organization context
- rejection of foreign organization data
- route execution uniqueness
- multiple routes for one driver on one day
- self-entry
- delegated entry
- non-negative parcel validation
- non-negative kilometre validation
- parcel total calculation
- kilometre difference calculation
- deviation below 10 percent
- deviation equal to 10 percent
- deviation above 10 percent
- zero planned kilometres
- allowed status transitions
- forbidden status transitions
- correction loop
- immutable versions
- immutable events
- approved report locking
- optional trip relation
- driver access isolation
- dispatcher review authorization
- approval authorization

Service-level tests must verify application outcomes and database state.

API response tests cover authentication, organization isolation, permissions and the complete write workflow.

## 20. Sprint 003 Scope

Sprint 003 includes:

- architecture documentation
- database migrations
- DailyReports domain and persistence module
- models and relationships
- calculations and value rules
- controlled workflow actions
- immutable versions and audit events
- organization-scoped permission enforcement
- direct and delegated report entry
- application-service and HTTP API feature and unit tests
- static analysis, formatting and CI validation

## 21. Deferred Scope

Sprint 003 does not include:

- advanced history filtering, pagination and export
- pricing and compensation
- partner-specific price lists
- monthly depot Excel import
- ORLEN or MOL fuel data
- invoicing
- banking reconciliation
- OCR
- telematics integration
- automatic GPS reconciliation
- frontend screens
- post-approval amendments
- full cross-organization report sharing

These capabilities must build on the immutable identifiers, versions and
events established by this module.

## 22. Implementation Order

The Sprint 003 foundation was implemented in controlled units:

1. architecture documentation
2. database schema
3. models and relationships
4. calculations and value rules
5. workflow and persistence actions
6. organization-scoped authorization
7. direct and delegated entry behavior
8. service-level feature and unit tests
9. static analysis and formatting
10. Sprint documentation and final validation

The HTTP API boundary is delivered through controlled follow-up units after the Sprint 003 foundation.

## 23. Acceptance Criteria

The Sprint 003 foundation is complete when:

- reports are isolated by verified organization context
- one driver can report multiple routes per day
- duplicate route executions are prevented
- parcel results remain separate
- planned and actual kilometres remain separate
- deviations above 10 percent are identified
- delegated entry preserves both actor identities
- workflow transitions are controlled
- corrections create immutable versions
- important actions create immutable events
- approved reports cannot be ordinarily edited
- all module tests and repository CI checks pass
- no existing Trip or Identity behavior is unintentionally changed
