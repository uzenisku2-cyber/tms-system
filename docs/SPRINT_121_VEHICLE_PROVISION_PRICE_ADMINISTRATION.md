# Sprint 121: Vehicle provision price administration

- Register organization-scoped prices against the latest provision agreement revision and append corrections as immutable price revisions.
- Require vehicle manage permission, verified vehicle document, optimistic vehicle/agreement/price revisions, and dates within the agreement.
- Validate nonnegative two-decimal amount, uppercase currency, billing period and mode, VAT mode and rate; record audit event for each write.
- Expose price history with the vehicle detail. No invoice, payment, settlement, migration, or existing financial source mutation.
