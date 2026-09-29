# Sprint 132 — Driver availability time windows

A daily declaration can include up to eight ordered, nonoverlapping local time windows in Europe/Prague. Each is half-open: its start is included, its end excluded. A blank window list from the original API means the whole day, including for historical records. Windows are copied to every append-only audit event. The calendar accepts one `HH:MM-HH:MM` interval per line and renders the confirmed or pending windows.

A dispatcher cannot confirm an unavailable interval when an assigned or started trip has its known scheduled start (or actual start when no scheduled time exists) inside it. Trip assignment likewise checks confirmed unavailability at its known start. Existing trips without either timestamp cannot be checked; the trip model currently has no planned end, so this feature does not infer full trip duration or assert that a trip overlaps when only its start is outside the window. Assignment and confirmation checks are synchronous; full reservation-level serialization remains separate work.

Availability remains separate from driver eligibility, reservation and trip assignment. Empty availability remains unknown. The checks use disposable SQLite for tests; no persistent data is migrated by installation.
