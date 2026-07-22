# TMS Reporting and KPI Model v1.1

## 1. Purpose

This document defines the metric, KPI, target, reporting, dashboard, snapshot, aggregation, drill-down, export, data-quality, and analytical-control architecture of the TMS platform.

The model supports:

- operational reporting
- driver and route reporting
- vehicle reporting
- availability and utilization reporting
- fuel reporting
- financial reporting
- pricing and compensation reporting
- banking and reconciliation reporting
- quality indicators
- planned-versus-actual analysis
- organization-scoped dashboards
- reproducible historical reports
- scheduled reports
- controlled exports
- data-quality monitoring
- future forecasting and analytical models

The reporting architecture must remain consistent with the authoritative business modules and must not replace their source records.


## 2. Core Separation Principle

The following concepts must remain separate:

- authoritative source record
- source-record version
- reporting data contract
- metric definition
- metric-definition version
- metric computation
- metric observation
- KPI definition
- KPI-definition version
- KPI target
- threshold
- dimension
- aggregation
- report definition
- report run
- report snapshot
- dashboard definition
- dashboard widget
- filter
- drill-down
- forecast
- export
- data-quality result

A metric is a defined quantitative measurement.

A KPI is a metric interpreted against an objective, target, threshold, or decision context.

A target is not an observed result.

A dashboard is a presentation layer and must not redefine the metric formula.

A report snapshot is evidence of what was reported at a defined point in time.

An export is a disclosure artifact and not an authoritative source record.

The reporting module must not silently modify operational, financial, contractual, or audit source records.


## 3. Reporting Sources and Data Contracts

Every reporting input must originate from an identified authoritative module or controlled external source.

Reporting sources may include:

- organizations
- users and roles
- drivers
- vehicles
- route executions
- daily reports
- availability records
- reservations and assignments
- fuel transactions
- fuel imports
- price calculations
- compensation calculations
- invoices
- financial obligations
- payments
- bank transactions
- reconciliation allocations
- vehicle costs
- financing obligations
- audit events
- consent and terms evidence

A reporting data contract must identify:

- source module
- source record type
- source schema version
- authoritative fields
- record identity
- organization context
- business date
- event date and time
- recording date and time
- lifecycle status
- correction or replacement relationship
- currency and unit when applicable
- data sensitivity
- permitted reporting purposes

Reporting transformations must not change the meaning of source fields.

A source-system status must not be replaced by an invented reporting status.

Derived reporting data must remain traceable to its source records and transformation version.


## 4. Metric and KPI Definitions

A metric definition must have a stable internal identity.

A metric-definition version must identify:

- metric identifier
- version
- name
- description
- business purpose
- owner
- responsible organization
- formula
- numerator
- denominator when applicable
- aggregation method
- unit
- precision
- rounding rule
- applicable dimensions
- eligible source records
- excluded source records
- missing-data behavior
- zero-denominator behavior
- currency treatment when applicable
- valid-from date
- valid-to date when applicable
- lifecycle status
- creating actor
- approving actor
- change reason

Possible definition states include:

- DRAFT
- UNDER_REVIEW
- APPROVED
- ACTIVE
- REPLACED
- ARCHIVED

An active metric-definition version must not be edited in place.

Changing a formula, eligibility rule, unit, rounding method, or denominator requires a new definition version.

Metric name alone must not determine calculation behavior.

Undefined, unavailable, and zero are different results and must not be silently treated as equivalent.

A KPI definition must have a stable internal identity separate from a metric definition.

A KPI-definition version may identify:

- KPI identifier
- version
- name
- description
- linked metric-definition version
- business objective
- interpretation
- direction of improvement
- evaluation method
- permitted dimensions
- owner
- responsible organization
- organization scope
- valid-from date
- valid-to date when applicable
- lifecycle status
- creating actor
- approving actor when applicable
- change reason

A KPI definition interprets a metric for a defined objective and must not duplicate or silently override the metric formula.

More than one KPI definition may interpret the same metric for different objectives, scopes, or decision contexts.

