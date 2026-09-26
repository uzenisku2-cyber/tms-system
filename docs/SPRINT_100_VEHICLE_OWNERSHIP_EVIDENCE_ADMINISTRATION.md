# Sprint 100 â€” Vehicle Ownership Evidence Administration

This sprint adds organization-scoped administration of time-bounded vehicle ownership using the existing `vehicle_ownerships` foundation. No schema change is required.

An ownership record is registered only against an existing verified vehicle document. The durable append-only registry event records the source document public identifier and revision. New ownership records begin as `unverified` and may subsequently be verified or rejected against verified document evidence.

Both registration and review require `vehicle.manage`, organization-scoped vehicle visibility, a current vehicle revision, and row locks. Review additionally requires the current ownership revision. Every effective mutation advances the vehicle revision and appends a registry event; records are never physically deleted.

This stage supports organization, user, and external-party owners. Financing-provider ownership, insurance, financing calculations, payments, and bank matching remain explicitly outside scope.