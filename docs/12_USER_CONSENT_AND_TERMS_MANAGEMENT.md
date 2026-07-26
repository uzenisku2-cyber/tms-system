# TMS User Consent and Terms Management Model v1.1

## 1. Purpose

This document defines the document, terms, policy, acceptance, acknowledgement, consent, signature, withdrawal, evidence, applicability, access-control, and historical-integrity architecture of the TMS platform.

The model supports:

- platform terms
- organization-specific terms
- operational rules
- privacy notices
- data-processing notices
- contractual documents
- internal instructions
- safety instructions
- partner requirements
- mandatory acknowledgements
- voluntary consents
- electronic acceptance
- electronic signatures when applicable
- document versioning
- multilingual documents
- applicability rules
- access gating
- withdrawal and revocation
- historical evidence
- complete auditability

The model must remain consistent with:

- TMS Business Principles
- TMS Core Organization Model
- TMS User Roles and Permissions
- TMS Driver and Vehicle Model
- TMS Notification and Rule Engine
- TMS Audit and Data Integrity Model
- applicable contractual, privacy, employment, and compliance requirements


## 2. Core Separation Principle

The following concepts must remain separate:

- document definition
- document version
- document content
- publication
- effective period
- applicability rule
- presentation event
- acceptance
- acknowledgement
- consent
- signature
- refusal
- withdrawal
- revocation
- expiration
- evidence record
- audit event
- access restriction

A document definition identifies the continuing document concept.

A document version preserves specific immutable wording.

Publication makes a version available through a controlled channel.

An effective period determines when a version is intended to apply.

An applicability rule determines to whom and under which context the version applies.

Presentation records that the version was shown or made available.

Acceptance records agreement to applicable terms when acceptance is the required action.

Acknowledgement records confirmation that information was received or reviewed.

Consent records a specific voluntary authorization when consent is the applicable basis.

A signature records a signing act and its associated evidence.

These actions must not be represented by one generic confirmation state.

An audit event records that an action occurred but must not replace the business evidence record for that action.

A button click alone does not prove that every legal, contractual, identity, disclosure, or authorization requirement was satisfied.


## 3. Document Definitions and Document Types

A document definition must have a stable internal identity.

A document definition may identify:

- internal document identifier
- document type
- owner
- responsible organization
- title
- description
- intended purpose
- applicable business process
- confidentiality classification
- default language
- lifecycle status
- creation date and time
- archival date and time when applicable

Document types may include:

- platform terms
- organization terms
- privacy notice
- data-processing notice
- contractual agreement
- employment-related document
- operational rules
- safety instructions
- internal policy
- partner requirement
- vehicle-use agreement
- fuel-card agreement
- compensation agreement
- deduction authorization
- marketing consent
- communication preference
- other controlled document

Document type does not by itself determine the required user action.

The same document type may require acceptance, acknowledgement, signature, or no action depending on its purpose and applicability configuration.


## 4. Document Ownership, Authority and Scope

Each document definition must identify its owner and publishing authority.

The owner may be:

- platform operator
- organization
- external contractual party
- authorized compliance function
- another explicitly identified legal or operational party

Document ownership must remain separate from:

- authorship
- publishing authority
- applicability
- access permission
- acceptance authority
- signature authority
- organization hierarchy

A document owner must not publish requirements for unrelated users or organizations without an applicable relationship or authority.

Document scope may be based on:

- platform use
- organization membership
- employment relationship
- external-carrier relationship
- commercial contract
- operational responsibility
- assigned role
- assigned module
- assigned vehicle
- assigned fuel card
- specific business process
- geographic or regulatory context
- delegated authority

Organizational hierarchy alone does not prove that a document applies to a user.

A higher organizational level does not automatically receive authority to impose, inspect, or replace another organization's private internal documents.


## 5. Document Versions, Content and Localization

Each published wording must be represented by an immutable document version.

A document version must identify:

- document definition
- version identifier
- version number
- version status
- language
- locale
- exact content or immutable content reference
- content hash
- summary of changes
- creating actor
- approving actor when applicable
- approval date and time
- publication date and time
- effective-from date and time
- effective-to date and time when applicable
- replacement-version reference
- reason for replacement

Possible document-version states include:

- DRAFT
- UNDER_REVIEW
- APPROVED
- PUBLISHED
- EFFECTIVE
- WITHDRAWN
- REPLACED
- EXPIRED
- ARCHIVED

An approved or published version must not be edited in place.

