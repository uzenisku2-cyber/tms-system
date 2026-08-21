# Driver quality profile administration

## Purpose and boundary

Driver quality profiles define how the application evaluates the named driver
metric `partial_quality`. They are separate from:

- canonical raw route statistics;
- operational route-tolerance policies;
- driver and external-carrier price lists;
- financial bonus thresholds and rates.

The profile selects which canonical parcel counts contribute to the numerator.
The denominator is always `loaded_parcels`. A price list will later consume the
evaluated named metric; it must not rebuild this formula.

## Supported components

The first version supports these numerator components:

- `delivered_parcels`;
- `redirected_parcels`;
- `customer_rejected_parcels`.

The `processed_share` method evaluates:

`sum(selected numerator components) / loaded_parcels * 100`.

A `disabled` version explicitly suppresses quality evaluation. This makes it
possible to opt out at a more specific scope without falling back silently to
a broader formula.

Missing, negative or internally inconsistent parcel counts never produce an
applicable result. A zero denominator is complete source data but has no
percentage value.

## Profiles and versions

`driver_quality_profiles` is the organization-owned identity. Its code is
unique within the organization. Formula changes live in immutable historical
rows in `driver_quality_profile_versions`; selected sources live in
`driver_quality_profile_components`.

An activated formula begins on the first day of a month. A closed version ends
on the final day of a month. PostgreSQL excludes overlapping historical
versions for one profile. The write service applies the same chronological
rules on SQLite before persisting any lifecycle change.

Each profile has at most one draft version. Draft updates require the current
`lock_version`, and every successful update or activation increments it.
Activating a newer draft closes the previous active version on the final day
of the preceding month, preserving an auditable formula history.

## Effective assignment

`driver_quality_profile_bindings` assigns a profile over an effective monthly
period. Resolution order is:

1. `driver_assignment`;
2. `carrier_relationship`;
3. `organization`.

The driver scope references `driver_organization_assignments`, preserving the
meaning of an override when a driver later transfers. The carrier scope
references the outgoing subcontracting `organization_relationships` row, not a
bare organization id. The organization scope is the default.

Once a more-specific binding is found, an unavailable or not-yet-effective
profile version does not fall back to a broader binding. This prevents a
configuration mistake from silently applying a different business formula.

Replacing a binding in the same month updates that month atomically. Replacing
it in a later month closes the previous binding on the preceding month end.
Backdated insertion before the latest configured month is rejected.

## API and authorization

Read routes use `daily-reports.view`; mutation routes use
`daily-reports.review`. Every profile and binding query is constrained to the
verified organization context.

The administration API exposes:

- profile list and detail with complete version history;
- a dedicated list of organization, carrier-relationship and driver-assignment
  targets already constrained by the Statistics permission and supervisory
  boundary;
- atomic profile creation, draft-version copying and draft editing;
- revision-guarded activation on a monthly boundary;
- organization, carrier-relationship and driver-assignment bindings;
- explicit binding end dates that resume inheritance in a selected month;
- effective-profile preview for a service date.

A carrier binding accepts only an active outgoing subcontracting relationship
of the current organization. A driver-assignment binding additionally reuses
the existing supervisory-scope authorization, so possession of the review
permission alone cannot escape the caller's driver boundary. Foreign profile,
relationship and assignment identifiers are returned as not found.

The target endpoint belongs to the quality-profile API. The Statistics page
does not reuse Finance carrier lists or driver-administration endpoints, so a
user does not gain or require an unrelated permission merely to understand
the scopes they may configure.

## Statistics settings UI

The Statistics page contains two explicit tabs: the canonical raw overview
and **Nastavení dílčí kvality**. Opening Settings loads profiles, bindings and
permitted targets from the quality-profile API without changing the raw
overview query.

The UI supports the complete reviewed lifecycle:

- create a profile draft and select one or more canonical numerator sources;
- explicitly disable evaluation for a profile version;
- copy the latest formula into a new draft revision;
- edit the draft with its current optimistic `lock_version`;
- activate a revision from the first day of a selected month;
- assign an active profile at organization, carrier-relationship or
  driver-assignment scope;
- end a more-specific assignment at a monthly boundary to resume inheritance;
- preview the effective binding and version for a service date.

The browser shows a human-readable formula preview, but it never computes a
quality result. The denominator remains the canonical `loaded_parcels` value,
and all formula validation, resolution and evaluation stay on the server.

## Delivery slices

S027-02A added the persistence models, portable resolver, evaluator and
foundation tests. S027-02B added the read/write API, version lifecycle and
scope-safe binding administration. S027-03A adds the separate Statistics
settings tab and the scope-safe target endpoint used by that UI.

None of these slices evaluates historical overview rows or connects profiles
to pricing. Applying the selected profile to statistics and later consuming
the named metric in pricing require separate review gates.

The existing `daily_report_performance_policies` table remains unchanged and
continues to represent organization and route operational tolerances.
