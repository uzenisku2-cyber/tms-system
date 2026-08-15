# DRAYVIA Price List Administration and Billing Overview

## 1. Finance target structure

Sprint 021 prepares the operational Finance area:

- Odběratelé
- Ceníky
  - Fakturační ceníky
  - Ceníky řidičů
- Fakturace
- Srovnání
- Ziskovost

## 2. Odběratel is a relationship role

An odběratel is not a duplicate Organization record.

The existing Organization identity remains canonical. Customer/provider meaning
is determined by a direct commercial relationship.

For the current carrier pricing direction:

- source organization = customer / odběratel
- target organization = provider / carrier performing the service

This direction must not be reversed just because the provider operates the
administration UI.

## 3. Price-list ownership versus management authority

The existing pricing invariant remains unchanged:

- owner_organization_id = customer
- customer_organization_id = customer
- provider_organization_id = service provider

Sprint 021 introduces a separate management authority:

- managed_by_organization_id = organization authorized to administer the price list

Existing customer-created price lists keep their existing behavior. Their
management authority is the customer organization.

During the Sprint 021 foundation phase, managed_by_organization_id remains
nullable for backward compatibility with legacy direct model writes and test
fixtures. Lifecycle write scope therefore accepts either the explicit manager or,
only when the manager is null, the original owner organization. Production rows
are backfilled from owner during migration.

The column can be tightened to NOT NULL after every supported write path
explicitly supplies management authority.

The existing creation endpoint therefore remains customer/source scoped. It is
not redefined to let a provider reverse relationship direction.

A later explicit provider-managed workflow will allow DRAYVIA to record an
agreed customer billing price list while preserving:

- customer ownership
- customer/source relationship direction
- DRAYVIA provider/target direction
- DRAYVIA management authority

## 4. Historical and current price lists

Price-list versions stay immutable, versioned, and effective-dated.

The existing database period guard prevents overlapping applicable versions for
the same commercial relationship.

Historical financial calculations are not rewritten when a newer price-list
version is created.

## 5. Odběratelé

The Odběratelé browser area will display organizations that have the active
customer/source role in a direct relationship where the active DRAYVIA
organization is the provider/target.

A selected odběratel must resolve to one concrete direct commercial
relationship before a fakturační ceník can be written.

## 6. Fakturační ceníky

The operational billing price list will contain:

- odběratel
- direct commercial relationship
- name
- currency
- valid from
- valid until
- delivered parcel rate
- redirected parcel rate
- undelivered parcel rate
- actual kilometre rate
- conditional pricing rules where applicable

## 7. Ceníky řidičů

Driver pricing remains a separate financial contract and will not be persisted
through the organization PriceList aggregate merely because the rate fields are
similar.

## 8. Fakturace, comparison, and profitability

The billing overview will aggregate controlled financial calculations by period
and commercial party.

Comparison will expose customer billing revenue versus driver compensation.

The first profitability level is gross margin, not final net profit.

## 9. S021-03B-R3 boundary

This unit separates price-list financial ownership from write-management
authority while preserving the existing customer creation API contract.

It does not:

- create an odběratel
- create a provider-managed price list
- create an invoice
- write to persistent PostgreSQL
- create a commit
- push the feature branch

The next unit will add the explicit Odběratelé/provider-managed price-list
workflow.

## 10. S021-03C customer billing administration

S021-03C adds the first explicit customer-facing finance administration
foundation.

### Customer role

Odběratel remains a role of an existing Organization in a direct commercial
relationship. No duplicate customer table is introduced.

For DRAYVIA customer billing:

- source organization = customer / odběratel
- target organization = DRAYVIA / provider
- relationship type remains subcontracting
- the customer browser reads incoming relationships of the active provider

The customer detail exposes identity, relationship validity, and the price lists
bound to that relationship.

### Provider-managed Fakturační ceník

The existing customer-created Price List endpoint is not redefined.

A separate provider-managed draft workflow is introduced for DRAYVIA billing
administration. It preserves:

- owner organization = customer
- customer organization = customer
- provider organization = DRAYVIA
- managed-by organization = DRAYVIA

This lets DRAYVIA record the agreed rates it uses to invoice an odběratel
without reversing the commercial relationship.

### API foundation

The following customer billing administration routes are introduced:

- GET /api/v1/customers
- GET /api/v1/customers/{relationship}
- POST /api/v1/customers/{relationship}/price-lists

Read routes require pricing.view and the write route requires pricing.manage.

### Browser foundation

Finance now contains five main areas:

1. Odběratelé
2. Ceníky
3. Fakturace
4. Srovnání
5. Ziskovost

