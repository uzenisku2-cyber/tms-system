# Sprint 070 - Bank Statement Import Administration UI

This sprint exposes the bank statement import foundation through the existing Laravel MVP application shell. The Bank page embeds a dedicated administration workspace for explicit CSOB CSV upload, organization-scoped batch listing, batch detail, normalized transaction rows, validation failures and unresolved duplicate candidates.

The UI sends `adapter=csob_csv` explicitly and relies on the existing API authorization boundary. It does not inspect or automatically detect customer bank formats. The committed fixture remains synthetic; no customer CSV is stored in the repository.

The workspace records evidence only. It contains no action that executes bank matching, marks a payment, mutates a billing document, applies a deposit offset or moves repair-fund money.