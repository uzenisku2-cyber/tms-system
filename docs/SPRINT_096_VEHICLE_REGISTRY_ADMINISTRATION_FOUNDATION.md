# Sprint 096 - Vehicle registry administration foundation

This sprint adds an organization-scoped read API and an MVP administration page for the canonical vehicle registry. The page provides filtering and a consolidated vehicle card covering ownership, responsibility, documents, compliance, insurance, service, incidents, financing and append-only registry history.

The new administration path requires `vehicle.view` and resolves the active organization context. It does not use legacy `vehicles.user_id` as ownership or authorization evidence. It does not expose physical deletion and does not mutate vehicle lifecycle, ownership, responsibility or financial records.

No schema migration is introduced. Later explicit workflows will add lifecycle transitions and revisioned administration for the individual vehicle sub-records.