# Driver performance overview

## Purpose

The driver performance overview publishes canonical raw route facts and
database-portable aggregations. It is the source layer for a later configurable
quality profile and for price-list bonus rules. This slice does not decide
whether a route is financially eligible and does not apply a quality profile.

## Canonical source

The first implementation reads active, non-deleted `daily_reports` records in
the current organization context. It deliberately includes every workflow
status, including historical `authorized_import` drafts. The immutable
`daily_reports.organization_id` is the historical ownership boundary.

The endpoint is:

`GET /api/v1/daily-reports/performance-overview`

It requires the existing organization context and `daily-reports.view`
permission. Supported filters are `performed_by_driver_id`, historical carrier
scope, named quick period, inclusive custom date range, and
`group_by=day|month`.

## Raw metric contract

The historical column `undelivered_parcels` is exposed as
`customer_rejected_parcels` because that is its business meaning.

- processed parcels = delivered + redirected + customer rejected
- not delivered parcels = loaded - processed
- delivered share = delivered / loaded * 100
- redirected share = redirected / loaded * 100
- customer-rejected share = customer rejected / loaded * 100
- not-delivered share = not delivered / loaded * 100
- kilometre difference = actual - planned

These are objective source statistics, not an evaluation of the future
configurable quality profile. The UI labels the raw processed share as
`Dílčí kvalita`, with its formula displayed explicitly. Percentages for a
driver, day, month, or custom interval are calculated from aggregated raw
counts. Daily percentages are never averaged. A zero denominator returns
`null` rather than a misleading zero percent.

## Multiple routes per driver and day

A driver may perform multiple distinct routes on the same service date. Every
route contributes to `route_count` and to raw metric sums. The date contributes
only once to `work_day_count`. Daily and monthly shares are calculated only
after all applicable routes have been summed.

## Configurable quality profile

The effective partial-quality formula will be introduced as a separate,
versioned profile. Profile assignment will inherit in this order:

1. driver override;
2. external-carrier relationship default;
3. organization default.

The driver and carrier forms will select or inherit a profile; they will not
store formula components directly. Monthly profile changes must become
effective on the first day of a month so that one monthly result never mixes
two formula definitions. Price lists will reference the evaluated named metric
and threshold instead of rebuilding its formula.

The existing `daily_report_performance_policies` foundation remains dedicated
to operational threshold tolerances. It must not be overloaded with historical
formula assignments without a separate versioned profile contract.

## Statistics UI and filter contract

The Statistics page reads only the canonical
`/api/v1/daily-reports/performance-overview` endpoint. It does not download
individual daily reports or repeat aggregation formulas in browser JavaScript.

The initial view requests the current calendar month. The API publishes quick
periods for current month, previous month, current year, previous year, the
last twelve calendar months, and all history. A quick period is returned and
rendered only when at least one route exists after the selected driver and
carrier filters. Custom inclusive dates and day/month grouping remain
available.

Driver carrier attribution is resolved from the
`driver_organization_assignments` row effective on each route's
`service_date`. The current organization is presented as `Vlastní řidiči`;
another organization is presented by its historical name. A route without a
matching historical assignment remains explicitly filterable as
`Bez historicky doloženého dopravce` and is never guessed.

The page renders organization totals, one row per driver, a time series and
explicit parcel, kilometre and carrier-attribution completeness counts. All
kilometre values are rounded to whole kilometres for display only; the API and
database preserve their existing precision. Quality profile evaluation and
financial bonus eligibility remain unapplied and visibly separate.

## Completeness and safety

The response publishes complete and incomplete route counts separately for
parcel and kilometre data. Only complete rows contribute to the corresponding
metric sums. Soft-deleted records are excluded by the model scope.

No migration, materialized statistic, or financial policy is introduced by
this slice. Cross-organization supervisory consolidation remains an explicit
follow-up boundary; the first endpoint matches the existing Daily Reports
organization scope.
