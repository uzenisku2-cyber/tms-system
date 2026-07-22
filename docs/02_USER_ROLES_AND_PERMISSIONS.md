# TMS User Roles and Permissions v1.1

## 1. Purpose

This document defines the user role, permission, scope, and data visibility architecture of the TMS platform.

The model supports:

- MASTER platform governance
- carrier organizations
- sub-carrier organizations
- organization owners and administrators
- dispatchers
- drivers
- financial users
- delegated operational management
- future specialized roles

The authorization model must remain consistent with:

- TMS Business Principles
- TMS Core Organization Model
- organizational responsibility relationships
- resource ownership rules
- data visibility rules


## 2. Core Authorization Principle

A role alone does not determine access.

Access is determined by the combination of:

- authenticated user identity
- active organization membership
- assigned roles
- granted permissions
- selected organization context
- permission scope
- organizational responsibility relationships
- resource ownership
- data visibility rules
- delegated authority
- record status
- audit and security restrictions

The default authorization rule is:

> Access is denied unless it is explicitly allowed.

Organizational hierarchy does not automatically grant unrestricted access.


## 3. Roles and Permissions

Roles represent reusable responsibility profiles.

Examples:

- MASTER
- organization administrator
- dispatcher
- driver
- financial user

Permissions represent individual system capabilities.

Examples:

- view a daily report
- submit a daily report
- approve a daily report
- manage users
- manage vehicles
- manage pricing
- view financial results

A role may contain multiple permissions.

Permissions may also be granted or restricted independently when the business model requires it.


## 4. Permission Scope

Every permission must be evaluated within a defined scope.

Supported scope concepts include:

- own records
- assigned resources
- selected organization
- delegated operational area
- direct responsibility relationship
- responsibility chain
- explicitly shared records
- platform governance scope

The same permission may produce different access depending on its scope.

Example:

A driver and a dispatcher may both have permission to view daily reports.

The driver may see only their own reports.

The dispatcher may see reports within the operational area delegated to them.


## 5. Organization Context

A user operates within an active organization context.

The organization context determines:

- which role assignments are active
- which permissions are evaluated
- which resources may be accessed
- which data visibility rules apply
- which responsibility relationships are relevant

A user may belong to more than one organization.

Roles and permissions must therefore be evaluated separately for each organization context.


## 6. MASTER Role

MASTER represents the platform owner and platform governance authority.

Possible capabilities include:

- manage platform configuration
- create and administer organizations
- manage global permission definitions
- manage platform-level integrations
- manage system-wide security rules
- manage technical support access
- review platform audit information

MASTER status does not automatically mean unrestricted ownership of organization data.

Access to internal organization data must be:

- authorized
- purpose-limited
- auditable
- consistent with platform terms
- restricted to the required scope

Platform governance and business data ownership are separate concepts.


## 7. Organization Owner and Administrator

An organization owner represents the highest responsibility level within a specific organization.

An organization administrator performs delegated administrative management.

Possible capabilities include:

- manage organization memberships
- create and manage organization users
- assign organization roles
- grant organization permissions
- manage dispatchers
- manage drivers
- manage organization resources
- configure organization rules
- configure operational settings
- manage organization-specific integrations

An organization role does not automatically transfer ownership of:

- user-created factual records
- vehicles owned by another organization
- fuel cards owned by another organization
- financial data belonging to another organization
- internal data of a connected organization


## 8. Dispatcher Role

Dispatcher is a configurable operational role.

Possible permissions include:

- view drivers within an authorized scope
- view assigned operational resources
- review daily reports
- request report corrections
- approve daily reports
- record review notes
- manage operational exceptions
- communicate with drivers
- view operational performance

Dispatcher access is limited by:

- organization context
- delegated responsibility
- explicit permissions
- data visibility rules
- operational assignment

A dispatcher does not receive financial or administrative access unless it is granted separately.


## 9. Driver Role

A driver is a full TMS user.

Typical permissions include:

- access their own account
- view their own assignments
- view resources assigned to them
- create their own daily reports
- edit reports that remain editable
- submit daily reports
- respond to correction requests
- use the mobile application
- view the status of their own records

A driver is the factual author of operational information they enter.

