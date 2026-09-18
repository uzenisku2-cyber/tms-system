# Sprint 086: Bank transaction evidence administration UI

This increment adds an organization-scoped, read-only administration surface for bank transaction evidence.

## Delivered

- Filtered evidence index and detail endpoints guarded by `compensation.view`.
- Total, allocated, and remaining evidence capacity using the existing capacity service.
- Read-only links to settlement candidates, settlement payments, reconciliations, supplier-fuel payments, and vehicle-cost matches.
- Amount-breakdown and append-only evidence-event presentation.
- Administration panel in the existing financial settlement screen.

## Boundaries

- No schema migration.
- No evidence, payment, reconciliation, statement, or billing-document mutation.
- No accounting posting or export-audit persistence.
- Existing evidence recording and financial lifecycle commands remain authoritative.