Ceníky continue to contain two separate sub-areas:

- Fakturační ceníky
- Ceníky řidičů

The Fakturační ceník form is customer-specific and keeps the canonical pricing
codes:

- delivered_parcels
- redirected_parcels
- undelivered_parcels
- actual_km

S021-03C does not yet activate browser-side POST. The next unit will load the
real customer list into the form, select one verified relationship, enter the
first historical/current billing price list, and validate the payload before
any persistent business write.

## 11. S021-03E live customer Finance UI

The Finance customer surface is now connected to the existing authenticated MVP
API helper.

When the Finance page is rendered, the browser performs a read-only load from:

- GET /api/v1/customers

The response populates:

- the Odběratelé table,
- the Odběratel selector in Fakturační ceníky,
- the first customer detail automatically when a relationship is available.

Selecting a customer or pressing Detail loads:

- GET /api/v1/customers/{relationship}

The detail shows organization identity, relationship validity and the related
current/historical billing price-list aggregates.

The browser uses the existing MVP API helper and therefore keeps the existing
Bearer authentication and verified X-Organization-ID request context.

S021-03E intentionally does not activate browser-side billing-price-list POST.
No persistent billing business data is written by this unit. The provider-
managed POST endpoint remains available for the next controlled data-entry
unit after a real customer relationship and tariff values are verified.

## 12. S021-03L customer write API foundation

The customer administration surface now has an explicit provider-side write
endpoint:

- POST /api/v1/customers

The route requires the verified organization context and `pricing.manage`.

The request contains:

- `registration_number` — Czech IČO, exactly eight digits,
- `relationship_valid_from` — the start date of the business relationship.

The write model deliberately does not introduce a `customers` table and does
not add a new `customer` organization type.

Customer remains a business role derived from relationship direction:

- source organization = customer,
- target organization = the verified provider organization,
- relationship type = `subcontracting`.

For a previously unknown IČO, the existing ARES service is used and a canonical
Organization is created with the existing `carrier` archetype. For an IČO
already present in `organizations`, the existing Organization is reused and no
duplicate legal entity is created.

An active or suspended customer relationship for the same source and target is
rejected. An ended relationship does not permanently prevent a later new
relationship period.

This unit activates only the backend API foundation and automated tests. It
does not create a persistent customer and it does not enable the browser-side
customer creation form yet.

## 13. S021-03M browser customer creation

The Finance > Odběratelé surface now exposes provider-side customer creation
through the existing authenticated MVP API helper.

The user enters:

- Czech IČO (`registration_number`),
- relationship start date (`relationship_valid_from`).

The browser submits:

- POST /api/v1/customers

After successful creation, the Finance customer list is reloaded and the newly
created relationship detail is opened when the returned relationship identifier
is available.

The browser does not collect or persist a duplicate customer name/address model.
For a new IČO, canonical organization identity still comes from the backend ARES
workflow. For an already known IČO, the backend reuses the existing Organization.

This unit enables browser-side creation of the customer relationship only.
Browser-side billing-price-list POST remains disabled and is handled separately
so that customer onboarding and tariff definition remain distinct operations.

No persistent business data is written by the S021-03M automated validation.

## 14. S021-03N atomic provider-managed billing draft

The provider-managed customer billing endpoint now accepts the complete canonical
pricing-item set in the same request that creates the billing price list:

- POST /api/v1/customers/{relationship}/price-lists

The request contains the existing price-list metadata plus exactly one rate for
each canonical billing code:

- `delivered_parcels`
- `redirected_parcels`
- `undelivered_parcels`
- `actual_km`

The backend validates and normalizes the complete item set before the write
service starts its transaction. The provider-managed service then persists, in
one database transaction:

1. the PriceList aggregate,
2. draft version 1,
3. all four canonical PriceListItem rows.

Canonical item metadata remains server-owned. The service derives calculation
method, unit, quantity source, rounding policy and deterministic item position
from the existing Pricing model helpers. The browser supplies only code,
description and unit rate.

This provider-managed onboarding operation does not change the generic
organization-owner Price List creation endpoint and does not bypass the normal
draft lifecycle. Approval and activation remain explicit later operations.

Finance > Ceníky > Fakturační ceníky now exposes an `Uložit fakturační ceník`
action. The browser submits one provider-managed POST containing the selected
customer relationship, price-list metadata and all four rates. After success it
reloads the Finance customer data and opens the selected customer detail so the
new draft is visible immediately.

Automated validation uses SQLite memory and does not create persistent customer
billing business data.
