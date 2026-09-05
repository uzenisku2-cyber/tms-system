# Sprint 058 â€” Vehicle Operational File and Compliance Foundation

This sprint adds the operational vehicle file above the canonical vehicle registry. Technical inspection and emissions compliance, insurance policies, service history, and incidents are stored as separate organization-scoped records.

Each business record has a stable `record_uid` and an append-only revision. Corrections and lifecycle transitions create a new revision rather than overwriting historical evidence. The database enforces revision uniqueness, valid date ordering, allowed lifecycle values, and non-negative insurance coverage or deductible metadata.

Compliance records cover technical inspection, emissions, registration, roadworthiness, and exceptional checks. Insurance records distinguish compulsory liability, casco, GAP, assistance, and other cover. Service records retain opening and completion times, odometer readings, future service dates or odometer thresholds, provider identity, and a primary supporting vehicle document. Incident records retain accidents, damage, theft, vandalism, breakdowns, driver or responsible organization context, police and insurance claim references, resolution state, and severity.

Document metadata remains separate from the operational record and may be linked as primary evidence. File upload transport and storage remain outside this sprint. Reminder delivery is also deferred; `valid_until`, `next_service_on`, and `next_service_odometer` provide deterministic inputs for a later notification module.

No repair price, party allocation, deductible charge, recoverable VAT, repair-fund movement, invoice, deposit offset, financial calculation, payment, or bank match is created or inferred here. These financial consequences require explicit later workflows and must reference the immutable operational evidence rather than alter it.

Operational visibility, sensitive insurance details, financial responsibility, and authorization remain separate concerns. Persistent production data is not used by sprint validation.