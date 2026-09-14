# Sprint 079 - Financial settlement bank match candidate foundation

## Scope

- Introduces an organization-scoped, explainable bank-match candidate between a materialized external-carrier settlement output and immutable bank transaction evidence.
- `carrier_payable` output requires debit evidence; `carrier_receivable` output requires credit evidence.
- Driver payout, driver deduction and zero-balance outputs remain outside bank matching.
- Candidate states are `proposed`, `accepted`, `rejected` and `superseded`, with optimistic revisions and append-only audit events.

## S079-02A - persistence foundation

- Stores the settlement statement, billing document, bank evidence and evidence revision explicitly.
- Stores exact minor-unit capacity snapshots, expected bank direction, explainable score inputs and immutable source evidence.
- Provides no API route, proposal algorithm or review command in this block.
- Creates no payment allocation, marks no document as paid, performs no bank-matching execution and does not mutate the settlement statement or billing document.

## S079-03A - proposal and explainable scoring

- Proposes a candidate only for a closed, materialized external-carrier settlement output.
- Maps `carrier_payable` to debit evidence and `carrier_receivable` to credit evidence.
- Requires organization, recorded status, currency, direction and exact outstanding amount to match before scoring.
- Scores variable symbol, counterparty account, counterparty name and due-date proximity, with deterministic tie-breaking.
- Preserves idempotency, the bank-evidence revision and an explainable immutable source snapshot.
- Exposes no HTTP route or review transition in this block and creates no payment allocation or matching execution.

## S079-04A - human review lifecycle

- Adds an organization-scoped review endpoint for `accepted`, `rejected` and `superseded` decisions.
- Requires the expected candidate revision and preserves idempotent replay and command-conflict detection.
- Records the reviewer, timestamp, reason and append-only review event at the next revision.
- Does not mutate the settlement statement or billing document and creates no payment allocation or bank-matching execution.
