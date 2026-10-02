# Sprint 136 — access boundary before user administration

The driver role is organization scoped, but `daily-reports.view` previously exposed every report in that organization, including navigation counts and the performance overview. A driver now reads only reports belonging to their linked driver profile. Show, version and event lookups return 404 for another driver's report. The aggregate performance overview and depot import and quality administration reads reject a driver who has no delegated entry or review permission. Dispatcher plus driver remains possible: the additional operational permission permits the larger scope.

This is the first access boundary. It does not create user accounts, invitations or role assignment screens. The next units must separate people administration from the overloaded `users.manage` permission, audit every module's read scope, implement an organization user invitation and role wizard, and implement a shared day-only availability read with a separate write/confirmation boundary. Do not assign broad financial or administrative permissions to drivers during that work.

All tests use an isolated SQLite database. The local PostgreSQL source and preview copy are not changed by installing these source files.

## Role foundation

The permission seed now defines `people.manage` separately from the legacy `users.manage`, and `availability.confirm` separately from either. The `carrier-admin`, `dispatcher`, and `driver` roles receive only their explicit operational permissions. None receives `pricing.*`, `compensation.*`, or legacy `users.manage`. A dispatcher with `availability.confirm` can confirm a day in their authorized supervisory scope while the submitter cannot confirm their own entry. This role foundation does not yet invite people or grant the carrier-admin role to an existing user; those operations follow in the organization people API.

The daily availability calendar has one deliberate read exception: every active member of the selected organization can see the names and day status of its active member drivers. A peer's response omits their private reason, submission and decision actor IDs, record ID, and revision. Only the driver themself or an authorized supervisor can submit; only an authorized supervisor can confirm. Drivers assigned through other organizations remain visible only under existing supervisory scope. The calendar UI offers an entry form only for the actor's own driver profile or supervisory scope.
