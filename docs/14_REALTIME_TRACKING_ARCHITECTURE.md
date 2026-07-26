# TMS Realtime Tracking Architecture v1.0

## 1. Purpose

This document describes the implemented realtime vehicle-tracking architecture, its security boundaries and the required verification rules.

## 2. Main Components

The implementation uses Laravel API routes, Sanctum bearer authentication, persisted vehicle positions, `TripLocationUpdated`, `UpdateTripRealtimeProjection`, `TripRealtimeBroadcast`, Laravel Reverb and Laravel Echo.

Key files:

- `backend/routes/api.php`
- `backend/bootstrap/app.php`
- `backend/routes/channels.php`
- `backend/app/Events/TripRealtimeBroadcast.php`
- `backend/resources/js/app.js`
- `backend/resources/js/realtime/tripRealtime.js`
- `backend/resources/views/realtime-test.blade.php`

## 3. GPS Data Flow

1. An authenticated GPS request reaches the protected API.
2. Input values are validated.
3. Vehicle ownership is verified.
4. Trip ownership is verified.
5. The vehicle and trip relationship is verified.
6. The position is persisted.
7. `TripLocationUpdated` is dispatched.
8. `UpdateTripRealtimeProjection` updates current realtime state.
9. `TripRealtimeBroadcast` broadcasts the accepted update.
10. Laravel Reverb transports the event.
11. An authorized Echo subscriber receives it.

Rejected input must not update persistence, projection or broadcast state.

## 4. API Authentication

Protected realtime APIs use Laravel Sanctum.

The client sends:

```text
Accept: application/json
Authorization: Bearer <token>
```

Authentication never replaces server-side ownership validation.

## 5. Broadcast Authorization

The authorization endpoint is `/broadcasting/auth`.

It uses the `api` and `auth:sanctum` middleware.

Expected results:

| Request | Result |
|---|---:|
| Guest | HTTP 401 |
| Trip owner | HTTP 200 |
| Foreign authenticated user | HTTP 403 |

## 6. Private Channel Contract

The logical channel is `trip.{tripId}`.

The server event uses `PrivateChannel`. The frontend subscribes with:

```javascript
echo.private(`trip.${tripId}`)
```

The exact event name is `.trip.position.updated`.

Public `.channel()` subscriptions are forbidden for GPS data.

## 7. Ownership Boundary

Knowledge of a trip ID does not grant access. The channel callback resolves the trip and compares its owner with the authenticated Sanctum user.

## 8. Browser Authentication Lifecycle

The diagnostic frontend logs in through `POST /api/v1/auth/login` and logs out through `POST /api/v1/auth/logout`.

The bearer token is used for protected vehicle loading and `/broadcasting/auth`.

## 9. Bearer Token Rules

The token remains only in page memory.

It must not be stored in `localStorage`, `sessionStorage`, query parameters, logs, committed source code or documentation as a real value.

A page reload intentionally requires a new login.

## 10. Single Echo Client Rule

Only one `new Echo()` instance may exist in the frontend entry graph.

`tripRealtime.js` owns the Echo client. `app.js` exposes the application-facing facade without creating another client.

## 11. Dynamic Trip Subscription

Trip IDs must be positive integers. Before subscribing to another trip, the frontend leaves the previous private channel.

A hard-coded channel such as `trip.6` must not be used as authorization.

## 12. Protected Vehicle Loading

Vehicles are loaded only after login and with the bearer token. The API remains responsible for ownership filtering.

## 13. Frontend HTML Safety

Values inserted into Leaflet popup HTML are escaped through `escapeHtml`. Ordinary status messages use `textContent`.

## 14. Local Diagnostic Page

`/realtime-test` is registered only when:

```php
app()->environment('local')
```

Expected behavior:

| Environment | Result |
|---|---:|
| `local` | HTTP 200 |
| `testing` | HTTP 404 |
| `production` | route absent |

The page is not a production user interface.

## 15. Logging and Secrets

The broadcast event does not log the complete GPS payload.

Reverb secrets, database passwords, Laravel application keys, bearer tokens and user passwords must remain outside committed frontend source and documentation.

## 16. Realtime Projection

Current realtime projection, persisted position history, broadcast events, dashboard presentation, reporting and audit records remain separate responsibilities.

## 17. Verified Tests

Primary coverage:

- `BroadcastChannelSecurityTest.php`
- `RealtimeVehicleSecurityTest.php`
- `TripRealtimeProjectionTest.php`
- `TripRealtimeApiTest.php`
- `TripSecurityTest.php`
- `DashboardTest.php`

The tests verify private channels, guest rejection, owner authorization, foreign-user rejection, local-only diagnostics, vehicle security, projection, realtime API and dashboard regression.

## 18. Required Pre-Commit Checks

Before commit:

1. Run PHP syntax checks.
2. Run JavaScript syntax checks.
3. Run the Vite production build.
4. Run realtime security and regression tests.
5. Run a secret scan.
6. Run `git diff --check`.
7. Verify explicitly staged files.

## 19. Architecture Invariants

- Realtime GPS channels remain private.
- Broadcast authorization uses `auth:sanctum`.
- Trip ownership is checked server-side.
- The frontend uses one Echo client.
- Bearer tokens remain in memory only.
- Protected API calls use bearer authentication.
- Previous channels are left before switching trips.
- Popup values are escaped.
- Realtime payloads are not logged indiscriminately.
- `/realtime-test` remains local-only.
- Production secrets remain server-side.
- Security and regression tests pass before commit.

## 20. Final Design Rule

Authentication, ownership authorization, persisted position, realtime projection, broadcast event, WebSocket transport, frontend presentation, logging and audit are separate responsibilities.

A successful WebSocket connection is not authorization, and knowledge of a trip ID is not ownership.

## Canonical Vehicle Model

`App\Modules\Fleet\Models\Vehicle` is the canonical Eloquent model for the `vehicles` table.

Application services, controllers, events, relationships, factories and tests must use the canonical Fleet model.

`App\Models\Vehicle` remains only as a deprecated compatibility bridge that extends the canonical model. It must not contain independent fillable fields, casts, relationships or business behavior.

New code must not import `App\Models\Vehicle`.
