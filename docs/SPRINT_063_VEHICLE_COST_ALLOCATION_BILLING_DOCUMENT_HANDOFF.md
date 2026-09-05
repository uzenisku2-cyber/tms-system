# Sprint 063 â€” Vehicle Cost Allocation Billing Document Handoff

An authorized operator may explicitly execute one `billing_document` handoff instruction. Execution is revision guarded, idempotent and append-only. It creates exactly one draft billing document and one line from the immutable instruction snapshot.

The owner is the organization context. The counterparty must be a manageable organization or a visible registered driver. A customer invoice contains exactly one counterparty kind. External free-text parties remain manual review.

Standard VAT is mandatory. The operator supplies the VAT rate in basis points; the service verifies the active payer tax profile and recomputes VAT against the snapshotted net and VAT amounts. This is operational evidence, not an accounting or tax filing system.

Execution does not approve or close the document, mark payment, match a bank transaction, offset a deposit, or move repair-fund money. Those require separate explicit workflows.