# Depot record import foundation

## Read-only inspection slice

Sprint 028 starts with a read-only workbook inspection and preview. It does
not create daily reports, store the uploaded workbook, change driver
assignments, or write an import profile.

The API endpoints are:

- `POST /api/v1/daily-reports/depot-imports/inspect`
- `POST /api/v1/daily-reports/depot-imports/preview`

Both endpoints require authentication, an active organization context and
`daily-reports.view`. They are available only when that context identifies an
active master organization.

## Workbook safety contract

The implementation uses PHP's native `ZipArchive` and DOM support already
present in the application image. It adds no Composer dependency and never
extracts the workbook to a filesystem directory.

The reader:

- accepts only a structurally valid OOXML workbook;
- limits archive entries, expanded size, XML size, visible sheets, rows and
  columns;
- rejects encrypted content, macro workbooks, unsafe paths, DTD declarations
  and suspicious compression ratios;
- reads cell values without evaluating or returning formula text;
- marks mapped formula cells as invalid import inputs;
- ignores hidden sheets when selecting an import table; and
- computes a SHA-256 fingerprint for the source and the detected schema.

The source upload is temporary request data. The preview response explicitly
reports that the source was not stored and that no write was performed.

## Adaptive schema detection

The detector scans the first 25 rows of every visible sheet and evaluates
one-, two- and three-row header candidates. It maps columns by normalized
header meaning instead of a fixed sheet name, row number or column letter.

Required meanings are:

- service date;
- route number;
- carrier;
- source driver name;
- loaded parcels; and
- parcels delivered to an address.

Optional recognized meanings include year, month, notes, departure and
arrival times, actual and planned kilometres, parcels delivered to a pickup
point, customer-rejected parcels and the basic customer-entered surcharge.

The import contract is an explicit operational whitelist. It does not map
calculated rewards, rates, quality bonuses, fuel bonuses, monthly billing
totals, total costs or other financial calculation columns. A header such as
`Příplatek` or `Příplatky` is the basic customer-entered surcharge; headers
such as `Příplatek palivo Kč`, `Příplatek kvalita Kč` and `Náklady celkem Kč`
remain outside the import contract.

The workbook's own `Nerozvezeno`, `Nedoručeno` or equivalent column is also
outside the import contract. Its position and value do not affect schema
detection, row validity, persistence or reconciliation.

Ambiguous duplicate mappings or equally likely import tables are rejected.
They are never guessed.

## Carrier confirmation

The default carrier alias is the current master organization's configured
name. The user must inspect the workbook and explicitly confirm or edit this
alias before the preview endpoint accepts the request.

Matching is exact after deterministic normalization of case, whitespace,
punctuation and diacritics. It is not fuzzy. This intentionally makes
`Kökörčený` and `Kökörčeny` equivalent while preventing a merely similar
carrier name from being included.

Every carrier spelling and row count remains visible in inspection and every
matched spelling remains visible in preview.

## Preview controls

For matched rows the server parses and validates the operational source
values. Blank sparse parcel outcomes are treated as zero. The server derives
not-delivered parcels as:

`loaded - delivered_to_address - delivered_to_pickup_point - customer_rejected`

The calculation is authoritative inside the application. A negative balance,
invalid number, invalid date, calendar-column mismatch, duplicate date/route
key or formula in a mapped source field blocks the affected row. A positive
basic surcharge additionally requires a non-empty operational note explaining
the surcharge.

A row with route identity and a note but no operational values is classified
as `no_run`; it is not represented as a zero daily report.

The preview also lists source driver labels without fuzzy matching and lists
active driver assignments belonging to the master organization during the
detected period. This prepares explicit alias mapping in the next slice.

## Persistent depot import and driver-name mapping

After the operator confirms the alias and the preview contains at least one
ready route and no invalid matched row, an authorized dispatcher may create a
persistent import draft with:

- `POST /api/v1/daily-reports/depot-imports/drafts`;
- `GET /api/v1/daily-reports/depot-imports/drafts`;
- `GET /api/v1/daily-reports/depot-imports/drafts/{batch}`;
- `PATCH /api/v1/daily-reports/depot-imports/drafts/{batch}/source-driver`;
  and
- `POST /api/v1/daily-reports/depot-imports/drafts/{batch}/finalize`;
- `POST /api/v1/daily-reports/depot-imports/drafts/{batch}/cancel`.

