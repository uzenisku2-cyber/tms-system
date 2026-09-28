# Sprint 117: Vehicle installment evidence administration

- Register individual planned installments under the latest schedule and financing revision, with a verified vehicle document.
- Append corrections to the current installment revision; keep historical rows immutable and visible in vehicle detail.
- Require organization scope, manage permission, optimistic vehicle/schedule/installment revisions, valid sequence/date/currency, and exact cent arithmetic. Reject duplicate active sequences.
- Record each write as a vehicle registry event. This records contractual installment evidence only; no payment, settlement, or accounting action is performed.
- Existing schema is used without migration.
