# Sprint 071 â€“ Bank Import Duplicate Candidate Resolution

This sprint adds organization-scoped, append-only decisions for bank-import duplicate candidates. An authorized user records either `confirmed_duplicate` or `dismissed` with an idempotency key, reason, actor and timestamp.

The decision does not mutate the imported row, candidate, or bank transaction evidence. It does not execute matching, mark a payment, alter a billing document, offset a deposit, apply a settlement deduction, or move repair-fund money.
## Administration UI

The bank-import workspace exposes explicit actions to confirm or dismiss an unresolved duplicate candidate. Every action requires a reason and records a new idempotent, append-only resolution through the organization-scoped API.

The UI does not execute matching, mark payments, mutate billing documents, or modify the original imported row, duplicate candidate, or bank transaction evidence.
- Probable duplicate identity requires the same non-empty variable symbol; equal amounts with different or missing symbols remain separate bank evidence.
- Duplicate decisions use an application modal with a mandatory reason; native browser prompt dialogs are not used.
