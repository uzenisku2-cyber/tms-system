# Sprint 119: Vehicle provision agreement evidence administration

- Register organization-scoped vehicle provision agreements and append corrections as immutable revisions.
- Require vehicle manage permission, a verified vehicle document, valid provider and recipient parties, and optimistic vehicle and agreement revisions.
- Organization parties are limited to the active organization; driver parties must have active membership in it.
- Expose agreement history in vehicle detail and write one registry audit event per revision.
- No provision price, invoice, payment, or settlement operation is included. The existing schema is unchanged.
