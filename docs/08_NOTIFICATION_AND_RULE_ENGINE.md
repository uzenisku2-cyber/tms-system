# TMS Notification and Rule Engine v1.1

## 1. Purpose

This document defines the event, rule evaluation, automated action, notification, delivery, escalation, and acknowledgement architecture of the TMS platform.

The model supports:

- internal notifications
- email notifications
- mobile application notifications
- future SMS notifications
- future API callbacks
- operational reminders
- compliance warnings
- workflow review requests
- controlled task creation
- delivery retries
- escalation
- acknowledgement
- organization-specific rules
- platform rules
- user notification preferences
- multilingual templates
- deduplication
- complete audit history

The model must remain consistent with:

- TMS Business Principles
- TMS Core Organization Model
- TMS User Roles and Permissions
- TMS Driver and Vehicle Model
- TMS Daily Report Process
- TMS Fuel Management
- TMS Availability and Resource Planning
- TMS Pricing and Compensation Model
- audit and data integrity rules


## 2. Core Separation Principle

The following concepts must remain separate:

- source business record
- domain event
- rule definition
- rule version
- rule evaluation
- action request
- action execution
- recipient resolution
- notification instance
- rendered message
- delivery attempt
- acknowledgement
- escalation
- user preference

An event records that something occurred.

A rule determines whether configured conditions are satisfied.

An action requests a controlled outcome.

A notification informs a recipient.

A delivery attempt sends a rendered message through one channel.

A notification must not directly modify the source business record.

An automated action must be executed through the responsible source module and must not bypass its permissions, validation, lifecycle, or audit rules.


## 3. Domain Events

Notifications and automated rules are evaluated from domain events.

A domain event must identify:

- event identifier
- event type
- event schema version
- source module
- source aggregate type
- source aggregate identifier
- organization context
- responsible organization when applicable
- acting user or service
- occurrence date and time
- recording date and time
- timezone
- correlation identifier
- causation identifier when applicable
- immutable event payload snapshot

Examples include:

- daily_report_missing
- daily_report_submitted
- daily_report_rejected
- kilometre_difference_detected
- vehicle_maintenance_due
- vehicle_restriction_created
- fuel_card_expiring
- fuel_transaction_anomaly_detected
- driver_qualification_expiring
- availability_missing
- planning_conflict_detected
- pricing_calculation_disputed
- payment_reconciliation_failed

Events must remain immutable after publication.

Corrections must create a new event rather than rewriting the original event.

Event payloads must not contain unnecessary sensitive information.


## 4. Rule Definitions and Versioning

A rule definition represents a stable rule identity.

A rule version contains the executable configuration applicable during a defined period.

A rule version must identify:

- rule identifier
- version number
- rule name
- description
- owner
- organization scope
- event types
- conditions
- actions
- severity
- priority
- deduplication strategy
- escalation policy
- template references
- valid-from date and time
- valid-to date and time when applicable
- lifecycle status
- creating user
- approving user
- approval date and time
- change reason

Rule version states may include:

- DRAFT
- UNDER_REVIEW
- APPROVED
- ACTIVE
- PAUSED
- REPLACED
- ARCHIVED

An active rule version must not be edited in place.

Changing an active rule requires a new version.

Historical evaluations must retain the exact rule version that was used.


## 5. Rule Ownership, Scope and Authorization

Rules may be owned by:

- TMS platform
- organization
- commercial relationship
- delegated operational authority

Rule ownership must remain separate from source resource ownership.

Rule ownership does not grant access to source data.

A rule may operate only within its authorized:

- organization scope
- permission scope
- resource scope
- commercial relationship
- data-sharing scope
- action scope

Rule creation, approval, activation, pausing, and replacement require explicit permissions.

A higher-level organization does not automatically receive authority to define rules for another organization's internal processes.

Cross-organization rules require an explicit relationship, delegation, or data-sharing agreement.


## 6. Trigger and Condition Evaluation

An event may trigger evaluation of one or more active rule versions.

A rule evaluation must record:

- evaluation identifier
- event identifier
- rule identifier
- rule version
- organization context
- evaluation date and time
- condition input snapshot
- evaluated conditions
- condition results
- final result
- selected actions
- skipped actions
- failure reason when applicable
- evaluation service version

Possible evaluation results include:

- MATCHED
- NOT_MATCHED
- SKIPPED
- FAILED
- DUPLICATE
- OUT_OF_SCOPE

