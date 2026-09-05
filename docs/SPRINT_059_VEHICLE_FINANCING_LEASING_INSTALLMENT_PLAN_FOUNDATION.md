# Sprint 059 â€” Vehicle Financing, Leasing and Installment Plan Foundation

This sprint separates vehicle identity and legal ownership from the agreement under which a vehicle is provided for use. A vehicle may be the driver's own vehicle, be provided free of charge, be rented, be subject to an operating or finance lease, or be transferred through a purchase-installment arrangement. Provider, user and financial debtor are separate roles.

A provision agreement identifies the vehicle, organization context, provider and recipient. The recipient is explicitly either a driver or an organization. The model therefore supports lending a vehicle to one driver, providing it to a subordinate carrier, and recording that a driver uses their own vehicle. An own-vehicle or free-use agreement does not require a price.

The manual provision price is an explicit append-only revision with amount, currency, billing period and effective dates. Historical price revisions remain immutable. `billing_mode` records the intended later workflow: invoice required, deposit offset, informational only, or manual review. `vat_mode` is planning metadata and is not a tax decision.

Vehicle financing is recorded separately from vehicle provision. Financing agreements cover operating lease, finance lease, purchase installments, loans and exceptional arrangements. A revisioned installment schedule contains immutable planned installments, including principal and separately identified finance or other charges. Planned, cancelled, replaced or waived status does not mean that money was paid.

Authorization follows organizational hierarchy. The main carrier may administer agreements and manual provision prices for its own organization, subordinate carriers and their visible drivers. A subordinate carrier may administer only its own agreements and own drivers. It may not set a price for the main carrier, a sibling carrier or a foreign driver. These rules must be enforced by the later application service using organization relationship and driver visibility contracts; database ownership alone is not authorization.

This foundation must not create an invoice, billing document, VAT liability, deposit offset, financial calculation, payment, repair-fund movement or bank match. It must not mark a payment from an installment status. Later workflows must explicitly transform an eligible contractual amount into the chosen settlement channel and retain links back to the immutable agreement, historical price revision and installment.

Persistent production data is not used by sprint validation.