A KPI target must not be embedded into the KPI definition.

Changing the linked metric-definition version, objective, interpretation, direction of improvement, or evaluation method requires a new KPI-definition version.

## 5. Dimensions, Grain and Aggregation

Every metric must define its calculation grain.

Possible grains may include:

- route execution
- driver and day
- vehicle and day
- organization and day
- organization and month
- customer and period
- contract and period
- invoice
- bank transaction
- fuel transaction
- reporting snapshot

Dimensions may include:

- organization
- organization relationship
- driver
- vehicle
- route
- customer
- supplier
- external carrier
- contract
- depot
- date
- week
- month
- economic period
- currency
- lifecycle status
- approval status
- source system

Aggregation behavior must distinguish:

- sum
- count
- distinct count
- average
- weighted average
- minimum
- maximum
- ratio of sums
- average of ratios
- latest value
- period-end value
- non-additive measure

A ratio of sums must not be replaced by an average of individual ratios unless the metric definition explicitly requires it.

Metrics that are not additive across a dimension must declare that limitation.


## 6. Time, Period and Reporting Cut-Off

The reporting model must distinguish:

- business date
- route date
- event date and time
- recording date and time
- approval date and time
- correction date and time
- booking date
- value date
- payment date
- invoice date
- economic period
- report generation time
- reporting cut-off

Timezones must be explicit for time-sensitive calculations.

A reporting period must define:

- period type
- period start
- period end
- timezone
- inclusion boundary
- reporting cut-off
- late-arriving-data rule
- correction rule

Operational metrics should use the applicable operational business date.

Financial performance may use an economic period that differs from bank payment or recording dates.

A later correction must not be assigned automatically to the correction date when the corrected business event belongs to an earlier period.


## 7. Source Eligibility, Approval and Data Quality

Each metric definition must specify which source-record states are eligible.

Source records may be:

- draft
- submitted
- verified
- approved
- rejected
- corrected
- replaced
- cancelled
- archived

Draft, submitted, approved, and corrected values must not be combined without an explicit rule.

A dashboard may show provisional data only when it is clearly identified as provisional.

Financially authoritative reporting should use the applicable approved, posted, reconciled, or otherwise controlled source states defined by the owning module.

A data-quality result may identify:

- missing source records
- incomplete fields
- invalid values
- duplicate candidates
- unresolved corrections
- inconsistent statuses
- stale data
- failed imports
- unmatched transactions
- missing approvals
- out-of-range values
- source-system delay

Data quality and business performance must remain separate.

A low data-quality score must not automatically be interpreted as poor driver or organization performance.


## 8. Operational Route and Daily-Report Metrics

Operational measurements may include:

- route executions
- completed routes
- delivered parcels
- redirected parcels
- undelivered parcels
- total processed parcels
- planned kilometers
- actual kilometers
- absolute kilometer difference
- kilometer difference percentage
- missing daily reports
- late daily reports
- report corrections
- approval duration

Total processed parcels may be defined as:

Delivered parcels plus redirected parcels plus undelivered parcels.

Delivery success rate may be defined as:

Delivered parcels divided by total processed parcels.

Redirected parcel rate may be defined as:

Redirected parcels divided by total processed parcels.

Undelivered parcel rate may be defined as:

Undelivered parcels divided by total processed parcels.

When total processed parcels equals zero, percentage metrics must follow the configured zero-denominator rule rather than silently return zero.

Kilometer difference percentage may be defined as:

Absolute value of actual kilometers minus planned kilometers, divided by planned kilometers, multiplied by 100.

A kilometer difference percentage greater than 10 percent is considered outside the accepted tolerance under the current business rule.

When planned kilometers equals zero, the system must not calculate an ordinary percentage and must return the configured undefined or review-required result.

The metric must identify whether it uses submitted, approved, or latest corrected daily-report values.


## 9. Driver, Vehicle and Resource Metrics

Driver-related measurements may include:

- completed routes
- parcel outcomes
- reporting completeness
- reporting timeliness
- correction frequency
- approved kilometer deviation
- unresolved exceptions

