# Sprint 153 — Depot comparison filter

Route history supports filtering by depot comparison alongside the existing
period, driver and report-status filters. Filtering runs after all route-history
pages and authorized depot evidence have loaded.

Groups:
- All: all reports in the current period/driver/report-status selection.
- Matching: matched_pending_approval and approved.
- Mismatch: correction_required.
- Awaiting depot: awaiting_depot.
- Review: assignment_review, manual_review, unknown or missing evidence entries.

Counts use the complete current selection before the depot filter.
Clearing filters also clears the depot selection.
If depot evidence cannot load while a depot filter is selected, loading fails
visibly instead of displaying unfiltered reports.
Monthly bulk approval retains its existing scope across all drivers in the month.

Validation:
- JavaScript syntax: PASS for all 4 inline scripts.
- Classification checks: PASS for 8 cases.
- Browser: June 2025 contains 81 reports, 80 matching and 1 requiring review.
- Browser: selected driver 33102 contains 21 reports, 20 matching and 1 review.
- Browser: review filter selects route 16 on 2025-06-02.
- Browser: All restores 21 driver reports; Mismatch displays zero reports.
- No approvals were executed during validation.

The review case concerns driver attribution; displayed route values match.
Resolving attribution is separate from this filter change.