Changing wording requires a new version.

Publication date and effective date must remain separate.

A future-effective version must not replace the currently effective version before its effective date.

Historical acceptance or consent must continue to reference the exact version and language originally presented.

Translations must be versioned and traceable to the applicable source version.

A translation update must not silently change evidence attached to an earlier language version.


## 6. Applicability and Requirement Resolution

Applicability must be evaluated independently from document ownership and organization hierarchy.

An applicability rule may evaluate:

- user
- organization
- organization relationship
- role
- permission
- employment status
- external-carrier status
- contract
- module
- business process
- vehicle assignment
- fuel-card assignment
- location
- language
- age or representation status when relevant
- valid date and time
- previous action status
- combination of conditions

An applicability rule must identify:

- rule identifier
- owner
- organization scope
- document version
- required action type
- target population
- conditions
- valid-from date
- valid-to date when applicable
- priority
- lifecycle status
- creating actor
- approving actor when required
- change reason

Possible requirement types include:

- INFORMATION_ONLY
- ACKNOWLEDGEMENT_REQUIRED
- ACCEPTANCE_REQUIRED
- CONSENT_OPTIONAL
- CONSENT_REQUIRED_FOR_OPTIONAL_PROCESSING
- SIGNATURE_REQUIRED
- EXTERNAL_EVIDENCE_REQUIRED

An applicability result must identify the document version, user, organization context, rule version, required action, and evaluation time.

A requirement created by a new role, organization relationship, contract, or assignment must not depend solely on the user's first login.


## 7. Presentation, Accessibility and User Comprehension

The system must record the presentation context when evidence of presentation is required.

A presentation record may identify:

- user
- document version
- language
- organization context
- presentation date and time
- channel
- application or interface
- device class
- session identifier
- presentation status
- accessible-content reference
- downloadable-content reference
- required action
- applicable rule version

Possible presentation channels include:

- web application
- mobile application
- email link
- administrator-assisted process
- external signature provider
- imported offline process
- another controlled channel

The complete applicable document must be available before the action is submitted.

A summary must not silently replace access to the complete document.

The system should support accessible presentation appropriate to the interface.

The system must not record acceptance merely because a document page was opened.

Failure to display or retrieve required content must not create successful acceptance, acknowledgement, consent, or signature evidence.


## 8. Acceptance, Acknowledgement, Consent and Signature

Acceptance, acknowledgement, consent, and signature must use separate action types and evidence records.

Acceptance may be used for agreement to terms or contractual conditions.

Acknowledgement may be used to confirm receipt, review, or awareness of information.

Consent may be used only for the specific purpose and scope represented by the consent request.

Signature may be used where a signing process and associated identity evidence are required.

An action record must identify its action type explicitly.

The system must not treat:

- acknowledgement as contractual acceptance
- acceptance as voluntary consent
- consent as acceptance of unrelated terms
- presentation as acknowledgement
- authentication as signature
- typed name as verified identity without applicable evidence
- absence of refusal as consent
- continued system use as consent unless an applicable rule explicitly and validly defines that consequence

One user action must not bundle unrelated voluntary consents with mandatory terms.

Separate purposes should use separate consent choices where independent choice is required.

A signature record must remain separate from the signed document version and from the audit event recording the signing process.


## 9. Consent Purpose, Scope and Processing Context

A consent definition must identify:

- consent purpose
- requesting party
- responsible organization
- affected user or represented person
- processing or activity scope
- data categories when applicable
- recipient categories when applicable
- communication channels when applicable
- valid-from date
- valid-to date when applicable
- withdrawal method
- consequences of refusal when applicable
- related privacy or information notice
- applicable document version
- required granularity
- lifecycle status

Consent must not be used as a generic substitute for another applicable contractual, operational, employment, safety, or compliance basis.

The system must support optional consent without falsely recording refusal as acceptance.

The user must be able to distinguish mandatory terms from optional consent.

A consent request must not hide unrelated purposes in one generic statement.

Consent validity must be evaluated from the applicable evidence, scope, version, dates, withdrawal status, and governing rules.

The technical system records evidence and workflow state; it does not independently determine legal validity without the applicable policy and authority configuration.


## 10. Evidence Records and Identity Context

Each acceptance, acknowledgement, consent, refusal, withdrawal, revocation, signature, or invalidation action must create an independent evidence record.

An evidence record must identify:

