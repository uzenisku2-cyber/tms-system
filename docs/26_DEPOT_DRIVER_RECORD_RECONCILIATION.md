# Depot versus driver record reconciliation

## Purpose and boundary

Sprint 029 introduces a separate workspace for comparing an immutable depot
record with the independently maintained daily report of a driver. This work
does not extend the depot import workflow. Import remains complete as soon as
the exact depot values are stored and the depot driver labels are mapped in
bulk.

The operator-facing location for reconciliation is:

`Trasy -> Kontrola zapisu`

It is intentionally separate from `Importy`.

The independently maintained driver report remains the primary billing
source. The depot record is an immutable operational control source: billing
may proceed only after the relevant route values agree or an authorized,
audited correction resolves the difference. Depot financial calculations are
not a billing source for drivers or external carriers and are not imported.

## First read-only comparison slice

The first slice exposes:

`GET /api/v1/daily-reports/record-review/depot-driver/{batch}`

The endpoint requires authentication, an active organization context,
`daily-reports.view` and `daily-reports.review`. The batch must belong to the
active organization and have status `imported`. Draft, ready, cancelled and
foreign-organization batches are not reviewable.

Before returning any comparison, the service recalculates and verifies every
protected depot-row SHA-256 and the protected batch totals SHA-256. A failed
integrity check stops the comparison.

The endpoint is read-only. It creates no:

- daily report or daily-report version;
- pairing or reconciliation record;
- route allocation or split;
- depot-source revision; or
- audit event.

It also changes neither depot values nor driver-report values.

## Pairing key and classifications

The read-only candidate key is:

`organization_id + service_date + route_number_normalized`

The driver assigned during depot import must also equal the
`performed_by_driver_id` of the independently entered daily report.

Each source record receives exactly one classification:

- `matching` -- one daily report has the same driver and all comparable values
  match;
- `different` -- the key and driver match, but one or more comparable values
  differ;
- `missing_driver_record` -- no active daily report exists for the key;
- `driver_mismatch` -- a daily report exists for the key but belongs to another
  driver; or
- `not_comparable` -- the depot row is a no-run record, has no assigned driver,
  or the driver-side record set is structurally ambiguous.

Cancelled depot batches remain preserved for audit but are excluded from this
workspace.

## Comparable values

The comparison normalizes only storage representation, not business meaning:

- database times are compared as `HH:mm`;
- decimals are compared with two decimal places;
- blank text is compared as `null`, with line endings normalized; and
- parcel values remain integers.

Directly comparable values are departure and arrival times, loaded parcels,
address deliveries, pickup-point deliveries, customer rejections, actual and
planned kilometres, the basic customer-entered surcharge and operational
notes. Calculated financial rewards, rates and billing totals are neither
imported nor compared here.

The historical daily-report field `undelivered_parcels` has the current
operator meaning `customer rejected`. It is therefore compared with the
depot field `customer_rejected_parcels`.

The driver-side `Nedoruceno` value is derived as:

`loaded - delivered_to_address - delivered_to_pickup_point - customer_rejected`

and is compared with the depot's protected
`computed_not_delivered_parcels`. A workbook-provided `Nerozvezeno` or
equivalent value is ignored during import and is never returned by this review
endpoint.

## Filters and pagination

The endpoint supports:

- comparison status;
- assigned driver;
- service-date range;
- partial normalized route number; and
- page size up to 100.

The summary is calculated after the business filters and before the optional
comparison-status filter. This lets the future UI show stable overview counts
while an operator opens one status bucket.

The response also exposes additive `filter_options` containing the canonical
comparison statuses and the complete set of drivers assigned anywhere in the
selected immutable depot batch. This keeps the driver selector complete even
when a batch contains more rows than one result page.

## Operator UI

Sprint 030 makes the read-only workspace physically available in the MVP
application as the nested navigation entry:

`Trasy -> Kontrola zapisu`

The screen loads the latest imported depot batches from the existing depot
import index. After an operator selects a batch, it displays:

- stable summary counters for every comparison classification;
- filters for status, assigned driver, service-date range and partial route
  number;
- paginated comparison cards with the route, date, assigned driver and result;
  and
- an expandable three-column matrix showing each field as
  `Kontrolovane pole | Depo | Ridic`.

The UI remains read-only and calls only the two existing GET contracts used to
list imported batches and compare an exact batch. It contains no accept,
correction, split or depot-source revision control.

Kilometres are a presentation-only exception: `actual_km` and `planned_km`
are formatted in Czech locale with zero fractional digits and the `km` unit.
For example, `120.49` is displayed as `120 km` and `120.50` as `121 km`.
The response values and their exact two-decimal comparison semantics remain
unchanged, so rounding in the UI can never modify or authorize source data.

## Deliberately deferred mutations

This slice does not yet allow quick acceptance, driver correction, route
splitting or depot-source revision. Those actions require separate write
contracts and audit rules:

- a driver quick accept must create a new version of the driver's own report;
- a split must reference the immutable depot row and conserve every protected
  total across active parts;
- an ordinary driver must never edit depot-source values; and
- a depot-source revision must require superior authorization, a written
  reason and recorded depot approval.

Driver self-service visibility and mutation authorization will be added with
the corresponding write slice. The first endpoint is restricted to existing
review permission so the comparison semantics can be verified without
expanding driver access.
