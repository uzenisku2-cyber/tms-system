# TMS Driver Supervisory Scope Architecture v1.0

## 1. Purpose

This document defines the explicit supervisory authorization scope for driver management.

The supervisory scope determines which drivers a user may view or manage.

It must remain separate from:

- driver organizational assignment;
- organization membership;
- organization hierarchy;
- organization relationship;
- role assignment;
- permission capability;
- ownership of driver-created factual records.

## 2. Core Authorization Principle

Driver access is never granted by organizational hierarchy alone.

Authorization requires the combination of:

1. authenticated user identity;
2. active and verified organization context;
3. active organization membership;
4. explicit capability such as `driver.view` or `driver.manage`;
5. an active supervisory scope covering the target driver;
6. a valid organization relationship when access crosses an organization boundary.

Default authorization is deny unless every required condition is satisfied.

## 3. Separation of Concepts

`DriverOrganizationAssignment` answers:

> For which organization does the driver operate during a defined period?

`DriverSupervisoryScope` answers:

> Which drivers is this user explicitly responsible for supervising in this organization context?

These are independent concepts.

Changing a driver's organizational assignment must not automatically grant or revoke another user's supervisory authorization.

Creating an organization relationship must not automatically grant driver visibility or management access.

Granting `driver.view` or `driver.manage` must not by itself determine which drivers are accessible.

## 4. Organization Relationships

`OrganizationRelationship` remains the canonical business relationship between organizations.

It is not an authorization grant.

For cross-organization supervisory access, a supervisory scope may reference the valid organization relationship that makes the requested scope structurally possible.

The relationship proves that the organizations are connected.

The supervisory scope proves that the specific user has responsibility over the target driver population.

Both are required for cross-organization access.

## 5. Supervisory Scope Model

Canonical model:

`DriverSupervisoryScope`

Canonical table:

`driver_supervisory_scopes`

Foundation attributes:

- `id`;
- `organization_id`;
- `supervisor_user_id`;
- `scope_type`;
- `target_organization_id`, nullable;
- `target_driver_id`, nullable;
- `organization_relationship_id`, nullable;
- `valid_from`;
- `valid_until`, nullable;
- `created_by_user_id`;
- `ended_by_user_id`, nullable;
- `end_reason`, nullable;
- timestamps.

## 6. Scope Types

Initial supported scope types are:

### organization

The supervisor receives driver scope over one explicitly selected organization.

`target_organization_id` is required.

`target_driver_id` is null.

For the current organization itself, `organization_relationship_id` is null.

For another organization, a valid supported organization relationship is required.

### driver

The supervisor receives scope over one explicitly selected driver.

`target_driver_id` is required.

`target_organization_id` is null.

This scope does not change the driver's organizational assignment or ownership.

## 7. Permission and Scope

Permissions define capabilities.

Examples:

- `driver.view`;
- `driver.manage`.

Supervisory scope defines the population over which that capability may operate.

A user with `driver.manage` and no applicable supervisory scope cannot manage any driver through supervisory management APIs.

A supervisory scope without `driver.manage` does not grant management capability.

`driver.view` and `driver.manage` remain organization-scoped permissions evaluated in the verified active organization context.

## 8. Own Organization

The fact that a driver belongs to the active organization does not itself create supervisory authority.

Users who need organization-wide driver management receive an explicit organization supervisory scope for that organization.

This keeps organizational assignment and authorization scope separate even for own drivers.

## 9. Cross-Organization Access

Cross-organization driver access requires all of:

- explicit `driver.view` or `driver.manage`;
- an active supervisory scope;
- a valid supported organization relationship;
- a target driver covered by that scope;
- verified active organization context;
- applicable visibility rules.

A subcontracting relationship alone never grants access.

## 10. Carrier Hierarchy

The master/main carrier may be granted explicit supervisory scopes covering:

- its own organization;
- selected directly subordinate carrier organizations;
- later, other explicitly supported responsibility scopes if the architecture is extended.

An external carrier may be granted scope over its own drivers without receiving visibility into sibling carriers or the master carrier's unrelated drivers.

No recursive hierarchy access is inferred merely from organization structure.

## 11. Driver Identity and Ownership

A supervisor does not become the owner of another driver.

A supervisor does not become the author of the driver's factual operational records.

Driver factual authorship, organization assignment, visibility, supervision and management authorization remain separate concepts.

## 12. Temporal and Audit Requirements

Supervisory scopes are time bounded and auditable.

An active scope satisfies:

`valid_from <= effective date`

and:

`valid_until IS NULL OR valid_until >= effective date`

Ending a scope preserves the historical row.

Historical supervisory scope records are not overwritten or deleted during normal lifecycle operations.

Creation and ending record the acting user.

## 13. Foundation Database Invariants

The database foundation must enforce:

- valid `scope_type`;
- exactly one target dimension for each scope type;
- no invalid date interval;
- foreign-key integrity;
- cross-organization relationship reference compatibility at the service boundary;
- prevention of duplicate overlapping open grants for the same canonical scope.

The service layer must additionally verify active organization context, permission and relationship validity.

## 14. Authorization Service Boundary

Controllers must not independently reconstruct supervisory rules.

A canonical authorization/scope service will resolve whether a supervisor may access a target driver.

The service will provide reusable boundaries for at least:

- visible driver queries;
- `driver.view`;
- `driver.manage`;
- own-organization driver management;
- cross-organization driver management.

Existing organization-assignment queries must not be reused as authorization scope.

## 15. Migration Strategy

Sprint 021 introduces the supervisory scope foundation without rewriting historical driver organization assignments.

Existing `organization_relationships` remain unchanged as the organization relationship source of truth.

Existing driver assignments remain the organizational history source of truth.

Authorization consumers will be migrated deliberately to the new supervisory scope service.

No implicit supervisory grants are inferred from existing assignment history.

## 16. Sprint 021 Boundary

Sprint 021 foundation covers:

- canonical supervisory scope persistence;
- model and relationships;
- lifecycle rules;
- authorization/scope service;
- permission plus scope enforcement;
- tests for own and cross-organization isolation;
- integration foundation for driver management.

Driver-management UI may consume this foundation only after the authorization contract is proven by backend tests.
