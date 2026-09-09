# Sprint 072 â€” Bank Transaction Amount Breakdown Foundation

- Bank transaction evidence remains immutable source evidence.
- A breakdown is stored as append-only revisions with a stable breakdown UID.
- Components support VAT, deductible/co-payment, and labeled custom purposes.
- Signed component corrections are allowed.
- Financial equality is evaluated using integer minor units, never floating-point arithmetic.
- Every saved revision must balance exactly to the source bank amount to one cent.
- Finalized breakdowns cannot be revised.
- Writes are organization-scoped, permission-protected, idempotent, revision-guarded, and audited.
- This foundation performs no automatic matching, payment marking, invoice mutation, settlement, or fund movement.

- Administration UI supports editable VAT, deductible and named custom components, signed corrections, draft/finalize actions and an exact-cent live balance guard.
