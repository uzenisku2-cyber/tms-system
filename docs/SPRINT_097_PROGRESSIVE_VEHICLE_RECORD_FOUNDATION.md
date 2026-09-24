# Sprint 097 - Progressive vehicle record foundation

Vehicles can be registered when only a registration number or VIN is currently available. Manufacturer, model, year, fuel type, odometer information and documentary details may be completed later without inventing placeholder business values.

Each organization-scoped record receives an operational responsibility, a twelve-area revisioned completeness checklist and append-only registry audit events. Field states distinguish missing, pending document, unverified, verified and not applicable information. Omitted mileage remains semantically unknown and is returned as null by the administration read API, while an explicitly supplied zero remains a valid odometer value.

The write API requires `vehicle.manage`, uses optimistic revision checks and does not expose physical vehicle deletion. Existing ownership, document, insurance, service, incident and financing models remain authoritative and are not duplicated.
