# DRAYVIA Driver Price List Administration

## 1. Purpose

Driver compensation pricing is a separate financial contract from customer
billing pricing.

The existing `PriceList` aggregate remains the customer/provider billing
contract. Driver compensation must not reuse its
`organization_relationship_id` identity merely because both domains use the
same operational quantity fields.

## 2. Driver compensation identity

The dedicated driver price-list aggregate is anchored to
`driver_organization_assignment_id`.

This keeps compensation attached to the historical organizational assignment
under which the driver performed work. Management authority is stored
separately in `managed_by_organization_id`.

The foundation deliberately does not duplicate driver or organization identity
inside the price-list aggregate. Those identities are derived through the
canonical `DriverOrganizationAssignment`.

## 3. Separate persistence

The driver compensation contract uses three independent tables:

- `driver_price_lists`
- `driver_price_list_versions`
- `driver_price_list_items`

They do not reference the customer-billing `price_lists`,
`price_list_versions`, or `price_list_items` tables.

## 4. Shared operational metric taxonomy

Driver pricing uses the same four canonical operational quantity codes:

- `delivered_parcels`
- `redirected_parcels`
- `undelivered_parcels`
- `actual_km`

Sharing these codes does not merge the financial contracts. It only preserves
a common source-field vocabulary for later calculations.

## 5. Lifecycle and history

The driver price-list aggregate supports:

- aggregate status: draft, active, archived
- version status: draft, approved, active, replaced, expired
- effective dates
- optimistic `lock_version`
- historical versions
- non-overlapping applicable version periods within one driver price list on
  PostgreSQL

## 6. Draft administration API

The first administration endpoint creates an aggregate and its version 1 draft
atomically:

`POST /api/v1/driver-price-lists`

The route requires authenticated organization context and
`compensation.manage`.

The API accepts a canonical `driver_organization_assignment_id`. The
assignment is the compensation identity anchor. The current organization
context is persisted independently as `managed_by_organization_id`.

Authorization does not infer visibility from the assignment and does not use a
direct `OrganizationRelationship` query in Pricing. The write service delegates
driver and organization scope to `DriverSupervisoryAuthorizationService`.

For a subordinate carrier driver, both the supervisory scope and the active,
date-valid subcontracting relationship required by the shared authorization
layer must be present. A relationship by itself does not grant driver price-list
administration.

The initial draft contains the complete four-item canonical metric set. Version
1 starts with `draft` status and `lock_version = 1`. No approval or activation
is performed implicitly.

## 7. Draft-version administration

The administration API also supports explicit draft-version evolution:

- `POST /api/v1/driver-price-lists/{driverPriceList}/versions`
- `PUT /api/v1/driver-price-lists/{driverPriceList}/versions/{version}`

Creating a new draft version requires `expected_current_version`. Updating the
current draft requires `expected_lock_version`. Both operations require the
complete canonical four-item set and preserve the aggregate assignment identity
and managing-organization boundary.

Every write re-authorizes the price list through the shared driver supervisory
authorization layer. A different organization context cannot manage a price
list merely because it can resolve the same driver assignment.

## 8. Approval, activation, replacement, and expiration

Driver compensation versions follow the same temporal lifecycle semantics as
billing price-list versions while retaining driver-specific authorization.

- Approval is allowed only for the current draft version with a matching
  `expected_lock_version` and a complete canonical four-item set.
- Activation is allowed only for the current approved version with a matching
  lock and valid approval/effective-period metadata.
- Activating a replacement marks the previous active version as `replaced`.
  Its `valid_until` is shortened to the day before the replacement starts when
  the previous period would otherwise overlap.
- Expiration applies only to an active version. The requested `valid_until`
  cannot precede `valid_from`, cannot be in the future, and cannot extend an
  already bounded effective period.
- Lifecycle transitions validate `expected_lock_version` but do not increment
  it. Draft edits remain the operation that increments the optimistic lock.
- Expiring an active version preserves the aggregate status and
  `current_version`, matching the existing billing lifecycle contract.

Every lifecycle write re-authorizes the aggregate through the shared driver
supervisory authorization service. Billing relationship authorization is not
reused as a substitute for driver supervisory scope.

## 9. Read administration API

Driver compensation administration exposes read endpoints under
`compensation.view`:

- `GET /api/v1/driver-price-lists`
- `GET /api/v1/driver-price-lists/{driverPriceList}`
- `GET /api/v1/driver-price-lists/{driverPriceList}/versions`
- `GET /api/v1/driver-price-lists/{driverPriceList}/versions/{version}`

Read access is intentionally separate from `users.manage`. The shared driver
supervisory authorization layer resolves the currently visible
`DriverOrganizationAssignment` identifiers using the caller's explicit
supervisory scopes, active membership, and active subcontracting relationship
where cross-organization scope is involved. Pricing code does not duplicate
relationship authorization.

