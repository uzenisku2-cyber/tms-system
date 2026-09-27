# Sprint 109 – Vehicle service evidence administration

The existing `vehicle_service_records` table stores organization-scoped service evidence. Managers register a record against a verified vehicle document and correct it by adding a new immutable revision. The registry detail already reads these records.

Writes require `vehicle.manage`, organization-scoped vehicle visibility, a current vehicle revision and a verified document in the same organization. Corrections also require the latest service record public ID and revision. Each successful write advances the vehicle revision and appends a registry audit event with the source document revision.

Service type, status, dates, odometer values and provider organization are validated. A provider organization reference, if supplied, must be the active organization. No schema migration, payment, invoice, physical deletion or service scheduling execution is introduced.