- evidence identifier
- user or represented subject
- authenticated actor
- effective actor when delegation applies
- organization context
- document definition
- document version
- exact language
- content hash or immutable content reference
- action type
- action outcome
- evidence lifecycle state
- validity evaluation reference when applicable
- action date and time
- recording date and time
- timezone
- presentation reference
- applicability-rule version
- required-action type
- channel
- application or integration
- session identifier when applicable
- authentication context
- identity-assurance context when applicable
- IP address when permitted and necessary
- device or client metadata when permitted and necessary
- explicit user choice
- reason when applicable
- withdrawal or replacement reference when applicable
- source evidence when imported
- integrity information

Possible action types include:

- ACCEPT
- ACKNOWLEDGE
- CONSENT
- SIGN
- REFUSE
- WITHDRAW
- REVOKE
- INVALIDATE

Possible action outcomes include:

- SUCCEEDED
- FAILED
- DENIED
- CANCELLED

Possible evidence lifecycle states include:

- RECORDED
- SUPERSEDED
- INVALIDATED
- ARCHIVED

Expiration is a validity condition evaluated from applicable dates and rules, not an action outcome.

Withdrawal and revocation are separate recorded actions; they must not silently mutate the original evidence record.

Action type, action outcome, evidence lifecycle state, and current validity must remain separate.

Current validity may be derived as NOT_YET_EFFECTIVE, VALID, EXPIRED, WITHDRAWN, REVOKED, OUT_OF_SCOPE, or REQUIRES_REVIEW without rewriting the evidence record.

Identity evidence must be proportionate to the importance of the action.

Authentication proves control of an authenticated account only to the extent supported by the authentication method.

Authentication does not automatically prove contractual authority, representation authority, informed consent, or signature validity.

Evidence records must be immutable after successful recording.

Corrections must use linked correction or invalidation records rather than silent editing.


## 11. Refusal, Withdrawal, Revocation and Expiration

Refusal, withdrawal, revocation, and expiration must remain separate.

Refusal records that the requested action was not granted.

Withdrawal records that previously granted consent or authorization was withdrawn by an entitled actor.

Revocation records that an authorized party invalidated or ended an earlier authorization where applicable.

Expiration records that validity ended because of time or another configured condition.

Withdrawal must not delete or overwrite the original consent evidence.

A withdrawal record must identify:

- original evidence record
- withdrawing actor
- represented subject
- organization context
- withdrawal date and time
- effective date and time
- channel
- reason when provided
- resulting scope
- processing or activity consequences
- notification requirement
- audit reference

The system must distinguish future processing restrictions from historical processing evidence.

Refusal or withdrawal must not be recorded as acceptance.

The consequences of refusal or withdrawal must be determined by the applicable requirement and business process, not by a generic global rule.


## 12. Material Changes and New Action Requirements

A new document version does not automatically invalidate all prior evidence.

The system must evaluate whether a new user action is required.

The evaluation may consider:

- material wording change
- changed purpose
- changed scope
- changed requesting party
- changed organization relationship
- changed data categories
- changed recipients
- changed contractual conditions
- changed user role
- changed module access
- changed validity period
- governing policy
- explicit replacement rule

Possible outcomes include:

- no new action required
- information-only notification
- new acknowledgement required
- new acceptance required
- new consent required
- new signature required
- manual review required

The requirement decision must identify the rule and document versions used.

A minor formatting or typographical correction must not automatically create false evidence of new acceptance.

A materially changed document must not silently inherit acceptance or consent from an earlier version when a new action is required.

Previous evidence must remain historically visible after replacement.


## 13. Access Gating and Functional Restrictions

Access gating must remain separate from the acceptance or consent evidence itself.

A requirement may restrict:

- platform login
- specific module access
- specific action
- document download
- vehicle usage
- fuel-card usage
- financial operation
- administrative action
- optional communication
- optional processing purpose

A restriction record may identify:

- affected user
- organization context
- restricted capability
- requirement source
- required document version
- required action
- current evidence status
- restriction start
- restriction end when applicable
- exception or override
- reason

Mandatory access conditions and optional consent must not be treated identically.

Refusal of optional consent must not block unrelated mandatory platform functions.

Where refusal of terms prevents a contractual or operational function, the restriction must reference the applicable requirement.

An administrator must not manually mark a user as having accepted merely to remove an access restriction.

Exceptional access must be explicitly authorized, limited, and audited without fabricating acceptance or consent evidence.


## 14. Organization Relationships and Cross-Organization Applicability

