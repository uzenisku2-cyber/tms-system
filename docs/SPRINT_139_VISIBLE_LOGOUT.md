# Sprint 139 – Visible logout

The active DRAYVIA preview overlay, the underlying application header and the People page offer a visible logout action. All invoke the existing token revocation endpoint and clear the browser session even if the endpoint is temporarily unavailable.

Logging out closes the preview overlay, returns to the sign-in screen and removes organization capabilities from session storage.
