# Sprint 098 â€“ Progressive vehicle record completion

Vehicle administrators can progressively update only the explicitly supplied core vehicle fields while preserving unknown values and explicit zero mileage semantics.

- PATCH `/api/v1/vehicle-registry-administration/{vehicle}` requires `vehicle.manage`, organization responsibility and `expected_revision`.
- Registration number or VIN must remain available after every update.
- Omitted fields remain unchanged; an explicitly null field returns to `pending_document`.
- Values without a verified source document remain `unverified`; a verified existing vehicle document can mark supplied fields `verified`.
- The existing document is referenced in the append-only `progressive_vehicle_record_updated` registry event; no parallel document table is created.
- Insurance, financing and physical deletion are outside this sprint.