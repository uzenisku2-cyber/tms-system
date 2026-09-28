# Sprint 124: Customer invoice document presentation

The authenticated HTML document endpoint renders approved or closed customer invoices from their issued commercial identity snapshot. It reuses issuer/customer scoped invoice reads and refuses drafts. The Finance workspace opens the printable A4 page and the browser can save it as PDF. The document includes number, issue and due dates, taxable supply date when present, variable symbol, parties, lines, totals and currency. Tests cover customer visibility and persistence of the original address after the customer record changes.

The ledger does not yet store an issuer payment account; the document explicitly directs separate payment instructions. This sprint does not generate or store a PDF file, send email, post a ledger entry, or reconcile payments. Review the final operational invoice policy before external use.
