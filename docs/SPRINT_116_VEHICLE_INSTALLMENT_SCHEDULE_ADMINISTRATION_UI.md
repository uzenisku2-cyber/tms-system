# Sprint 116 – Vehicle installment schedule administration UI

The vehicle detail card adds a collapsed installment schedule panel. A manager can register a schedule against the latest financing agreement revision, correct its latest version, and inspect earlier immutable revisions. The panel uses the existing organization-scoped read API and the Sprint 115 write routes. It reselects a verified source document on each correction, submits vehicle, financing and schedule optimistic revisions, and reloads current data after a conflict.

The schedule is a plan. This panel does not create individual installment rows or mark any amount as paid. Schedule revisions tied to an older financing agreement are displayed as history; a new schedule can be registered against the current financing revision. No schema or payment changes are introduced.