Approval of the report does not change the identity of the original author.


## 10. Dual Roles and Delegated Management

A user may perform more than one business function.

Examples:

- a driver may also be an organization owner
- a driver may also be a dispatcher
- an external carrier owner may also perform deliveries
- a financial user may also have administrative permissions

This is represented by multiple role assignments or delegated permissions.

It is not represented as an employee hierarchy between users.

Example:

Driver A may have:

- driver permissions for their own operational work
- delegated dispatcher permissions for an authorized driver group

Driver B may have:

- driver permissions for their own operational work only

Driver A does not become the owner of Driver B's factual records.


## 11. Financial User Role

Financial access must be assigned separately from operational access.

Possible permissions include:

- view authorized financial data
- manage price lists
- manage compensation rules
- review calculated compensation
- manage financial classifications
- perform reconciliation
- view settlement status
- export authorized financial reports

Financial visibility follows the economic responsibility chain.

A higher organization does not automatically receive visibility into the internal accounting of a lower organization.


## 12. Permission Naming Convention

Permissions should use a predictable resource-and-action format.

Examples:

- organization.view
- organization.manage
- user.view
- user.create
- user.edit
- user.disable
- user.assign_roles
- daily_report.view
- daily_report.create
- daily_report.edit
- daily_report.submit
- daily_report.review
- daily_report.request_correction
- daily_report.approve
- driver.view
- driver.manage
- vehicle.view
- vehicle.manage
- vehicle.assign
- fuel_card.view
- fuel_card.manage
- fuel_card.assign
- pricing.view
- pricing.manage
- compensation.view
- compensation.manage
- reconciliation.view
- reconciliation.manage
- audit.view

Permission names define capabilities.

They do not define data scope by themselves.


## 13. Ownership and Visibility

Ownership and visibility must be evaluated separately.

A user may be allowed to see a record without becoming its owner.

A user may own or author a record while another authorized user reviews or approves it.

Examples:

- a driver authors a daily report
- a dispatcher reviews the report
- an organization receives operational visibility
- a resource owner defines usage rules
- a financial user sees only the financial records within their authorized scope

Approval, review, assignment, and visibility do not automatically transfer ownership.


## 14. Cross-Organization Access

Carrier organizations and sub-carrier organizations remain economically and operationally independent.

A responsibility relationship may allow access to selected shared information.

Examples of information that may be shared:

- delivered service quantities
- route execution results
- approved operational reports
- contractual performance indicators
- settlement information between direct commercial parties

Information that is not automatically shared includes:

- internal salaries
- private employment agreements
- internal profit calculations
- unrelated customer contracts
- private financial accounts
- internal organization settings

Cross-organization access must be based on:

- a defined responsibility relationship
- an explicit permission
- an authorized scope
- a visibility rule
- a valid business purpose


## 15. Delegation Rules

Organizations may delegate operational or administrative responsibilities.

A delegation must define:

- delegating organization
- receiving user or role
- granted permissions
- permitted scope
- effective date
- expiration date when applicable
- revocation status

Delegation does not automatically transfer:

- organization ownership
- resource ownership
- authorship of historical records
- financial responsibility
- unrestricted visibility

Expired or revoked delegation must no longer grant access.


## 16. Record State Restrictions

Permission evaluation may depend on the state of a record.

Example daily report states may include:

- draft
- submitted
- correction requested
- corrected
- approved
- closed

A user may have permission to edit daily reports but still be prevented from editing an approved or closed report.

Record-state restrictions apply in addition to role and permission checks.


## 17. Sensitive Operations and Audit

Sensitive operations must be auditable.

Examples include:

- role assignment
- permission changes
- delegated access
- approval of operational records
- financial rule changes
- ownership changes
- access to protected organization data
- administrative override
- support access by MASTER users

The audit record should identify:

- acting user
- organization context
- affected resource
- performed action
- date and time
- previous value
- new value
- reason when required


## 18. Future Extensions

The authorization model must support:

- web portal access
- mobile application access
- API access
- service accounts
- external integrations
- temporary access
- approval workflows
- configurable roles
- field-level restrictions
- organization-specific policies
- multi-factor authentication requirements

Future modules must use the same role, permission, scope, ownership, and visibility principles.
