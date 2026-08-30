# Sprint 044 â€” Fuel Transaction Driver Attribution

## Business purpose

Imported fuel transactions must distinguish the card holder from the driver who actually fueled. A driver may legitimately borrow another driver's card after reporting the situation. An authorized user must therefore be able to correct the actual fueling driver after an import has been saved.

## Binding invariants

1. Provider source data and the original imported transaction remain immutable.
2. `fuel_card_assignment_id` records the assignment used when the transaction was imported.
3. The effective actual driver is a separate attribution and defaults to the driver resolved from that assignment.
4. A later driver correction changes attribution only and never changes quantity, price, tax, amount, currency, occurrence time, provider identifiers, fingerprint, source row, or import totals.
5. Every correction is append-only and stores transaction, previous driver, new driver, previous and new driver-organization assignments, reason, actor, and correction timestamp.
6. Reverting creates another event; audit history is never updated or deleted.
7. The selected driver must have an organization assignment valid at the transaction time.
8. The main carrier may see and correct drivers in its authorized supervisory hierarchy.
9. An external carrier may see and select only its own subordinate drivers.
10. Organizational assignment and authorization scope remain separate concepts.
11. Corrections remain available after import completion or row finalization.
12. Concurrent corrections use an expected revision and fail safely when stale.

## Planned implementation boundary

- Add a dedicated append-only fuel transaction driver-attribution event table.
- Preserve import provenance while exposing the effective actual driver.
- Add organization-scoped eligible-driver, history, and correction endpoints.
- Reuse driver supervisory authorization for visibility.
- Expose correction from import review and transaction detail.
- Test borrowed-card correction, immutable financial values, audit history, scope isolation, finalization independence, and stale revisions.

## Explicit non-goals

- Editing provider transaction values or historical card assignment.
- Recalculating import totals because of a driver-only correction.
- Deleting or rewriting attribution history.
