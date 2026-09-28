# Sprint 123: Customer invoice workspace

The Finance billing panel lets an authorized master organization create a draft invoice from selected approved or closed calculation UUIDs, review the persisted parties, lines and totals, and issue it with a number, dates and audit reason. The server validates organization, tax profile, calculation eligibility, uniqueness and amount integrity. The panel hides write controls in restricted billing views; API permissions remain the authority.

Each command holds its UUID idempotency key while its inputs remain unchanged. A failed issue response requires checking the invoice state before retrying. The overview refreshes after draft creation and issuance. The panel is an operational issuance interface; PDF rendering, delivery, ledger posting, payment and settlement remain separate.
