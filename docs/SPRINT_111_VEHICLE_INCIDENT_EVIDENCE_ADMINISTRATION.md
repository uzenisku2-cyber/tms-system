# Sprint 111 – Vehicle incident evidence administration

Vehicle incidents use the existing `vehicle_incidents` table. Managers register an incident against a verified vehicle document and correct it by adding a new immutable revision. Vehicle details already expose these records.

Writes require `vehicle.manage`, visibility in the active organization, the current vehicle revision and a verified document from that organization. Corrections require the latest incident public ID and revision. Each successful write advances the vehicle revision and appends a registry audit event carrying the source document revision.

Incident type, status, severity and chronological dates are validated. The optional responsible organization must be the active organization, and an optional driver must have active membership there. This evidence does not create an insurance claim, payment, repair order or lifecycle transition. No schema migration or physical deletion is introduced.