Documents may apply across organizations only through an applicable relationship such as:

- platform agreement
- employment relationship
- carrier agreement
- subcontractor agreement
- delegated operational responsibility
- service contract
- vehicle-use relationship
- fuel-card relationship
- explicit data-sharing relationship
- another documented authority

A responsibility hierarchy does not itself prove contractual authority or document applicability.

Each cross-organization requirement must identify:

- issuing party
- affected party
- relationship
- document version
- target users
- required action
- valid period
- visibility scope
- evidence-access rules
- termination behavior

An organization may manage its own internal documents without automatically exposing them to parent, partner, or platform organizations.

A platform administrator must not automatically see complete private consent or contractual evidence belonging to another organization.

Shared evidence should expose only the minimum information required for the applicable relationship.


## 15. Representation, Delegation and Assisted Actions

The system may support actions performed through:

- authorized representative
- legal representative
- organization representative
- guardian where applicable
- administrator-assisted process
- imported offline signature process
- external signature provider

The represented subject and acting person must remain separate.

A represented action must identify:

- represented subject
- acting representative
- representation type
- authority reference
- authority validity period
- organization context
- document version
- action type
- action date and time
- source evidence
- verification status

Delegation must not silently replace the identity of the represented subject.

An administrator assisting with presentation must not be recorded as the accepting or consenting user unless the administrator is the authorized acting representative.

Representation authority must be evaluated independently from ordinary application permission.


## 16. Privacy, Retention, Export and Evidence Access

Consent and terms evidence may contain sensitive personal, contractual, employment, or technical information.

Evidence collection must follow data-minimization rules.

The system must not unnecessarily store:

- passwords
- authentication secrets
- complete access tokens
- private cryptographic keys
- unrestricted device fingerprints
- unrelated browsing information
- unnecessary personal explanations
- unrelated document content
- unnecessary network metadata

Retention rules must distinguish:

- active document versions
- historical document versions
- presentation records
- acceptance evidence
- acknowledgement evidence
- consent evidence
- withdrawal evidence
- signature evidence
- audit events
- technical logs

Evidence retention may depend on:

- document type
- contractual relationship
- legal or compliance requirement
- active dispute
- employment relationship
- organization policy
- processing purpose
- legal hold

Deletion or anonymization must not silently destroy evidence that must remain available for an active contractual, legal, financial, compliance, or dispute purpose.

Export of evidence must record the exporting actor, scope, reason when required, date and time, and delivery method.

Access to sensitive evidence may itself require an access-audit event.


## 17. Audit, Corrections, Notifications and Operational Control

The following actions must be audited when applicable:

- document creation
- document-version creation
- approval
- publication
- withdrawal of a version
- applicability-rule creation
- requirement evaluation
- presentation
- acceptance
- acknowledgement
- consent
- refusal
- withdrawal
- revocation
- signature
- evidence import
- evidence invalidation
- access restriction
- exceptional override
- evidence export
- retention action
- anonymization or deletion
- configuration change

The audit event and action evidence record must remain separate.

A failed audit write must not silently produce apparently valid acceptance, consent, or signature evidence.

Where evidence and required audit data can share one database transaction, they should commit atomically.

Where atomic persistence is not possible, a durable and idempotent delivery mechanism must reconcile missing or delayed audit events.

Notifications may be generated for:

- newly applicable document
- upcoming effective date
- required action
- missing action
- rejected or refused action
- expiring evidence
- withdrawn consent
- replaced document version
- changed access restriction

Notifications and escalations must be created through the Notification and Rule Engine.

A notification does not itself create acceptance, acknowledgement, consent, refusal, withdrawal, or signature evidence.


## 18. Integrations and Final Design Rule

The consent and terms model supports integration with:

- organizations
- users and permissions
- identity and authentication
- employment and carrier relationships
- drivers
- vehicles
- fuel cards
- pricing and compensation
- document storage
- access control
- notifications and rule engine
- audit and data integrity
- reporting and KPI modules
- future electronic-signature providers
- future identity-verification providers
- future compliance integrations

The consent and terms module must not silently create, infer, approve, withdraw, revoke, or replace contractual, employment, financial, operational, or privacy records owned by another module.

Final design rule:

Document definition, document version, publication, effective period, applicability rule, presentation, acceptance, acknowledgement, consent, signature, refusal, withdrawal, revocation, expiration, evidence record, audit event, and access restriction must remain separate controlled and auditable records.
