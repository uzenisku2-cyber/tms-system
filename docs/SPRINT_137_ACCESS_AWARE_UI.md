# Sprint 137: Organization-aware access in the operational UI

The carrier manager sees only actions available in the selected organization. The authenticated `auth/capabilities` endpoint applies the existing organization middleware and resolves permissions in that team's context. The application menu and embedded settings use these capabilities. The people screen loads all carriers only for users with `users.manage`; a carrier manager continues to manage their own organization.

Draft edit and delete controls are presented only for the original entry actor with the relevant driver or delegated-entry permission; the server's write service remains authoritative. The UI never grants authorization. Manual checking must cover a master administrator, carrier manager plus driver, a driver-only account, and a carrier manager without a driver profile. The existing API denies finance to a carrier manager.

Deploy after review and tests; the installer only creates a worktree and places source changes. No persistent database changes are required.