The read query additionally requires `managed_by_organization_id` to match the
current organization context. This preserves the compensation-management
ownership boundary while allowing the master carrier to read price lists it
manages for explicitly supervised subordinate drivers. A subordinate carrier
is restricted to price lists managed in its own organization context.

Index reads support status, currency, driver-assignment, sorting, direction,
and page-size filters. Version lists are returned newest first and include the
complete item set.

## 10. Deferred workflows

This increment still does not add:

- browser UI
- automatic selection of a driver price list for a daily report
- driver compensation calculation persistence
- comparison against customer billing revenue

Those workflows remain separate increments so the driver compensation contract
stays independent from the customer billing calculation lifecycle.
## 11. September 2026 MVP browser workflow

The first operational browser workflow is intentionally optimized for replacing
the manual monthly Excel process as quickly as possible.

Finance > Ceniky > Ceniky ridicu now lets an authorized administrator:

1. select a driver with a current organizational assignment;
2. enter the four canonical compensation rates:
   - `delivered_parcels`;
   - `redirected_parcels`;
   - `undelivered_parcels`;
   - `actual_km`;
3. set the effective start date and optional end date;
4. create the complete driver price-list draft through
   `POST /api/v1/driver-price-lists`;
5. approve version 1;
6. activate version 1;
7. immediately reload the visible Driver Price List overview.

The browser does not write directly to database tables and does not bypass
organization, supervisory-scope, permission, lifecycle or PostgreSQL rules.
All writes go through the existing `/api/v1/driver-price-lists` administration
API.

This is the minimum operational pricing workflow required before wiring driver
compensation calculations to daily reports for the September 2026 Excel
replacement milestone.

## 12. Multi-item conditional reward contract

Conditional driver surcharges may pay a rate for one or more operational
quantities. The ordered sources are stored in
`driver_price_list_conditional_rule_reward_components`.

For an `amount_per_unit` rule, the eventual compensation calculation is:

`sum(selected reward quantities) * matched band adjustment value`

The legacy `reward_quantity_source` column remains populated with the first
ordered source. It is a transitional compatibility field and is not the
authoritative representation for newly written driver rules.

Existing rules are backfilled as one reward component at position 1. Read and
copy operations fall back to the legacy column only when no component rows are
available. This makes deployment additive and preserves existing drafts and
history.

The request contract accepts both forms:

- legacy `reward_quantity_source` string;
- canonical `reward_quantity_sources` array with unique metric sources.

Request normalization converts the legacy string to a single-item canonical
array before the service layer runs. The payload guard rejects reward methods
whose quantity sources and target item are incompatible.

The browser presents standard quality and redirected-share surcharges as a
business sequence: checked performance, percentage basis, qualifying range,
selected payout quantities, and the rate. Technical reward-method selection is
reserved for a custom surcharge or deduction.

This contract prepares driver compensation calculation but does not silently
route driver rules through the existing customer-billing conditional engine.
That calculation and its immutable financial snapshots remain a separate
integration increment.
# R7 – společný kontrakt příplatků

Příplatky jsou volitelné. Ceník odběratele, externího dopravce i vlastního
řidiče lze uložit bez jediného podmíněného pravidla. Pokud pravidlo existuje,
musí mít úplnou podmínku, rozsah, alespoň jednu položku pro výplatu u metody
`amount_per_unit` a explicitní nezápornou sazbu; hodnota `0` je platná.

Standardní pravidla `delivery_quality` a `redirected_share` oddělují metriku
podmínky od položek výplaty. Po splnění pásma platí:

`conditional_amount = sum(reward_quantity_sources) * adjustment_value`

Metrika i odměna se nejprve sčítají ze zdrojových hodnot a teprve poté se
vyhodnocuje procento. Rozsah `per_route` používá jednu trasu. Měsíční rozsahy
agregují zapsané trasy v jednom kalendářním měsíci. Průběžný výsledek je
informativní; fakturovatelný výsledek nadále vyžaduje schválené nebo uzavřené
zdrojové výpočty.

# R8 – průvodce založením příplatku

Základní ceník je úplný a lze jej uložit i bez příplatku. Příplatek se ve všech
třech typech ceníků zakládá sedmikrokovým průvodcem: druh příplatku, zdroje
výpočtu, vyhodnocení za trasu nebo kalendářní měsíc, pásma, výplatní položky,
sazby a závěrečné srozumitelné shrnutí.

Výplatní položky `delivered_parcels` a `redirected_parcels` jsou přednastavené,
ale lze je jednotlivě změnit. Sazba `0` je platná. Pokud je vybraná obchodní
strana podle daňového profilu plátcem DPH, průvodce i souhrn označí sazbu jako
částku bez DPH. U vlastního zaměstnance se informace o DPH nezobrazuje.

Potvrzené příplatky se v ceníku zobrazují pouze v souhrnné tabulce. Úprava znovu
otevře stejného průvodce; kompletní skrytý formulář nadále vytváří kanonický R7
payload pro ukládací API.
