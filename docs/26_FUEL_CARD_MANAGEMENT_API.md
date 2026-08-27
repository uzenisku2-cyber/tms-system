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

## Fuel transaction imports

- `POST /api/v1/fuel-imports` accepts a real ORLEN CSV or MOL XLSX source file.
- `GET /api/v1/fuel-imports` lists batches visible to the active organization.
- `GET /api/v1/fuel-imports/{batch}` returns preserved raw rows, normalized values and matching results.
- Reimport of the same provider file is rejected by organization/provider/SHA-256 identity.
- ORLEN receipt number is preserved as the provider transaction identifier. MOL receipt number is preserved, while deduplication uses a composite transaction fingerprint.
- Card identifiers remain strings and are normalized only by removing provider whitespace, including NBSP characters.
- Matching resolves the fuel-card assignment effective at transaction time; a current assignment never rewrites historical responsibility.
- Unknown cards, missing assignments and conflicting assignments remain visible with `match_status=review`.