Rule evaluation must use the rule version and context applicable at evaluation time.

Rule evaluation must be deterministic when the event, context snapshot, and rule version are unchanged.

A failed evaluation must remain visible and must not be treated as NOT_MATCHED.


## 7. Automated Actions and Side-Effect Safety

A matched rule may create one or more controlled action requests.

Supported action types may include:

- CREATE_NOTIFICATION
- CREATE_OPERATIONAL_TASK
- REQUEST_REVIEW
- REQUEST_APPROVAL
- REQUEST_SOURCE_MODULE_TRANSITION
- CREATE_ESCALATION
- START_EXTERNAL_CALLBACK
- RECORD_COMPLIANCE_WARNING

An action request must define:

- action identifier
- rule evaluation
- action type
- target module
- target object
- organization context
- requested parameters
- idempotency key
- status
- creation date and time
- execution deadline when applicable

Action execution must be separate from action request creation.

The target module must validate:

- acting authority
- organization scope
- target lifecycle
- business rules
- current target version
- duplicate execution
- action compatibility

The rule engine must not update protected source records directly.

Reprocessing must not silently create duplicate actions or notifications.


## 8. Recipient Resolution

Recipients may be resolved from:

- explicit user
- driver
- dispatcher
- organization administrator
- organization owner
- defined role
- responsible organization
- resource custodian
- assigned reviewer
- commercial partner contact
- escalation contact
- configured recipient group

Recipient resolution must use:

- organization context
- active organization relationship
- permission scope
- responsibility valid at the relevant time
- rule scope
- data-sharing rules
- recipient status
- available communication channels

Recipient resolution must record:

- resolution method
- resolved user or contact
- organization context
- role or responsibility used
- resolution date and time
- excluded candidates
- exclusion reasons

Recipient resolution must not grant visibility to unrelated source data.

Each recipient must receive an independent notification instance.


## 9. Notification Instances and Deduplication

A notification instance represents one controlled notification for one resolved recipient.

A notification instance must identify:

- notification identifier
- event identifier
- rule evaluation identifier
- recipient
- recipient organization
- source object reference
- notification category
- severity
- priority
- creation date and time
- scheduled date and time when applicable
- expiration date and time when applicable
- lifecycle status
- deduplication key
- acknowledgement requirement
- escalation policy

Possible notification lifecycle states include:

- CREATED
- SCHEDULED
- QUEUED
- PARTIALLY_DELIVERED
- DELIVERED
- EXPIRED
- CANCELLED
- FAILED

Read state and acknowledgement must remain separate records.

A notification may expose derived read or acknowledgement indicators for presentation, but those indicators must not replace the underlying read-state and acknowledgement history.

Deduplication may use:

- event identifier
- rule version
- recipient
- source object
- notification category
- configured time window
- explicit idempotency key

Duplicate delivery must be prevented through idempotency and deduplication controls.

A suppressed duplicate must retain a record explaining why it was not created or delivered.

Related events may be grouped only when their individual references remain traceable.


## 10. Channels and Message Templates

Supported channels include:

- internal notification centre
- email
- mobile application
- future SMS
- future API callback

A message template must identify:

- template identifier
- template version
- channel
- locale
- title or subject
- body
- supported variables
- fallback locale
- owner
- lifecycle status
- valid-from date
- valid-to date when applicable

Template variables must be validated before rendering.

Rendered messages must preserve:

- template identifier
- template version
- resolved locale
- resolved title or subject
- resolved body
- rendered date and time
- source data references

Changing a template must not rewrite previously rendered messages.

Sensitive information must be limited according to recipient permissions and channel security.


## 11. Delivery Attempts, Retry and Failure

Each notification channel requires an independent delivery attempt.

A delivery attempt must identify:

- notification instance
- recipient destination
- channel
- rendered message
- attempt number
- provider
- provider message identifier
- start date and time
- completion date and time
- delivery status
- error code
- error details
- next retry time when applicable

Possible delivery statuses include:

- QUEUED
- SENDING
- SENT
- DELIVERED
- FAILED
- BOUNCED
- REJECTED
- SUPPRESSED
- CANCELLED

Retry behavior must be configurable by channel and failure type.

Permanent failures must not be retried indefinitely.

Exhausted retries must create a visible failed-delivery state or operational exception.

Failure of one channel must not automatically mark another channel as failed.

