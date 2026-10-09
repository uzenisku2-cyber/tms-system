# Sprint 154 - Record review navigation

## Problem
The record-review navigation item remained hidden when organization identity
did not provide its type. Loading the authorized master depot-route-status
endpoint later established the master type without refreshing navigation.

## Change
Refresh navigation visibility after successfully recognizing the master
organization. Ignore obsolete route-history responses before updating shared
organization state. Existing permission and organization checks remain active.

## Validation
- Four inline JavaScript blocks passed syntax validation.
- Browser preview: record-review navigation appeared after a full page reload.
- Clicking the item loaded the record-review screen and depot comparison data.
- Preview showed 82 records: 81 matching and one ignored.
- Temporary runtime view restored before committing.

## Scope
Two JavaScript lines added to the existing view.
No API, schema or permission changes. Bulk approval remains paused.
