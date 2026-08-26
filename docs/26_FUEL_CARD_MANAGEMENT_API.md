# Fuel Card Management API v1.0

## Scope

The module keeps card identity, owner, responsible organization, driver, vehicle, assignment history, settlement policy and imported transactions as separate concepts.

## Authorization

- Read operations require `compensation.view` and return cards owned by the active organization or assigned to it.
- Mutation operations require `users.manage`.
- A card may be mutated only by its owner organization.
- Responsible organizations and drivers are resolved through the existing supervisory authorization hierarchy; inaccessible targets return 404.

## Lifecycle and audit

- Card states: `active`, `blocked`, `expired`, `retired`.
- Status mutations use optimistic `lock_version` control.
- Active assignment periods for one card cannot overlap.
- Creation, status change, assignment start/end and settlement policy creation write an audit event in the same database transaction.

## Settlement safeguards

- Driver settlement requires `vat_mode=not_applicable`.
- Carrier settlement requires `vat_mode=counterparty_tax_profile`; the eventual calculation must resolve the counterparty's historically effective tax profile.
- `amount_basis` (`net` or `gross`) and `discount_beneficiary` are independent historical policy attributes.

## Routes

- `GET /api/v1/fuel-cards`
- `GET /api/v1/fuel-cards/{fuelCard}`
- `POST /api/v1/fuel-cards`
- `PATCH /api/v1/fuel-cards/{fuelCard}/status`
- `POST /api/v1/fuel-cards/{fuelCard}/assignments`
- `POST /api/v1/fuel-cards/{fuelCard}/assignments/{assignment}/end`
- `POST /api/v1/fuel-cards/{fuelCard}/settlement-policies`
