# Sprint 142 – carrier provisional remuneration read

GET `/api/v1/carrier/provisional-remuneration` is scoped to the authenticated carrier organization and its assigned drivers on the dates of reports. The endpoint reads current daily-report values and active relationship price-list versions for the route date. It does not write a financial calculation, settlement or invoice.

The response separates delivered parcels, redirected parcels, actual kilometres, a monthly quality reward and the manually entered route surcharge. The quality evaluation groups the full month by tariff version and uses delivered + redirected + customer rejected parcels over loaded parcels, with the configured 20% inclusive threshold. The reward pays delivered + redirected parcels only. The monthly total changes while reports are edited or added.

When any scoped report has no unique tariff or incomplete route values, `unpriced_route_count` is nonzero and `total_minor` is null. `priced_subtotal_minor` contains only routes priced so far and is not a billable amount. The endpoint always labels its output provisional and states that depot agreement is required for billing. It does not implement or bypass depot reconciliation.

If a route in a month is unpriced, quality for the entire month is marked `quality_pending` and omitted from priced route subtotals until the missing input is fixed. This prevents a partial monthly numerator from producing a misleading quality reward.