Draft creation and mapping require `daily-reports.enter-for-driver`; reading
an existing draft requires `daily-reports.view`. Cancelling a finalized import
also requires `daily-reports.review`. The uploaded XLSX remains
temporary request data. The application stores only validated values, source
and schema SHA-256 fingerprints, the confirmed carrier alias and audit
metadata. It never stores the workbook or formula output.

Persisted route values are limited to route identity, source driver and
carrier labels, service date, departure and arrival times, actual and planned
kilometres, loaded parcels, address deliveries, pickup-point deliveries,
customer rejections, the server-computed not-delivered balance, the basic
surcharge and its operational note. The legacy nullable database column for a
depot-reported not-delivered value is retained only so historical protected
row hashes remain verifiable; new imports never populate or expose it.

The source SHA-256 and normalized confirmed alias are unique within the
organization. Repeating the same source therefore returns the existing-batch
conflict instead of duplicating routes.

Every persisted row has a SHA-256 fingerprint over its source identity and
operational values. The batch also has a SHA-256 fingerprint over the exact
control totals. A source-name mapping:

- accepts only an active driver with a valid assignment to the master
  organization on every affected route date;
- changes only the driver and assignment foreign keys;
- uses optimistic `lock_version` concurrency control;
- verifies every protected row fingerprint and the batch totals both before
  and after the change; and
- writes an immutable event with actor, reason and before/after mapping data.

There is deliberately no endpoint for assigning one individual depot row in
the import screen. The import step maps each exact normalized source-driver
label in bulk. Any later correction of who actually drove all or part of a
route belongs to reconciliation, not source import.

The draft becomes `ready` when every ready route has an eligible driver. The
finalize endpoint rechecks every row fingerprint, exact totals, assignment
coverage and optimistic lock version in one database transaction. It then
changes only the batch state to `imported` and writes an `import_finalized`
audit event. It creates no route allocation, daily report, comparison or
correction record. A `no_run` row remains part of the depot source history.

A finalized import can be audit-cancelled only with the current lock version
and a required reason. Cancellation changes only the batch state from
`imported` to `cancelled` and writes an `import_cancelled` event with actor,
time, reason and unchanged totals fingerprints. It never deletes or edits a
source row, driver-name mapping or control total. A cancelled batch remains
available for audit but is excluded from future reconciliation.

## Audited draft administration UI

The Importy page lists the latest organization-scoped batches so a
dispatcher can resume work after refreshing the browser. Opening a batch
revalidates every protected row fingerprint and the batch control totals before
returning its detail. The screen supports exactly one assignment flow: bulk
mapping of one exact normalized depot driver label to one eligible active
driver.

Mapping and finalization submit the displayed `lock_version`, reload the
authoritative batch payload after a conflict and show only drivers whose
organization assignment overlaps the route period. Dates are displayed as
`dd.mm.yyyy`.

All parcel counts, kilometre values, times, notes and source identity fields
are rendered as text. There are no per-row driver controls or numeric edit
controls. The UI deliberately calls them depot records, not created routes.
It repeats the protected control totals. Once every source name is mapped,
`Dokončit import depa` locks the depot batch as the authoritative source
snapshot. No endpoint for creating daily reports, comparing reports or
splitting a route is exposed by Importy.

The finalized state is highlighted with a green check and the persistent
message `Import úspěšně uložen`. It also offers `Stornovat import`. Storno uses
an application modal instead of a browser confirm dialog, summarizes the
source file, alias, row count and totals, and requires a written reason. The
resulting cancelled state keeps the source visible and locked and displays the
stored cancellation reason and time.

## Separate future reconciliation workspace

The following operations belong under `Trasy -> Kontrola zapisu`, outside the
Importy workflow:

- pairing a depot record with one or more driver-entered reports;
- comparing depot and driver values and highlighting differences;
- splitting the real work between multiple drivers while conserving the
  depot totals;
- a driver's quick proposal to copy depot values into a new version of their
  own report; and
- supervisor-only, depot-approved revision of the immutable depot source.

Protected numeric or identity values have no ordinary update endpoint. A
future value correction must create a proposed revision, carry a depot
approval reference and require an independent approval before it can replace
the effective depot values. Driver-side corrections must remain versioned in
the existing daily-report history. Splits and pairings must reference the
imported depot row rather than altering it, and the sum of every active split
must equal the protected depot totals.
