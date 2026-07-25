# TMS Identity and Organization Architecture v1.0

## 1. Purpose

This document defines the implementation baseline for Sprint 002.

It translates the approved business principles from the existing TMS
documentation into an enforceable Identity and Organization architecture.

The architecture must support:

- the primary carrier organization;
- external carrier and subcontractor organizations;
- users belonging to one or more organizations;
- organization-scoped roles and permissions;
- employee drivers;
- drivers of external carrier organizations;
- dispatchers and organization administrators;
- delegated actions performed on behalf of drivers;
- strict separation of identity, organization, role, and operational data.

## 2. Core Separation Principle

The following concepts are separate:

- User: the person who authenticates in TMS.
- Organization: the business entity in whose context work is performed.
- Membership: the relationship between a user and an organization.
- Role: a reusable responsibility profile inside an organization.
- Permission: an individual authorized capability.
- Driver: the professional driver profile associated with a user.
- Organization relationship: an explicit business relationship between
  two organizations.

No single foreign key may replace all of these concepts.

## 3. User

A User represents a unique login identity.

A user contains authentication and account lifecycle information.

Required account states:

- active;
- suspended;
- disabled.

Rules:

- an inactive account cannot create new API tokens;
- suspension does not delete historical operational records;
- one person has one user identity;
- a user may belong to multiple organizations;
- a user may hold different roles in different organizations;
- global access must not be inferred from a role in one organization.

## 4. Organization

An Organization represents an independent business entity.

Initial organization types:

- master;
- carrier;
- subcontractor.

The type is a domain classification and must not itself grant permissions.

Required organization states:

- active;
- suspended;
- archived.

An organization owns or controls only records explicitly assigned to it.

A parent or connected organization does not automatically receive
unrestricted access to another organization's internal data.

## 5. Organization Membership

OrganizationMembership connects a User to an Organization.

Required fields:

- organization_id;
- user_id;
- relationship_type;
- status;
- valid_from;
- valid_until;
- created_at;
- updated_at.

Initial relationship types:

- owner;
- employee;
- contractor;
- representative.

Initial membership states:

- invited;
- active;
- suspended;
- ended.

Rules:

- a user may have only one current membership record for the same
  organization and relationship;
- only an active and currently valid membership grants organization access;
- ending membership does not delete historical records;
- role assignment does not replace membership;
- membership does not automatically grant operational permissions.

## 6. Organization Relationships

OrganizationRelationship represents an explicit relationship between two
organizations.

Required fields:

- source_organization_id;
- target_organization_id;
- relationship_type;
- status;
- valid_from;
- valid_until;
- created_at;
- updated_at.

Initial relationship type:

- subcontracting.

Rules:

- an organization cannot relate to itself;
- the relationship is directional;
- the relationship does not automatically grant data access;
- cross-organization access still requires explicit permission and scope;
- historical relationships remain auditable after termination.

## 7. Driver Profile

Driver is a professional profile associated one-to-one with a User.

Rules:

- driver.user_id is required;
- driver.user_id is unique;
- a user may exist without a driver profile;
- a driver profile cannot exist without a valid user identity;
- organization affiliation is derived from active memberships;
- driver status is separate from user account status;
- deleting a user must not silently destroy operational history.

Required driver lifecycle states:

- active;
- suspended;
- inactive.

Driver eligibility may additionally depend on:

- active user account;
- active organization membership;
- professional data validity;
- organization-specific rules.

## 8. Roles and Permissions

Spatie Laravel Permission remains the authorization engine.

Organization-scoped authorization will use the package teams capability.

Configuration baseline:

- teams enabled;
- team foreign key renamed to organization_id;
- roles assigned independently in each organization context;
- permissions evaluated in the verified active organization context.

Initial role profiles:

- master;
- organization_owner;
- organization_admin;
- dispatcher;
- driver;
- financial_user.

Roles are defaults, not hard-coded access decisions.

Permissions must use resource-action names, for example:

- organization.view;
- organization.manage;
- membership.view;
- membership.manage;
- user.view;
- user.manage;
- user.assign_roles;
- driver.view;
- driver.manage;
- daily_report.view;
- daily_report.create;
- daily_report.submit;
- daily_report.review;
- daily_report.approve.

## 9. Active Organization Context

The active organization may be transported by an HTTP header or another
client mechanism, but client input is never authorization by itself.

For every protected request the server must verify:

1. the user is authenticated;
2. the user account is active;
3. the requested organization exists and is active;
4. the user has an active and currently valid membership;
5. organization-scoped roles and permissions are loaded;
6. the requested record belongs to an authorized scope;
7. record lifecycle rules permit the requested action.

The existing IdentifyTenant middleware must not trust X-Tenant-ID without
membership validation.

The target concept name is OrganizationContext, not an unverified tenant ID.

## 10. Delegated Driver Actions

