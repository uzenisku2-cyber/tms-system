# ADR-025: Unified price-list administration

- Status: Accepted
- Sprint: S025
- Branch: `feature/v0.8.5-unified-price-list-administration`
- Decision date: 2026-08-17

## Context

Finance currently contains a complete administration workflow for customer
billing price lists. Driver price lists use a separate domain aggregate and
provide only a basic create-and-activate interface. External carrier price-list
administration is not yet exposed in Finance.

The required Finance navigation contains three equivalent administration areas:

1. Billing price lists
2. Driver price lists
3. External carrier price lists

All three areas must provide the same user experience and lifecycle controls.

## Decision

The application will expose one shared price-list administration experience
with three domain adapters. Existing aggregates will remain separate.

### Billing price lists

Billing price lists continue to use the generic `PriceList` aggregate in the
provider-managed relationship direction.

They retain:

- base rates;
- unlimited conditional rules;
- formula components;
- threshold and reward bands;
- draft editing;
- optimistic locking;
- version history;
- approval, activation and expiration;
- organization and permission scoping.

### Driver price lists

Driver price lists continue to use the `DriverPriceList` aggregate targeted at
a driver organization assignment.

They gain full functional parity:

- four canonical base rates;
- unlimited conditional bonuses and deductions;
- quantity and percentage metrics;
- multiple numerator and denominator components;
- per-route and monthly evaluation;
- fixed amount, amount-per-unit and percentage rewards;
- threshold bands with independently inclusive boundaries;
- draft editing with optimistic locking;
- version history;
- explicit approval, activation and expiration.

Driver price-list creation must no longer approve and activate the first version
automatically. Creation produces a draft. Lifecycle actions remain explicit.

### External carrier price lists

External carrier price lists use the existing generic `PriceList` aggregate in
the customer-managed relationship direction.

The target is the active subcontractor relationship from the master
organization to the external carrier organization.

They provide the same capabilities as billing price lists:

- complete atomic draft creation;
- base rates;
- conditional rules and bands;
- draft replacement;
- history;
- approval, activation and expiration;
- relationship and organization scoping.

No third price-list aggregate is introduced.

## Driver conditional-rule persistence

Driver conditional rules must not reference generic price-list versions.

Three dedicated tables will mirror the proven generic rule structure:

- `driver_price_list_conditional_rules`
- `driver_price_list_conditional_rule_metric_components`
- `driver_price_list_conditional_bands`

Every rule belongs to exactly one `driver_price_list_version`.

Components and bands belong to exactly one driver conditional rule. Foreign
keys use cascading deletion so replacement of a draft version remains atomic.

The existing generic conditional-rule behavior is the semantic source of truth.
Shared validation and evaluation code should be extracted where practical.
Existing generic tables and active billing records must not be rewritten by
this sprint.

## Shared administration UI

Finance will contain these tabs:

1. `Fakturační ceníky`
2. `Ceníky řidičů`
3. `Ceníky externích dopravců`

Every tab provides:

- summary counts;
- filters `Vše`, `Aktivní`, `Koncepty`, `Historie`;
- refresh;
- list view;
- localized status and validity;
- detail view;
- editable draft;
- base-rate table;
- conditional-rule editor;
- version history;
- lifecycle actions allowed by the domain and permissions.

The status label is `Aktivní`. It does not imply that the validity range
contains the currently selected date. Historical validity is displayed
separately.

Shared rendering and form builders must be reused. API calls and authorization
remain domain-specific.

## Authorization

- Billing and external carrier price lists use `pricing.view` and
  `pricing.manage`.
- Driver price lists use compensation permissions and existing supervisory
  assignment scope.
- A user must never retrieve or mutate a price list outside the active
  organization context.
- Internal organization identifiers must not be exposed unnecessarily.

## Atomicity

Creation and draft updates always persist the complete version tree in one
database transaction:

- version metadata;
- four canonical items;
- conditional rules;
- formula components;
- bands.

Any validation or persistence failure rolls back the complete write.

Lifecycle transitions use the existing domain services. Controllers and browser
code must not update lifecycle columns directly.

## Migration safety

New migrations only add the three driver conditional-rule tables and required
indexes or foreign keys.

The sprint must not:

- alter existing active billing price-list content;
- change persistent lifecycle state during development;
- run tests against the persistent PostgreSQL instance;
- create a local sprint `.env`;
- rewrite existing generic conditional-rule records.

Development tests use controlled SQLite environments. PostgreSQL compatibility
is verified only on a disposable container and network that are removed after
the gate. Browser verification uses an isolated database clone.

## Implementation sequence

1. Add driver conditional-rule schema and model relationships.
2. Reuse or extract conditional-rule payload validation.
3. Extend driver requests, resources and write service atomically.
4. Add driver rule persistence and lifecycle tests.
5. Add atomic complete-draft creation for external carrier price lists.
6. Add route, authorization and relationship-scope tests.
7. Introduce shared administration UI primitives.
8. Replace the current driver create-and-auto-activate UI.
9. Add external carrier administration.
10. Run static, feature, browser and persistent-state gates.

## Acceptance criteria

The sprint is complete only when:

- all three tabs have equivalent administration behavior;
- driver rules survive create, edit, version copy and read operations;
- external carrier lists are scoped to their subcontractor relationship;
- stale draft writes fail atomically;
- non-draft versions cannot be edited;
- lifecycle transitions require the correct permission;
- rendered JavaScript passes an independent syntax check;
- targeted PHP lint, Pint, PHPStan and tests pass;
- isolated browser creation and editing pass;
- the persistent database fingerprint remains unchanged during development.
