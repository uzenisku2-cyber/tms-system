# Sprint 060 â€” Vehicle Cost Allocation and Financial Responsibility Foundation

This sprint records why a vehicle cost exists, its net amount, VAT amount and gross amount, and how responsibility is divided. Sources include service, incident, insurance, provision, rental, leasing, installments and manually recorded costs. The allocation is an operational financial overview, not an accounting entry.

Every allocation line identifies exactly one responsible party: an organization, driver, insurer, state, internal responsibility or named external party. The main carrier may manage allocations for its own organization, subordinate carriers and their visible drivers. A subordinate carrier may manage only its own scope and own drivers. Later application services must enforce this hierarchy before accepting or approving a revision.

Cost components distinguish base cost, VAT, deductible, damage, rental, leasing, installment, insurance recovery and other amounts. Net, VAT and gross amounts remain explicit. VAT treatment is recorded as standard rate, outside scope, not applicable or pending review; it is evidence for later workflow and not an automated tax decision.

Settlement mode expresses the intended next action: invoice required, deposit offset, repair fund reserve, insurance recovery, state recovery, informational only or manual review. A recognized cash or contractual deposit may therefore be allocated without pretending that an invoice or bank payment already exists. VAT may be assigned to a repair fund reserve only as an explicit approved allocation and never by hidden automatic conversion.

Allocations, lines and lifecycle events are append-only and revisioned. Approval records the responsible actor and time. Corrections create later revisions and preserve historical evidence.

This foundation must not create an invoice, billing document, tax document, financial calculation, bank transaction, payment, deposit movement or repair-fund transaction. It must not mark any amount as paid or bank-matched. Those effects belong to later explicit integration workflows with their own authorization, idempotency and audit controls.

Persistent production data is not used by sprint validation.