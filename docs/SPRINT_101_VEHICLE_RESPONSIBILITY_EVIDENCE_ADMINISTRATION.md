# Sprint 101 â€” Vehicle Responsibility Evidence Administration

This sprint adds organization-scoped administration of time-bounded vehicle responsibility using the existing `vehicle_responsibilities` foundation. No schema change is required.

A responsibility record is registered only against an existing verified vehicle document. The durable append-only registry event records the source document public identifier and revision. New records begin as `active` and may subsequently transition to `ended` or `cancelled` against verified document evidence.

Both registration and review require `vehicle.manage`, organization-scoped vehicle visibility, a current vehicle revision, and row locks. Review additionally requires the current responsibility revision. Every effective mutation advances the vehicle revision and appends a registry event; records are never physically deleted.

This stage supports registered operator, operational organization, custodian, authorized user, and default driver responsibilities assigned to an organization, user, or external party. Insurance, financing calculations, payments, and bank matching remain explicitly outside scope.