# Sprint 074 - Supplier Fuel Invoice Administration UI

## S074-01A Supplier fuel invoice read API

- Lists payable supplier fuel invoice drafts within the active organization.
- Returns a persistent invoice detail with commercial identity, document lines, audit events, and allocation history.
- Presents active and reversed fuel-transaction allocations without deleting history.
- Computes invoice, active allocated, and remaining values in integer minor units.
- Exposes unallocated, partially_allocated, and fully_allocated states.
- Read operations do not match bank transactions, mark payments, or mutate fuel settlement applications.

## Deferred UI scope

- Invoice creation, list, detail, allocation, and reversal controls will use this read contract.
- Automatic bank matching and payment marking remain outside Sprint 074.
## S074-02A Supplier fuel invoice administration UI

- Adds an organization-scoped invoice list and persistent detail backed by the read API.
- Shows invoice, active-allocation, and unallocated totals as exact minor-unit values.
- Allows an authorized operator to allocate a fuel transaction and auditably reverse an active allocation.
- Allocation history remains visible after reversal; automatic bank matching and payment marking remain outside this UI.