A driver metric must not silently infer employment status, fault, misconduct, or compensation entitlement.

Performance interpretation must remain separate from source operational facts.

Vehicle measurements may include:

- actual usage
- route kilometers
- operating days
- available days
- reserved days
- assigned days
- downtime
- maintenance cost
- fuel consumption
- financing cost
- insurance cost
- total operating cost

Vehicle ownership, operator, custody, planned assignment, reservation, availability, and actual usage must remain separate dimensions.

Vehicle utilization must define its numerator, denominator, period, and eligible states.

Availability does not prove actual usage.

Assignment does not prove that a route was completed with the assigned vehicle.


## 10. Financial, Cost and Profitability Metrics

Financial reporting must distinguish:

- revenue
- invoiced amount
- recognized income
- cash receipt
- financial obligation
- payment
- bank transaction
- compensation
- cost
- cost allocation
- reserve-fund movement
- reimbursement
- reconciliation result
- profit
- margin
- cash flow

A bank receipt is not automatically revenue.

A bank expense is not automatically an economic-period cost.

An invoice is not proof of payment.

An approved allocation connects financial evidence to an authorized financial target but does not rewrite either source record.

Profit may be defined as eligible revenue minus eligible cost for the same defined scope, period, and currency basis.

Margin percentage may be defined as profit divided by eligible revenue, multiplied by 100.

When eligible revenue equals zero, margin percentage must follow the configured zero-denominator rule.

Every financial KPI must identify:

- organization perspective
- commercial relationship
- economic period
- included source states
- currency
- exchange-rate source
- exchange-rate date
- rounding method
- tax treatment
- treatment of corrections and reversals

Cash-flow reporting and profitability reporting must remain separate.


## 11. Metric Computation, Observations and Lineage

A metric computation represents one controlled calculation execution.

A computation must identify:

- computation identifier
- metric-definition version
- source-data cut-off
- period
- organization scope
- dimensions
- filter set
- source-query or transformation version
- execution start and completion time
- executing service
- result status
- source-record count
- excluded-record count
- data-quality result
- error information when applicable

A metric observation represents the result for one defined dimensional combination.

An observation may identify:

- metric definition and version
- dimension values
- measured value
- unit
- precision
- currency when applicable
- numerator
- denominator
- source-record references or lineage reference
- computation
- provisional status
- validity status
- creation date and time

The same source data and same metric-definition version should produce the same result, subject to declared deterministic dependencies.

Metric lineage must support explanation of how a value was calculated.


## 12. Historical Reproducibility, Corrections and Restatement

Historical reporting must distinguish:

- live recalculated view
- as-of view
- frozen report snapshot
- restated historical view

A report snapshot must identify:

- report definition and version
- metric-definition versions
- source-data cut-off
- included period
- organization scope
- filters
- generation date and time
- creating user or service
- data freshness
- source lineage
- snapshot status
- superseded-snapshot reference when applicable

A frozen snapshot must not silently change when:

- source data is corrected
- a metric formula changes
- an exchange rate changes
- an organization relationship changes
- a new source record arrives
- a reporting transformation changes

A corrected or restated report must create a new snapshot or clearly versioned result.

The original snapshot and reason for restatement must remain traceable.

A live dashboard may display current recalculated values but must not be presented as an unchanged historical snapshot.


## 13. KPI Targets, Thresholds and Evaluation

A KPI target must remain separate from the KPI definition and observed value.

A target may identify:

- KPI-definition version
- target owner
- organization scope
- applicable population
- target value
- unit
- comparison operator
- warning threshold
- critical threshold
- valid-from date
- valid-to date
- evaluation frequency
- evaluation rule
- approval
- lifecycle status

Possible evaluation results include:

- NOT_EVALUATED
- ON_TARGET
- WARNING
- OUTSIDE_TARGET
- CRITICAL
- NOT_APPLICABLE
- INSUFFICIENT_DATA

A threshold breach is an evaluation result, not a modification of the measured value.

Changing a target must not rewrite historical observations or earlier evaluations.

