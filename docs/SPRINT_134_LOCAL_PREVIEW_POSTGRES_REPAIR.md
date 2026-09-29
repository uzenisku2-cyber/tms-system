# Sprint 134 – Local preview and PostgreSQL migration repair

The local preview at `127.0.0.1:8134` uses a separate PostgreSQL container restored from a consistent snapshot of the existing local TMS database. The original database is not migrated or seeded by this preview workflow.

Applying the current migrations to the restored copy exposed PostgreSQL identifier truncation collisions. Five migrations now use explicit, shorter unique or foreign-key constraint names. The restored copy successfully reached 121 applied migrations and the preview seed completed.

The calendar header rendered a literal `${monthLabelFromValue(selectedMonth)}` expression. The JavaScript header argument now uses a template literal; the browser displays the selected month.

Verification: preview `/up` and `/` returned HTTP 200; the copied data contains 1,165 daily reports for the master organization and seven drivers; Blade compilation succeeded; the full disposable SQLite suite passed with 982 tests, 9,791 assertions and 11 skipped. The PostgreSQL clone remains available for interactive review. The snapshot is a copy, so review actions in the preview do not alter the original database.