A driver is the physical performer of a route.

The person entering data may be different.

Operational records must therefore preserve:

- performed_by_driver_id;
- entered_by_user_id;
- responsible_organization_id;
- reviewed_by_user_id when reviewed;
- approved_by_user_id when approved.

An authorized dispatcher, administrator, or carrier representative may
enter a daily report for a driver only within an explicitly authorized
organization scope.

Delegated entry never changes the identity of the actual driver.

## 11. Security Invariants

The implementation must guarantee:

- organization context cannot be selected without active membership;
- direct object identifiers cannot bypass organization scope;
- suspended users cannot authenticate;
- suspended memberships cannot authorize requests;
- roles from one organization do not apply in another organization;
- sensitive personal data is not written wholesale to application traces;
- account or membership deactivation preserves audit history;
- authorization failures return 401 or 403 without leaking foreign data.

## 12. Migration Strategy

Implementation will be incremental.

Phase 1:

- create organizations;
- create organization_memberships;
- create organization_relationships;
- add user account status;
- make driver.user_id unique;
- replace destructive user-to-driver cascade behavior;
- enable organization-scoped Spatie roles.

Phase 2:

- introduce verified OrganizationContext;
- replace unverified tenant header behavior;
- scope user and driver queries by organization;
- define role and permission seeders;
- add authorization policies and tests.

Phase 3:

- migrate the existing user and driver into a bootstrap organization;
- validate all existing API behavior;
- connect Daily Report development to the new organization model.

No existing migration will be rewritten after it has been shared.
Corrections will use new forward-only migrations.

## 13. Testing Baseline

Required automated tests include:

- active user can authenticate;
- suspended or disabled user cannot authenticate;
- user can enter an organization with active membership;
- user cannot enter a foreign organization;
- expired or suspended membership denies access;
- roles are isolated by organization;
- organization administrator can manage permitted members;
- dispatcher can view authorized drivers;
- dispatcher cannot view unrelated organization drivers;
- driver has exactly one driver profile;
- delegated actions preserve actual driver and entry actor;
- deactivation does not delete operational history.

## 14. Out of Scope for the First Implementation Unit

The following remain outside the first database foundation:

- complete Daily Report implementation;
- pricing and compensation calculations;
- depot Excel import;
- ORLEN and MOL fuel imports;
- billing and reconciliation;
- advanced organization delegation rules;
- mobile application user interface.

## 15. Final Design Rule

Authentication answers who the person is.

Membership answers where the person currently operates.

Role and permission answer what the person may do there.

Record scope answers which data the person may access.

Operational identity answers who actually performed the work.

These decisions must remain separate throughout the TMS architecture.

## Secure bootstrap organization

The development seed process must not contain fixed credentials.

Bootstrap identity creation is explicitly disabled by default and is enabled
only through deployment-specific environment configuration:

- `TMS_BOOTSTRAP_ENABLED`
- `TMS_BOOTSTRAP_ORGANIZATION_NAME`
- `TMS_BOOTSTRAP_OWNER_USER_ID`
- `TMS_BOOTSTRAP_OWNER_NAME`
- `TMS_BOOTSTRAP_OWNER_EMAIL`
- `TMS_BOOTSTRAP_OWNER_PASSWORD`
- `TMS_BOOTSTRAP_ADMIN_USER_ID`
- `TMS_BOOTSTRAP_ADMIN_NAME`
- `TMS_BOOTSTRAP_ADMIN_EMAIL`
- `TMS_BOOTSTRAP_ADMIN_PASSWORD`

When enabled, the bootstrap process creates or resolves one active `master`
organization, one active `owner` membership and an organization-scoped
`super-admin` role using the `web` guard.

An existing user with the configured email is reused. Its name and password
are not overwritten. No password or complete credential is written to logs
or console output.

The bootstrap seeder is idempotent and always clears the Spatie team context.
Applying it to the real PostgreSQL database is a separate data-changing
operation requiring explicit approval.
### Existing account bootstrap by ID

For an already existing account, `TMS_BOOTSTRAP_ADMIN_USER_ID` may contain
the positive internal user ID. In this mode the administrator name, email and
password variables are not required. The existing account credentials and
profile data are never overwritten.

The configured user must exist and must have an active account status. The
organization, membership, organization-scoped roles and role assignment
are created atomically. A missing or inactive account aborts the complete
bootstrap operation.
### Separate owner and super-admin bootstrap

Business ownership and application administration are separate concerns.

The bootstrap owner receives an active `owner` organization membership. No
application role is assigned automatically to the owner.

The bootstrap administrator receives an active `representative` organization
membership and the organization-scoped `super-admin` role.

Each identity may resolve either from a positive existing user ID or from its
own name, unique email address and password. Owner and administrator must
resolve to different user accounts. Creation of the organization, both
memberships, the permission catalogue and the role assignment is atomic.