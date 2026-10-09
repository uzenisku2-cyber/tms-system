# Sprint 155 - Fuel and vehicle workspace

## Scope

- Separate PHM and Vozidla in the main navigation.
- Embed vehicle administration in the main workspace without an external navigation action.
- Rename the shared offset-consent section to Zápočty nákladů.
- Expose vehicle.view and vehicle.manage capabilities and include them in the super-admin permission set.
- Improve vehicle completion, document and ownership forms and Czech labels.

## Private document files

- Accept PDF, JPEG and PNG files up to 10 MiB.
- Store uploads on the private local disk with generated filenames.
- Preserve the existing document evidence and verification lifecycle.
- Authorize downloads by organization, vehicle and document scope.
- Restrict non-operational document downloads to vehicle managers.
- Reject client-supplied managed storage references.
- Remove uploaded files if document registration fails.
- Configure PHP upload/post limits at 10M/12M and Nginx request size at 12m.

## Ownership verification and editing

- Resolve the ownership source document from the latest applicable registry event.
- Offer an edit action with prefilled ownership values and cancellation.
- Require vehicle.manage, a verified source document and a reason.
- Guard changes with both vehicle and ownership revisions.
- Preserve the ownership public ID and increase both revisions.
- Reset edited ownership to unverified.
- Append an event containing previous and new ownership snapshots and source-document references.
- Preserve existing registry events.

## Request feedback

- Display a spinner and status text while vehicle-workspace fetch requests transfer data.
- Track concurrent requests and hide the indicator after the last request completes.
- Disable ownership form controls during submission.
- Preserve entered ownership values after a failed submission.

## Validation

- JavaScript syntax checks passed.
- Targeted PHP formatting passed.
- Isolated SQLite regression validation: 19 tests, 317 assertions, exit code 0.
- UI inspection confirmed ownership verification, edit prefill and return to registration mode after cancellation.
- Manual ownership-save validation and manual download validation are not recorded as completed.

## Deployment notes

The current running application contains temporary preview changes.
Reconcile those changes before updating its main branch.
Rebuild the PHP image to retain upload limits after container recreation.
Preserve private uploaded documents and operational database records.
No migration is introduced by this sprint.

Fuel settlement, vehicle-cost billing and the June 2025 invoice remain separate work.
