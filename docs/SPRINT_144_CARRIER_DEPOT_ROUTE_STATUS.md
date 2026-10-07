# Sprint 144 – carrier route evidence status

The read API derives an operational status from the current driver report and an integrity-checked imported depot row. It scopes all reports and depot rows to the carrier's historical driver assignments and master relationship. `matched_pending_approval` means operational values match; it is never financial approval. Routes without a depot row are `awaiting_depot`. Differences require correction or assignment review. Multiple comparisons fail closed as `manual_review`.

This slice does not write approval, payment, accounting, settlement or invoice records. Provisional remuneration continues to use the current driver report. A later explicit master approval must bind the depot row and report version, and be invalidated by a subsequent report correction. Payment and accounting statuses require their own linked evidence.
