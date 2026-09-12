# Sprint 077 - Supplier Fuel Invoice Bank Matching and Payment UI

## S077-01A bank match candidate foundation

- A candidate links immutable bank transaction evidence to one supplier fuel invoice billing document within the same organization.
- Exact minor-unit amounts, evidence revision, currency, unpaid invoice capacity, and proposed allocation are snapshotted.
- Matching remains explainable through a basis-point score and explicit reasons derived from amount, variable symbol, account, supplier, and date signals.
- Proposed, accepted, rejected, and superseded states prepare a guarded human-review lifecycle.
- Idempotency, a stable candidate fingerprint, optimistic revision, and append-only events preserve command and audit integrity.
- Candidate creation does not allocate a payment, mark an invoice paid, execute generic vehicle-cost bank matching, or mutate fuel rebilling and settlement.

## S077-02A automatic candidate proposal API

- An organization-scoped idempotent command scans recorded debit bank evidence in the invoice currency and proposes the best eligible match.
- Deterministic basis-point scoring explains exact amount, variable-symbol, counterparty-account, supplier-name, and configurable date-window signals.
- Active payment allocations reduce both invoice and bank capacities before a proposal is created.
- The source snapshot freezes the selected evidence revision, capacities, scoring inputs, and command fingerprint.
- Proposal remains review-only: it does not accept the candidate, create a payment, mark the invoice paid, execute vehicle-cost matching, or mutate fuel rebilling.

## S077-03A candidate review lifecycle

- An authorized organization user may accept, reject, or supersede only a proposed candidate.
- Every review requires the expected candidate revision, an idempotency key, and a human reason.
- Successful review closes the candidate at revision two and appends an immutable decision event.
- Idempotent replay returns the same reviewed candidate; reuse of the key with a changed command is rejected.
- Acceptance records human intent only. It does not create a payment, mark the invoice paid, execute generic bank matching, or mutate fuel rebilling.
## S077-04B accepted candidate payment materialization

- Acceptance remains a review decision and does not itself create a payment.
- A separate idempotent materialization command converts one accepted candidate into exactly one supplier-fuel-invoice bank payment allocation.
- Materialization revalidates candidate revision, bank-evidence revision, invoice capacity, evidence capacity, organization, currency, and debit direction through the canonical payment service.
- The candidate links immutably to the resulting payment and appends a revision-three `candidate_materialized` audit event.
- Rejected and superseded candidates cannot be materialized; fuel settlement remains unchanged.
## S077-05B – administrace párování a úhrad

Doplněn organizací omezený čtecí endpoint kandidátů a UI pro vysvětlitelný návrh, ruční rozhodnutí a samostatnou materializaci platby. Přímé označení úhrady bez platebního záznamu není povoleno.
