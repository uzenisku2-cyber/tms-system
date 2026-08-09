# TMS MVP / Pilot Launch

## 1. Purpose

The MVP / Pilot Launch phase prioritizes the shortest path from the existing
backend foundation to a system that can replace the current monthly Excel
workflow in real operations.

The decision rule is:

> Does this work help us replace the current monthly Excel faster?

Work that directly enables the pilot workflow has priority over speculative
future functionality.

## 2. Pilot delivery strategy

The pilot is validated with historical operational data first.

The working cycle is:

1. open TMS in the browser;
2. authenticate through the existing Identity API;
3. load or enter historical operational data;
4. perform the real daily-report workflow;
5. identify missing, incorrect or unnecessarily complex behaviour;
6. adjust the system;
7. repeat with a larger historical period.

Validation should progress from one day to several days, then one week and
finally one complete month.

## 3. Initial browser UI decision

For the first usable pilot, the browser UI is hosted directly by Laravel as a
Blade application shell.

Reasons:

- Laravel web routing and Blade already exist in the repository;
- the backend already exposes the required versioned API;
- authentication already returns a Sanctum bearer token;
- no separate React frontend contract is established in the current project;
- this approach requires no additional frontend runtime or deployment service;
- it gives the fastest path to a browser-visible pilot.

The pilot UI communicates with `/api/v1` and does not bypass backend domain,
authorization or organization rules.

This decision is intentionally scoped to the pilot. A later UI architecture
change remains possible after the real workflow has been validated.

## 4. Authentication

The pilot UI authenticates with:

- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/logout`

The bearer token is stored only in browser session storage for the active
browser tab/session. Passwords are never stored by the UI.

No credentials, bearer tokens, application keys or other secrets are committed
to the repository.

## 5. First usable screen

The first UI slice provides:

- login;
- authenticated application shell;
- current-user verification;
- daily-report list;
- daily-report operational metrics already calculated by the backend;
- explicit visibility of API or authorization errors;
- logout.

The next pilot slice should enable historical daily-report entry and then the
dispatcher review/approval flow.

## 6. MVP scope discipline

Until one historical month can be processed without recreating the main monthly
Excel, work should remain focused on the pilot flow.

Nonessential future administration, SaaS/rental concerns and speculative edge
cases are deferred unless they block the pilot.

## 7. Pilot organization context

Organization-scoped API calls send the `X-Organization-ID` header.

For the first internal pilot, the browser shell uses organization `1`, which is
the existing active master organization in the local pilot database.

This does not bypass authorization. `ResolveOrganizationContext` verifies that
the authenticated user has an active, currently valid membership in the
requested active organization. Requests for an organization outside the
user's valid membership are rejected by the backend.

The fixed pilot organization is temporary. Before multi-organization pilot use,
the UI must obtain the user's available organizations and provide an explicit
organization selector instead of relying on a fixed pilot value.

## 8. Carrier and driver pilot setup

The pilot creates operational master data before historical daily reports.

Order:

1. create an external carrier as an `Organization` of type `subcontractor`
2. connect it to the master organization through an active `subcontracting` relationship
3. create each driver's own User account and active organization membership
4. create the Driver profile linked to that User
5. test login and visibility under the driver's own account
6. manually enter that driver's historical daily reports
7. change the temporary pilot password before handing the account to the real driver

Historical pilot data is entered manually. No historical import workflow is part of this MVP path.

## 9. Master organization profile

Before creating real carrier and driver accounts, the pilot completes the master organization profile.

Stored fields:

- organization name
- registration number (IČO)
- VAT number (DIČ)
- street
- city
- postal code
- country code
- contact email
- contact phone

The master organization remains the root operational organization. Own employees are members of the master organization. External carriers are separate subcontractor organizations connected through active subcontracting relationships.

## 10. Own driver account provisioning

Own drivers are created under the master organization before entering historical reports.

One controlled creation operation creates:

- the User login identity
- an active `employee` OrganizationMembership in the master organization
- the Driver profile linked one-to-one to that User
- the organization-scoped `driver` role

The pilot `driver` role receives only:

- `daily-reports.view`
- `daily-reports.create`
- `daily-reports.update`
- `daily-reports.submit`

It intentionally does not receive:

- `users.manage`
- delegated report entry
- dispatcher review
- correction request
- approval
- closure
- pricing or financial permissions

A temporary password is entered only during account creation and is persisted only through the normal password hash. The pilot must validate driver login and visibility before historical report entry is expanded to multiple drivers.

The administrative `/carriers` browser page redirects a user back to `/app` when the administration API returns HTTP 403.

Historical reports remain manual pilot entry; no historical import is part of the launch workflow.

## 11. Minimal driver onboarding and employee profile separation

Pilot driver onboarding must not require licence metadata that is not needed to create the login identity.

Required for the initial pilot account:

- first name
- last name
- login email
- temporary password and confirmation

Optional at onboarding:

- phone
- driving licence number
- driving licence category

Future employee/personnel data is intentionally separated from the operational Driver profile.

Planned employee profile data may include:

- date of birth
- national personal identifier / birth number
- health insurer
- employment-specific identifiers and dates
- other personnel fields required by later HR processes

These personnel fields must not be mixed into normal driver-list responses or broad operational screens. They require a dedicated employee profile and narrower access control.

## 12. MVP edit-state UX convention

Persisted master data must visually read as already saved.

The MVP administration convention is:

- saved data is shown first as a compact summary
- a green `Upravit ...` action indicates an existing persisted record
- edit forms are hidden until the user explicitly requests editing
- create forms are hidden until the user explicitly selects `+ Přidat ...`
- after a successful save, the form closes and the persisted summary/list is shown again
- destructive or privileged actions must not be mixed into the green saved/edit state

This convention applies to the master organization, own drivers and external carriers and should be reused by later administration screens.

Driver onboarding remains minimal. Driving licence number and category are optional. Future birth date, national identifier, health insurer and other employment data belong to a separate restricted employee profile rather than the operational Driver record.

## 13. Existing account to Driver linking

A Driver profile is an operational capability of a User account, not a replacement identity.

When driver onboarding receives an email that already belongs to an active member of the verified organization:

- no duplicate User is created
- the existing password is not changed
- the existing OrganizationMembership is preserved, including its relationship type
- existing organization-scoped roles and permissions are preserved
- a Driver profile is added only when the User does not already have one
- the organization-scoped `driver` role is added without removing other roles

This supports users who legitimately perform multiple functions, for example administration, dispatching and driving, through one login identity.

When the email is new, the normal pilot flow creates a User, active employee membership, Driver profile and temporary password.

The UI resolves the email before submission and explicitly distinguishes:

- new account
- linkable existing organization member
- existing account that cannot be linked

Passwords are never requested or changed when linking an existing account.

## 14. Controlled driving-licence category

The pilot UI does not accept an arbitrary free-text driving-licence category.

The allowed values are controlled in one backend constant and validated server-side:

- AM
- A1
- A2
- A
- B1
- B
- C1
- C
- D1
- D
- B+E
- C1+E
- C+E
- D1+E
- D+E
- T

The create and edit screens use the same controlled selection.

The field remains optional for minimal driver onboarding.

The current Driver model stores one selected operational category. If later HR or compliance requirements need complete multi-category licence history, that belongs in the dedicated restricted employee/credential profile rather than being represented as uncontrolled text.

## 15. Driver organization assignment history

A Driver is a persistent person-level operational identity. The Driver is not owned permanently by one carrier.

Carrier affiliation is represented as time-bounded assignment history:

- `driver_id`
- `organization_id`
- `valid_from`
- `valid_until`
- optional end reason
- creating actor
- ending actor

A driver can therefore:

1. work for the master organization
2. end that cooperation
3. have no active cooperation for a period
4. later work for an external subcontractor
5. later return again through another new period

Historical periods are not overwritten.

The pilot prevents overlapping assignment periods for the same Driver and allows only one open-ended period at a time.

Daily-report ownership must later resolve the driver's organization using the assignment that is valid on the report service date. A later move to another carrier must never reclassify historical reports or financial history.

Organization membership and Driver assignment remain separate concepts. A User may retain administrative or representative membership in one organization while the Driver profile is operationally assigned to another carrier during a defined period.

## 16. Driver list status, filtering and inline history

The driver list is optimized for operational scanning:

- active drivers are shown before inactive drivers by default
- active drivers use a green visual state
- inactive drivers use a red visual state
- the list can be filtered to all, active, or inactive drivers
- Driver assignment history expands directly below the selected driver
- expanded history can be closed again
- only the selected driver's history is expanded
- cooperation periods are created and ended inside that driver's expanded section

The active/inactive UI state is derived from the assignment valid on the current date, not from permanently assigning the Driver to one organization.

A Driver with no currently valid organization assignment is shown as inactive even if historical or future assignment periods exist.

## 17. Driver search and login email changes

The driver list supports live search beside the active/inactive filter.

Search is case-insensitive and diacritic-insensitive and matches:

- driver name
- login email
- phone
- driving-licence number
- driving-licence category
- organizations present in the driver's assignment history

Search and the active/inactive filter are combined.

The driver's login email is editable from `Upravit řidiče`.

Changing that value updates both the persistent User login email and the Driver email in one transaction. The email must remain unique across User accounts. The UI explicitly warns that the changed value becomes the account's new login email.

## 18. First manual daily-report entry UI

The pilot daily-operation screen now supports progressive manual creation of a Daily Report draft.

The form is hidden until `+ Přidat denní výkaz` is selected.

The authenticated user's Driver profile is resolved through the existing self-scoped Driver API and is used as `performed_by_driver_id`.

The pilot form collects:

- service date
- route number
- delivered parcels
- redirected parcels
- undelivered parcels
- planned kilometres
- actual kilometres
- optional operational note

Because pilot historical data is entered manually through the TMS UI, `actual_km_source` is sent as `manual`.

Saving creates a draft only. Submit/review/approval actions remain separate workflow steps.

The historical-import navigation placeholder is removed because the pilot strategy is manual historical entry through the same UI that real users will operate.

Before ordinary driver accounts are released for real use, report-list visibility must receive a dedicated driver-isolation acceptance gate. The current pilot administrator may intentionally have broader organization visibility.

## 19. Versioned daily-report form configuration

Daily-report input requirements are configuration data rather than hard-coded UI order.

The highest effective organization in a subcontracting chain owns the form configuration. A subordinate carrier inherits that configuration but does not gain permission to modify it merely because an organization relationship exists.

Each configuration is versioned and has:

- `organization_id`
- monotonically increasing `version`
- `valid_from`
- optional `valid_until`
- ordered field definitions
- creator identity
- optional ending actor identity

`valid_until = null` means that the version remains valid without a predefined end date until it is explicitly ended or replaced.

When a new version starts after an existing open-ended version, the old version is automatically ended on the day before the new version begins.

Two versions for the same owning organization must not overlap.

The effective configuration is resolved by `service_date`, not by the current date. Historical daily reports therefore continue to use the rules that were valid on the historical service date.

For hierarchy resolution, an ended subcontracting relationship may still be effective for a historical service date when that date falls inside the relationship's `valid_from` / `valid_until` interval.

Initial configurable fields are:

- service date
- route number
- delivered parcels
- redirected parcels
- undelivered parcels
- planned kilometres
- actual kilometres
- operational notes

Service date and route number are system fields and remain visible and required.

The pilot management endpoints use the existing `users.manage` administration permission. A dedicated form-management permission may be introduced later when role administration is expanded.

This foundation does not yet change the `/app` entry form. The next integration slice will make the driver form load its order, visibility and required flags from the effective configuration for the selected service date.

## 20. Real daily-report field model

The pilot daily-report definition is expanded to the actual operational field set requested for rollout.

Planned first configuration validity:

- `valid_from = 2025-06-01`
- `valid_until = null`

No configuration row is created automatically by the implementation script. The first real version is created manually in the browser after validation.

Operational order:

1. Datum
2. Trasa č.
3. Čas odjezdu
4. Čas příjezdu
5. Trasa naměřená
6. Trasa plánovaná
7. Naloženo ks
8. Doručeno na adresu
9. Doručeno na výdejní místo
10. Odmítnuté ks
11. Příplatek
12. Poznámka

Canonical backend mapping intentionally preserves existing pricing quantities:

- `Doručeno na adresu` -> `delivered_parcels`
- `Doručeno na výdejní místo` -> `redirected_parcels`
- `Odmítnuté ks` -> `undelivered_parcels`
- `Trasa naměřená` -> `actual_km`
- `Trasa plánovaná` -> `planned_km`

The three parcel pricing quantities remain separate. They are not silently summed or reclassified.

New operational persistence fields:

- `departure_time`
- `arrival_time`
- `loaded_parcels`
- `surcharge_amount`

`Příplatek` is a CZK amount. Missing surcharge input normalizes to `0.00`. Negative surcharge amounts are rejected.

When `surcharge_amount > 0`, `operational_notes` is mandatory. This rule is protected both by the persistence service and by the PostgreSQL database constraint.

`Příplatek` and `Poznámka` are not globally required configuration fields. The note becomes conditionally required only when a positive surcharge is entered.

The historical snapshot builder includes the new fields while remaining backward compatible with older in-memory snapshot fixtures by applying neutral defaults only to the newly introduced fields.

The driver `/app` form is not yet switched to the effective configuration in this slice. That is the next integration step.

## 21. Editable and extensible daily-report configuration

S020-04D extends the versioned daily-report configuration without mutating historical configuration rows.

Editing semantics:

- `Upravit / vytvořit novou verzi` copies an existing version into the editor.
- The source version is never overwritten.
- The operator must select a new `valid_from`.
- Saving uses the existing version-creation endpoint.
- Existing automatic validity replacement remains authoritative.

The twelve canonical operational fields remain the permanent baseline.

Two locked system fields remain always visible and required:

- `service_date`
- `route_number`

Additional custom fields may be added to a configuration version. Custom field metadata is stored inside the versioned `fields` JSON definition and therefore does not require a database migration for every new field.

Supported custom field types:

- `number`
- `text`
- `time`
- `money`
- `boolean`

Custom fields have stable generated `custom_*` keys. A custom field can be renamed, have its type changed, be reordered, hidden, made required, or removed while preparing a new version. Historical versions retain their original field definitions.

The configuration supports 12 to 40 fields total.

S020-04D changes only the configuration definition and settings UI. Persistence of custom field values on actual daily reports is intentionally deferred to the effective-form integration slice. No existing real configuration row is changed by the implementation script.

## 22. Effective daily-report form write foundation

S020-04E1 binds newly created Daily Reports to the form configuration that is effective for their `service_date`.

New report storage contains:

- nullable `daily_report_form_configuration_id`,
- versioned `custom_field_values` JSON.

The configuration identifier is server-controlled. Clients cannot select it directly.

When a configuration is effective for the service date, the server enforces:

- configured visibility,
- configured required fields,
- known custom field keys,
- custom value types,
- stable normalized custom values.

If the controlling organization already has configuration history but no version is effective for the requested date, creation is rejected.

A temporary compatibility path remains for organizations with no configuration history at all. This preserves pre-configuration test and legacy module contracts during the pilot rollout. The real pilot organization already has configuration history from 2025-06-01, so its writes always use the effective configuration contract.

Submission completeness for a configuration-bound report is no longer based on the legacy hard-coded `completion_confirmed_at` field. It is derived from the required fields of the bound configuration. Legacy unbound reports retain the original completeness rule.

The configuration binding and custom values are included in the immutable Daily Report snapshot.

S020-04E1 does not yet replace the static browser form. Browser rendering from the effective configuration is the next slice.

## 23. Dynamic effective Daily Report browser form

S020-04E2 replaces the fixed pilot Daily Report entry fields with a browser form generated from the effective configuration for the selected service date.

The date selector remains permanently visible because `service_date` determines which version is effective.

After a date is selected, the browser loads:

`GET /api/v1/daily-report-form/effective?service_date=YYYY-MM-DD`

The returned configuration controls:

- field order,
- field labels,
- visibility,
- browser-required state,
- canonical field control type,
- custom field control type.

Canonical operational values remain ordinary Daily Report attributes. Custom values are submitted through `custom_field_values`.

`actual_km_source=manual` remains a hidden technical value and is sent only when the configured form contains an entered `actual_km`.

The Save button remains disabled until an effective configuration is loaded. A date with no effective version shows a blocking message and cannot be saved.

The browser does not select or submit `daily_report_form_configuration_id`; S020-04E1 continues to resolve and bind that identifier server-side.

S020-04E2 does not create a Daily Report automatically. The first real historical report remains a manual pilot action after the remaining organization-assignment and actor-visibility gates are completed.

## 24. External driver identifier for depot matching

S020-04E2C adds a partner-facing identifier to Driver.

The database attribute is named `external_driver_id`. The pilot UI labels it `ID řidiče (Zásilkovna)` because the current depot source is Zásilkovna.

`drivers.id` remains the immutable internal TMS primary key and must never be repurposed as a depot or partner identifier.

`external_driver_id` is:

- nullable for existing and not-yet-mapped drivers,
- editable from driver administration,
- accepted during new driver creation,
- restricted to 1–32 digits in the current Zásilkovna pilot,
- unique across Driver records,
- exposed in the own-driver API resource,
- shown in the driver administration list,
- included in live driver search.

The value is stored as a string even though the current source is numeric. This preserves the exact external identifier without converting it into an internal sequence.

No real driver receives an identifier automatically during this migration. Existing drivers remain `NULL` until an authorized administrator enters the correct depot identifier manually.

The next Route Workflow UX slice may use this identifier, together with the driver name, instead of exposing the internal TMS `performed_by_driver_id` in the route overview.

## 25. Route workflow UX and editable driver entries

S020-04E2D changes the pilot user language from a day-centric report screen to a route-centric operational screen.

Business terminology:

- navigation: `Trasy`
- create action: `+ Zapsat trasu`
- list: `Zapsané trasy`

One driver may therefore have multiple independently stored routes on the same service date.

The browser presents the existing controlled Daily Report workflow using operational Czech labels:

- `draft` → `Zapsáno řidičem`
- `submitted` → `Čeká na schválení`
- `under_review` → `Čeká na schválení`
- `correction_requested` → `Vyžaduje opravu`
- `corrected` → `Opraveno řidičem`
- `approved` → `Schváleno`
- `closed` → `Uzavřeno`

The database and workflow status constants are not renamed. UI labels are presentation only.

Editing rules remain aligned with the Daily Operations workflow:

- `draft` may be edited by the authorized original entry actor,
- `correction_requested` may be corrected by an authorized correction actor,
- submitted, under-review, approved and closed records remain locked for ordinary driver editing,
- a `corrected` record exposes an explicit resubmit action.

The pilot browser uses optimistic concurrency through `expected_version`.

The service date is intentionally locked during ordinary browser editing in this slice because it determines the historically bound form configuration. A future controlled date-correction operation must update the configuration binding explicitly rather than silently changing historical semantics.

The route overview displays `ID řidiče (Zásilkovna)` when available and falls back to the driver name or internal identifier only when the external identifier has not yet been entered.

The current route workflow does not yet claim that depot import is implemented. Future depot reconciliation will be responsible for moving matched driver-entered routes into the review flow according to the import/reconciliation contract.

## 26. Route detail edit validation normalization

S020-04E2D1 fixes the first real pilot edit regression.

PostgreSQL `time` values are stored and returned internally with seconds (`HH:MM:SS`), while the Daily Report mutation request contract intentionally accepts `H:i`.

The API resource now normalizes operational route times to `HH:MM`. The browser also defensively truncates an edit-prefill time value to five characters before submission.

This keeps create and edit payloads on one public contract and prevents an unchanged stored time from making an otherwise valid route edit fail validation.

The pilot UI also replaces the remaining create-panel wording `Nový denní výkaz` with `Detail trasy`.

Validation error rendering now prefers Laravel field-level errors over the generic `Validation failed` message, so future input errors are visible to the operator.

No existing route data is rewritten by this normalization.

## 27. Whole-kilometre route entry

S020-04E2D2 aligns route-entry kilometre controls with the pilot business workflow.

For the canonical route fields `actual_km` and `planned_km`:

- the browser number input uses `step=1`,
- the intended operator input is whole kilometres,
- existing values such as `231.00` are shown as `231` during edit,
- the overview continues to show whole kilometres.

The surcharge field remains a decimal money input with `step=0.01`.

The database/API numeric storage contract is intentionally not narrowed in this slice. This keeps backward compatibility for historical or future integration data. The pilot browser itself prevents fractional kilometre entry.

A legacy non-integer kilometre value is not silently rounded during edit prefill. Only values that are already mathematically whole are displayed without trailing decimal zeroes.

No existing route values are rewritten by this presentation change.

## 28. Parcel balance and derived undelivered count

S020-04E2D5 clarifies parcel outcome semantics for the pilot.

The existing canonical field `undelivered_parcels` remains unchanged at the database/API compatibility level, but the operator-facing meaning is now `Odmítnuto zákazníkem`.

A separate value `Nedoručeno` is derived and is not stored as an independent editable field:

`Nedoručeno = Naloženo - Doručeno na adresu - Doručeno na výdejní místo - Odmítnuto zákazníkem`

The API exposes the derived value as:

`calculated.not_delivered_parcels`

A negative result is always an input inconsistency. The browser shows an immediate red error and blocks route saving.

The Store, Update and Correction request contracts also return a field-level validation error when all four parcel inputs are supplied and their balance is negative.

PostgreSQL adds a final persistence invariant preventing a fully populated negative parcel balance from being stored.

Historical Daily Report form configuration records are not mutated by this terminology change.

## 29. Route history navigation and filters

S020-04E2E turns the route list into a fast operational history for drivers.

The route overview now has four navigation layers:

1. year buttons;
2. month buttons;
3. quick periods;
4. business-status buttons.

Year and month buttons are metadata-driven and are shown only when at least one route exists in that period. Years and months are ordered newest first.

The initial route-history period is the current month when it contains route data. Otherwise the latest month containing route data is selected.

Quick periods are:

- last 7 days;
- current month;
- previous month;
- current year;
- custom date range.

Predefined quick-period buttons with zero routes are omitted. Custom date range remains available.

Business status filtering uses a dedicated server-side `status_group` contract:

- `written` = draft;
- `waiting` = submitted + under_review;
- `correction` = correction_requested;
- `corrected` = corrected;
- `approved` = approved;
- `closed` = closed.

The UI therefore shows one `Čeká na schválení` button even though the workflow has two internal waiting states.

Status buttons display counts for the selected period and zero-count status buttons are omitted. `Vše` remains available.

Filtering is performed by the Daily Reports API through `service_date_from`, `service_date_to` and `status_group`; it is not limited to the currently loaded browser rows.

`GET /api/v1/daily-reports` now also returns navigation metadata containing available years, available months, status counts and quick-period counts.

The historical stored form configuration is not rewritten when the operator-facing label `Odmítnuto zákazníkem` is used. The browser applies that label to the canonical `undelivered_parcels` field.

This unit does not change route records or workflow states.

## 30. Workflow colors and Czech date presentation

S020-04E2F standardizes the visual language of route history.

Workflow colors:

- driver-written, corrected, approved and closed states use green;
- waiting-for-approval uses amber;
- correction-required uses red.

The same semantic colors are used for status badges and quick status-filter buttons.

General route-history navigation buttons for available years, months and quick periods use a green operational treatment instead of the previous white treatment. The active selection uses a stronger green border/background.

Route action buttons follow the same principle:

- `Upravit trasu` is green;
- `Opravit trasu` is red;
- `Odeslat ke schválení` after correction is green.

Dates stored and exchanged through the API remain ISO `YYYY-MM-DD`. Browser presentation in route history uses the Czech `DD.MM.YYYY` format. Custom-period summaries use the same Czech display format.

No database values, route records or API date contracts are changed by this unit.

## S020-04E3D4 – Configurable route performance overview

The route overview displays parcel balance in the business order:

`Naloženo → Doručeno na adresu → Výdejní místo → Odmítnuto zákazníkem → Nedoručeno`.

`Nedoručeno` remains derived from the parcel balance and is not stored as a second source field.

Percentage KPIs use `loaded_parcels` as the denominator. The route overview loads the operational performance policy and evaluates the current row against the effective organization / route thresholds.

Normal values remain visually neutral. A value outside its configured limit uses an orange warning. A value at least five percentage points beyond the configured limit uses a red critical presentation. The five-point band is presentation severity only; the configured threshold remains the business tolerance.

The kilometre attention color in this MVP overview is evaluated against the configurable `kilometre_deviation_max_percent` rather than the historical fixed browser presentation.

The custom-period action is intentionally neutral: white background, dark text, grey border and a calendar cue. When active it retains the white background and only receives a subtle green outline.

The dedicated `/performance-settings` page lets an authorized reviewer maintain organization defaults and per-route overrides. Blank route-override values inherit from the organization defaults.

Operational performance policy remains separate from Pricing. Percentage-based financial surcharge rules are not calculated by this UI unit.

Historical route facts, report versions and workflow states are not modified by changing a performance threshold.

The route-history UI provides an explicit `Zrušit filtry` action. It clears year/month/custom-period/status state and shows the complete route history available to the current organization scope.

The Daily Reports API keeps its bounded pagination contract (`per_page=100` in the MVP client). The browser UI transparently follows every returned page and merges the matching rows before rendering. The user therefore sees one complete list without manual pagination controls.

## S020-04E3D5 – Route-list readability polish

The recorded-route table is optimized for fast visual checking:

- every recorded value is bold,
- every column is centered except the first `Datum` column,
- the `Datum` header and date values remain left aligned,
- the route edit action is labelled `Upravit zapsané údaje`,
- correction editing is labelled `Opravit zapsané údaje`.

The global route-filter reset is always visible in the top filter toolbar as
`✕ Zrušit filtry`. It is disabled only when no filter is active. Activating it
clears year, month, quick/custom period and workflow-status filters and returns
the user to the complete route list.

The complete-route loader introduced in S020-04E3D4 remains unchanged: API
pagination stays bounded while the browser transparently loads every matching
page before rendering.

## S020-04E3D6 – Driver-safe performance configuration placement

The daily-route overview is a driver-operational screen. It may display the
effective performance evaluation and its colors, but it must not expose
controls for changing operational performance thresholds.

The global `Zrušit filtry` action is placed after the complete filter block,
below the status filter. It remains visible and only becomes disabled when no
route filter is active.

The premature standalone `/performance-settings` UI route is removed. The
underlying performance-policy API remains available because route rows need
read-only effective thresholds for color evaluation.

Performance-policy writes remain protected by the existing organization-scoped
`daily-reports.review` permission. A driver-only account must not receive a
configuration editor.

The final management UI belongs in future driver management / driver settings,
not in the driver's own daily-route screen. Access must be based on explicit
supervisory responsibility plus permission scope. Organizational hierarchy or
the fact that one user is also a driver must never automatically grant this
management capability.

The existing organization default and route-override policy model is retained
until the driver-management UI and explicit supervisory-scope model are
implemented. No driver-specific or cross-organization inheritance is invented
by this correction block.
