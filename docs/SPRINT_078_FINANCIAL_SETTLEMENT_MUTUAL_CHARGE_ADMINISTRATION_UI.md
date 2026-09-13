# Sprint 078 - Financial settlement and mutual charge administration UI

## S078-01A - administration read foundation

- Organization-scoped list and detail endpoints expose financial mutual charges and settlement statements.
- Shared mutual charges are visible to their organization counterparty; settlement statements remain owner scoped.
- Exact minor-unit amounts, immutable source snapshots, lines and append-only audit events are presented without financial execution.

## S078-02A - mutual charge administration UI

- Adds a read-only administration page for searching and filtering mutual charges by status and direction.
- Presents exact amounts, bilateral visibility, source snapshots and audit history.
- No confirm, dispute, reverse, billing, payment or bank-matching action is exposed by this UI block.
## S078-03A - settlement statement administration UI

- Adds a read-only overview and detail page for driver and external-carrier settlement statements.
- Presents earning, deduction and signed net balance minor units, lifecycle state, output direction, billing-document reference, source lines and append-only audit events.
- No review, approval, closure, cancellation, output materialization, payment or bank-matching action is exposed by this UI block.
## S078-04B - mutual charge lifecycle administration UI

- Owner organizations may confirm draft charges and reverse non-reversed charges.
- Owners and shared organization counterparties may dispute confirmed charges.
- Every UI command supplies a new idempotency key, the displayed expected revision and a mandatory reason, then refreshes detail and list state.
- No payment, billing-document materialization or bank-matching action is introduced.
## S078-05A - settlement lifecycle administration UI

- Adds draft submission, approval, closure and pre-materialization cancellation controls.
- Corrects the presentation and filter contract from `review` to the domain status `under_review`.
- Every UI transition sends the selected action, a new idempotency key, the displayed expected revision and a mandatory reason.
- Output materialization, payment marking and bank matching remain outside this block.
## S078-06A - settlement output materialization UI

- A separate materialization action is shown only for closed statements without an existing output.
- Zero-balance and driver outputs require only command identity, revision and reason.
- External-carrier outputs collect document dates, commercial identity, VAT treatment and exact two-decimal net and VAT amounts whose sum must equal the absolute signed balance.
- Materialization creates no payment and performs no bank matching.
## Materialization form usability repair

The external-carrier document form uses dedicated responsive sections for document terms, counterparty identity, and exact VAT amounts. Required fields are marked, date values remain fully visible, and the form no longer inherits the compact overview-filter grid. API payload and payment/bank-matching boundaries remain unchanged.

## S078-07B - direction filter and administration copy repair

- Settlement direction filtering now applies the validated `direction` value to `output_direction` in the organization-scoped statement query.
- Current page copy describes the audited lifecycle and materialization controls instead of incorrectly calling the administration pages read-only.
- UI contract names and assertions cover the current administration behavior and the output-direction query binding.
- Payment marking and bank matching remain outside this sprint scope.
