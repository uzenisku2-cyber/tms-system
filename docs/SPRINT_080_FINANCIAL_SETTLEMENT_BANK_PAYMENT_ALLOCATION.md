# Sprint 080 - Financial settlement bank payment allocation

## S080-01A exact accepted-candidate materialization

- An authorized operator may materialize one accepted financial-settlement bank-match candidate into exactly one active payment allocation.
- The command is organization scoped, optimistic-revision guarded, fingerprinted and idempotent.
- The allocation snapshots the statement, billing document, bank evidence revision, exact integer minor-unit amount and currency.
- Sprint 080 initially supports exact settlement payment only: the proposed amount must equal the snapshotted outstanding settlement amount.
- One candidate can be materialized at most once, and every successful allocation creates one immutable append-only event.

## Shared bank evidence capacity

- Bank transaction evidence is locked before capacity is evaluated.
- Used capacity is the sum of active supplier-fuel invoice payments, executed vehicle-cost bank matches and active financial-settlement payments.
- Each participating write workflow uses the shared capacity service so one bank amount cannot be consumed independently by multiple domains.

## Explicit boundaries

- Materialization records the approved payment allocation but does not mutate the settlement statement or billing document.
- It does not create a generic vehicle-cost bank-matching execution, execute a deposit offset, move repair-fund money or perform currency conversion.
- Driver payout, driver deduction and zero-balance outputs remain outside bank matching.
- Partial settlement payments and administration UI remain separate follow-up units.

## S080-02A reversal lifecycle

- An active settlement payment can be reversed exactly once with optimistic revision and an idempotent command fingerprint.
- Reversal preserves the original allocation, advances its revision and appends a `payment_reversed` event.
- Reversed allocations no longer consume shared bank evidence capacity.
- Reversal does not modify the settlement statement or billing document and does not execute another bank match.