Delivery does not prove that the recipient has read or acknowledged the message.


## 12. Severity, Priority and Scheduling

Severity represents the business impact of the condition.

Possible severity levels include:

- INFORMATION
- WARNING
- HIGH
- CRITICAL

Priority represents processing and delivery order.

Severity and priority must remain separate.

Rules may define:

- immediate delivery
- delayed delivery
- scheduled delivery
- recurring reminder
- expiration
- maximum retry period
- quiet-hour behavior
- mandatory delivery behavior

Scheduling must use an explicit timezone.

Recurring scheduling must account for timezone and daylight-saving changes.

Mandatory safety, legal, or financial notifications may bypass quiet hours only through an explicit rule.


## 13. Escalation and Acknowledgement

Escalation may occur when:

- notification is not acknowledged
- notification cannot be delivered
- source issue remains unresolved
- configured deadline expires
- severity increases
- repeated events occur

An escalation policy must define:

- escalation steps
- delay for each step
- recipient resolution method
- channels
- severity changes
- stop conditions
- maximum escalation level

An acknowledgement must identify:

- notification
- acknowledging user
- organization context
- acknowledgement date and time
- acknowledgement channel
- optional note
- acknowledgement type

Acknowledgement does not automatically resolve the underlying business issue.

Resolution of the source issue must remain in the responsible source module.

Each escalation step must be independently recorded and audited.


## 14. User Preferences and Mandatory Notifications

A user may configure permitted preferences such as:

- preferred channels
- locale
- quiet hours
- optional notification categories
- digest preference
- fallback channel
- mobile notification preference

User preferences apply only after authorization and recipient resolution.

A preference must not broaden access to source data.

User preferences must not suppress mandatory safety, legal, compliance, security, or financial notifications.

A disabled optional channel may require an authorized fallback channel.

Preference changes must be time-stamped and auditable.


## 15. Notification History and Read State

Notification history must preserve:

- source event
- rule version
- rule evaluation
- recipient resolution
- notification instance
- rendered messages
- delivery attempts
- retry attempts
- read state
- acknowledgement
- escalations
- cancellation
- expiration

Read state may include:

- UNREAD
- READ
- DISMISSED

Read confirmation is available only when supported by the channel.

Dismissal does not equal acknowledgement.

Notification history must not be deleted merely because the user closes or dismisses a message.


## 16. Cross-Organization Visibility and Privacy

Cross-organization notification is permitted only when supported by:

- direct commercial relationship
- responsibility relationship
- delegated authority
- permission scope
- data-sharing rule
- contractual communication requirement

A notification sent to another organization must expose only the minimum required information.

It must not automatically expose:

- private driver absence reasons
- unrelated customer information
- internal compensation
- unrelated fuel transactions
- internal profit information
- private organization configuration
- unrelated operational records

A sender must not infer broader access from successful message delivery.

Notification delivery does not create source-data permission.


## 17. Audit, Monitoring and Operational Control

Sensitive notification and rule-engine actions must be audited, including:

- rule creation
- rule modification
- rule approval
- rule activation
- rule pausing
- rule replacement
- manual evaluation
- event replay
- manual notification creation
- recipient override
- action execution
- delivery retry
- cancellation
- acknowledgement
- escalation

Audit records must preserve previous and new values where applicable.

Operational monitoring should include:

- received event count
- evaluation count
- matched-rule count
- failed evaluation count
- notification count
- duplicate suppression count
- delivery success rate
- delivery failure rate
- retry count
- acknowledgement time
- escalation count
- processing latency

Event replay and backfill must be authorized, bounded, and auditable.

Replay should support a controlled dry-run mode.

Reprocessing must preserve idempotency.


## 18. Integrations and Final Design Rule

The notification and rule engine supports integration with:

- users and permissions
- organizations
- drivers
- vehicles
- daily reports
- route executions
- availability and resource planning
- maintenance
- fuel management
- pricing and compensation
- banking and reconciliation
- audit and data integrity
- user consent and terms
- reporting and KPI modules
- email providers
- mobile push providers
- future external APIs

Notifications must not silently overwrite or approve operational, financial, legal, or compliance records.

Final design rule:

Domain event, rule definition, rule version, evaluation, action request, action execution, recipient resolution, notification instance, rendered message, delivery attempt, acknowledgement, and escalation must remain separate controlled and auditable records.
