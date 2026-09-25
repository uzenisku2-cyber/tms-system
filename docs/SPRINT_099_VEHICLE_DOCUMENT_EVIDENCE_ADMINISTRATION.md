# Sprint 099 â€“ Vehicle document evidence administration

The vehicle registry reuses the existing `vehicle_documents` table and exposes organization-scoped administration for document references.

- A manager registers a document reference with its type, title, validity and access classification. New evidence starts as `unverified`.
- A manager can review evidence as `verified` or `rejected` using both vehicle and document optimistic revisions.
- Every state-changing operation increments the vehicle revision and appends a `vehicle_registry_events` audit record.
- Documents are visible only through an ownership or responsibility in the active organization context.
- Physical document deletion is intentionally unavailable.
- This sprint does not mutate insurance policies, financing agreements or persistent database schema.