Targets belonging to one organization must not be imposed on another organization without an applicable relationship and authority.


## 14. Reports, Dashboards, Filters and Drill-Down

A report definition may identify:

- report identifier
- owner
- organization scope
- version
- purpose
- metrics
- dimensions
- default filters
- permitted filters
- sorting
- grouping
- presentation format
- access policy
- schedule configuration
- lifecycle status

A dashboard definition may identify:

- dashboard owner
- organization scope
- widgets
- layout
- filters
- refresh behavior
- access policy

User dashboard customization may change presentation but must not change authoritative metric definitions.

A filter must reduce or reshape data within the user's authorized scope.

A filter must not expand permissions or expose data outside the authorized scope.

Drill-down may expose supporting records only when the user has permission to access those records.

Drill-down must be read-only with respect to authoritative source records unless the user explicitly enters the owning business module through an authorized workflow.


## 15. Organization Scope, Privacy and Fair Use

Reporting access must follow:

- organization relationships
- explicit permissions
- commercial relationships
- operational responsibility
- data-sharing rules
- contractual requirements
- legal requirements
- minimum necessary access

Organizational hierarchy alone does not create reporting access.

A higher-level organization may receive only the operational or settlement information required for an applicable relationship.

Internal economics of another organization must remain private unless explicit authority permits access.

Driver-level reporting may contain personal or employment-related information and must follow data-minimization and purpose-limitation rules.

Comparative rankings must not expose unnecessary personal information.

A KPI result must not automatically trigger compensation, deduction, discipline, contractual penalty, or access restriction unless the owning business process independently authorizes that action.


## 16. Refresh, Cache, Performance and Availability

Report freshness must be visible.

A report or dashboard may identify:

- source-data cut-off
- last successful refresh
- refresh frequency
- expected delay
- cache status
- provisional status
- failed-source status
- next scheduled refresh

A cached result must retain the metric-definition version, filters, organization scope, and source-data cut-off used to create it.

Cache invalidation must occur when applicable source data, permissions, definitions, or scope change.

A stale report must not be presented as current without a freshness indicator.

A failed refresh must not silently display an older result as newly calculated.

Reporting performance optimizations must not weaken organization isolation, permission checks, or calculation correctness.


## 17. Export, Sharing, Audit and Operational Control

Exports may include:

- CSV
- spreadsheet
- PDF
- scheduled email attachment
- controlled external delivery
- API response
- another approved format

An export must identify:

- report definition and version
- report snapshot or computation
- exporting actor or service
- organization scope
- filters
- period
- generation date and time
- source-data cut-off
- format
- record count
- destination or delivery method when known
- sensitivity classification
- reason when required
- expiration when applicable

Export permissions must be evaluated independently from report-view permission when required.

A shared report must not expose drill-down records or dimensions beyond the recipient's authorized scope.

Sensitive reporting actions must be audited, including:

- metric-definition change
- KPI-target change
- report-definition change
- dashboard sharing
- scheduled-report creation
- permission change
- report snapshot creation
- restatement
- export
- external delivery
- exceptional manual correction
- data-quality override

Notifications and threshold alerts must be created through the Notification and Rule Engine.

An alert does not change the source metric observation or source business record.


## 18. Integrations and Final Design Rule

The reporting and KPI model supports integration with:

- organizations
- users and permissions
- drivers
- vehicles
- route executions
- daily reports
- availability and resource planning
- fuel management
- pricing and compensation
- vehicle assets and financing
- banking and reconciliation
- notifications and rule engine
- audit and data integrity
- consent and terms management
- document storage
- future analytical platforms
- future forecasting services

Forecast, target, planned value, actual source value, metric observation, and KPI evaluation must remain separate.

Predictive or AI-generated output must be clearly identified as derived output and must not replace verified source facts.

Final design rule:

Authoritative source record, reporting data contract, metric definition, metric-definition version, computation, observation, KPI definition, KPI-definition version, target, threshold evaluation, report definition, report run, report snapshot, dashboard, filter, drill-down, export, and data-quality result must remain separate controlled and auditable records.
