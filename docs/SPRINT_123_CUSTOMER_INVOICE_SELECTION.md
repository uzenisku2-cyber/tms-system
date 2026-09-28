# Sprint 123: Customer invoice selection

The company billing workspace loads customers from the existing customer API and recent final calculations from the scoped financial-calculation API. Selection uses persisted calculation UUIDs; the draft service performs the definitive eligibility, period, customer and duplicate checks. Further pages can be loaded. An invoice row opens its scoped detail. Manual UUID entry remains available when a calculation is not among recent results. No schema or payment change.
