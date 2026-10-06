# Sprint 142 – provisional route remuneration foundation

The arithmetic component uses the driver's current route values and the applicable historical tariff. It returns integer minor currency units for delivered and redirected parcels, actual kilometres, the monthly quality reward, and the separately entered route surcharge.

The monthly quality numerator is delivered + redirected + customer rejected parcels. The threshold is at least 20% of loaded parcels, inclusive. A zero quality rate means no quality reward for that tariff period. The reward quantity is delivered + redirected parcels on the route. The manually entered surcharge is independent of refused parcels.

This foundation is read-only and is not an invoice, financial calculation, settlement, depot agreement, or authorization to bill. The API that selects current report versions and tariff versions, month aggregation, organization scope, and depot reconciliation gate belong to subsequent implementation slices.
