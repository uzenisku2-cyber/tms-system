# Fuel Transaction Review Finalization

## Purpose

Finalization applies the latest audited correction to the resulting fuel transaction.

## Safety contract

- only review or rejected rows can be finalized,
- the client supplies the latest expected_correction_revision,
- database records are locked and the payload is validated again,
- duplicate fingerprint, fuel card and one effective assignment are checked again,
- repeated finalization is rejected.

## Result and audit

The transaction becomes matched, the row becomes accepted and batch counters are recalculated. The fuel_import_row_finalizations table stores the correction revision, before and after snapshots, actor, reason, timestamp and resulting transaction.

## Authorization and UI

The endpoint requires compensation.manage. The /settings/fuel-imports page finalizes the latest revision with a mandatory reason and displays the immutable closure audit.

## Testing

Tests use disposable SQLite. Persistent PostgreSQL is not used by Sprint 037 verification.