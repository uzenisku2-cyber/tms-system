\# TMS System API Documentation



\## Overview



Transport Management System API.



API Version:



v1



Base URL:



http://localhost:8000/api/v1



All API responses are JSON.



Framework:



Laravel



Authentication:



Laravel Sanctum



Database:



PostgreSQL



Container:



Docker



\---



\# Authentication



The API uses Laravel Sanctum authentication.



Protected endpoints require:



Authorization header:



Authorization: Bearer {token}



Content type:



application/json



\---



\# Authentication Endpoints



\## Login



Method:



POST



Endpoint:



/auth/login



Successful response:



200 OK



Request example:



{

&#x20;   "email": "user@example.com",

&#x20;   "password": "password"

}



Response example:



{

&#x20;   "token": "xxxxxxxx",

&#x20;   "user": {

&#x20;       "id": 1,

&#x20;       "name": "User"

&#x20;   }

}



\---



\## Current User



Method:



GET



Endpoint:



/auth/me



Successful response:



200 OK



Returns authenticated user.



\---



\## Logout



Method:



POST



Endpoint:



/auth/logout



Successful response:



200 OK



Invalidates current token.



\---



\# Trips



\## List Trips



Method:



GET



Endpoint:



/trips



Successful response:



200 OK



Returns trips list.



\---



\## Create Trip



Method:



POST



Endpoint:



/trips



Successful response:



201 Created



Request example:



{

&#x20;   "origin": "Praha",

&#x20;   "destination": "Brno",

&#x20;   "origin\_lat": 50.0755,

&#x20;   "origin\_lng": 14.4378,

&#x20;   "destination\_lat": 49.1951,

&#x20;   "destination\_lng": 16.6068

}



\---



\## Trip Detail



Method:



GET



Endpoint:



/trips/{trip}



Successful response:



200 OK



\---



\## Update Trip



Method:



PATCH



Endpoint:



/trips/{trip}



Successful response:



200 OK



\---



\## Delete Trip



Method:



DELETE



Endpoint:



/trips/{trip}



Successful response:



200 OK



\---



\## Assign Driver



Method:



POST



Endpoint:



/trips/{trip}/assign



Successful response:



200 OK



\---



\## Start Trip



Method:



POST



Endpoint:



/trips/{trip}/start



Successful response:



200 OK



Changes trip status to started.



\---



\## Finish Trip



Method:



POST



Endpoint:



/trips/{trip}/finish



Successful response:



200 OK



Changes trip status to finished.



\---



\## Trip Timeline



Method:



GET



Endpoint:



/trips/{trip}/timeline



Successful response:



200 OK



Returns trip status history and events.



\---



\## Proof Of Delivery



Method:



POST



Endpoint:



/trips/{trip}/pod



Successful response:



200 OK



Stores delivery confirmation.



\---



\# Tracking



\## Send GPS Location



Method:



POST



Endpoint:



/trips/{trip}/locations



Successful response:



201 Created



Request example:



{

&#x20;   "latitude": 50.0755,

&#x20;   "longitude": 14.4378,

&#x20;   "speed": 60,

&#x20;   "heading": 180

}



Validation:



latitude:



\- required

\- range -90 to 90





longitude:



\- required

\- range -180 to 180





speed:



\- minimum 0



\---



\## Tracking History



Method:



GET



Endpoint:



/trips/{trip}/tracking



Successful response:



200 OK



Returns GPS location history.



\---



\## Active Live Trips



Method:



GET



Endpoint:



/trips/active/live



Successful response:



200 OK



Returns active trips with current tracking data.



\---



\# Live Monitoring



\## Live Trip Status



Method:



GET



Endpoint:



/trips/{trip}/live



Successful response:



200 OK



Returns:



\- trip status

\- driver

\- vehicle

\- latest GPS position

\- GPS status

\- active alerts



Response example:



{

&#x20;   "trip\_id": 1,

&#x20;   "status": "started",

&#x20;   "gps": {

&#x20;       "status": "fresh",

&#x20;       "age\_seconds": 10

&#x20;   },

&#x20;   "alerts": \[]

}



\---



\# ETA



\## Trip ETA



Method:



GET



Endpoint:



/trips/{trip}/eta



Successful response:



200 OK



Response example:



{

&#x20;   "distance\_km": 120.5,

&#x20;   "estimated\_minutes": 105,

&#x20;   "arrival\_time": "2026-07-12T18:30:00"

}



\---



\# Trip Progress



Method:



GET



Endpoint:



/trips/{trip}/progress



Successful response:



200 OK



Returns current trip progress.

\---



\# Alerts



\## List Alerts



Method:



GET



Endpoint:



/alerts



Successful response:



200 OK



Returns all alerts.



\---



\## Open Alerts



Method:



GET



Endpoint:



/alerts/open



Successful response:



200 OK



Returns unresolved alerts.



\---



\## Alert History



Method:



GET



Endpoint:



/alerts/history



Successful response:



200 OK



Returns resolved alerts.



\---



\## Unread Alerts



Method:



GET



Endpoint:



/alerts/unread



Successful response:



200 OK



Returns unread alerts.



\---



\## Alert Summary



Method:



GET



Endpoint:



/alerts/summary



Successful response:



200 OK



Returns alert counters.



\---



\## Alert Detail



Method:



GET



Endpoint:



/alerts/{alert}



Successful response:



200 OK



Returns alert details.



\---



\## Mark Alert Read



Method:



PATCH



Endpoint:



/alerts/{alert}/read



Successful response:



200 OK



Marks alert as read.



\---



\## Mark All Alerts Read



Method:



PATCH



Endpoint:



/alerts/read-all



Successful response:



200 OK



Marks all alerts as read.



\---



\## Resolve Alert



Method:



PATCH



Endpoint:



/alerts/{alert}/resolve



Successful response:



200 OK



Resolves an active alert.



\---



\# Dashboard



\## Dashboard Overview



Method:



GET



Endpoint:



/dashboard



Successful response:



200 OK



Returns:



\- trips statistics

\- drivers statistics

\- vehicles statistics

\- active trips

\- alerts

\- notifications



\---



\## Dashboard Metrics



Method:



GET



Endpoint:



/dashboard/metrics



Successful response:



200 OK



Returns monitoring metrics.



\---



\## Dashboard KPI



Method:



GET



Endpoint:



/dashboard/kpi



Successful response:



200 OK



Returns:



\- active trips

\- finished today

\- open alerts

\- gps lost

\- eta delay

\- vehicle idle

\- critical alerts



\---



\# Drivers



\## List Drivers



Method:



GET



Endpoint:



/drivers



Successful response:



200 OK



Returns all drivers.



\---



\## Create Driver



Method:



POST



Endpoint:



/drivers



Successful response:



201 Created



Creates a new driver.



\---



\## Available Drivers



Method:



GET



Endpoint:



/drivers/available



Successful response:



200 OK



Returns drivers available for assignment.



\---



\## Driver Detail



Method:



GET



Endpoint:



/drivers/{driver}



Successful response:



200 OK



Returns driver details.



\---



\## Update Driver



Method:



PATCH



Endpoint:



/drivers/{driver}



Successful response:



200 OK



Updates driver information.



\---



\## Delete Driver



Method:



DELETE



Endpoint:



/drivers/{driver}



Successful response:



200 OK



Deletes driver.



\---



\# Vehicles



\## List Vehicles



Method:



GET



Endpoint:



/vehicles



Successful response:



200 OK



Returns all vehicles.



\---



\## Create Vehicle



Method:



POST



Endpoint:



/vehicles



Successful response:



201 Created



Creates a new vehicle.



\---



\## Available Vehicles



Method:



GET



Endpoint:



/vehicles/available



Successful response:



200 OK



Returns vehicles available for assignment.



\---



\## Vehicle Detail



Method:



GET



Endpoint:



/vehicles/{vehicle}



Successful response:



200 OK



Returns vehicle details.



\---



\## Update Vehicle



Method:



PATCH



Endpoint:



/vehicles/{vehicle}



Successful response:



200 OK



Updates vehicle information.



\---



\## Delete Vehicle



Method:



DELETE



Endpoint:



/vehicles/{vehicle}



Successful response:



200 OK



Deletes vehicle.



\---



\# Notifications



\## List Notifications



Method:



GET



Endpoint:



/notifications



Successful response:



200 OK



Returns user notifications.



\---



\## Unread Notifications



Method:



GET



Endpoint:



/notifications/unread



Successful response:



200 OK



Returns unread notifications.



\---



\## Mark Notification Read



Method:



PATCH



Endpoint:



/notifications/{id}/read



Successful response:



200 OK



Marks notification as read.



\---



\## Mark All Notifications Read



Method:



PATCH



Endpoint:



/notifications/read-all



Successful response:



200 OK



Marks all notifications as read.



\---



\# Reports



\## Summary Report



Method:



GET



Endpoint:



/reports/summary



Successful response:



200 OK



Returns system summary statistics.



\---



\## Drivers Report



Method:



GET



Endpoint:



/reports/drivers



Successful response:



200 OK



Returns driver performance report.



\---



\## Driver Detail Report



Method:



GET



Endpoint:



/reports/drivers/{driver}



Successful response:



200 OK



Returns detailed driver report.



\---



\## Vehicles Report



Method:



GET



Endpoint:



/reports/vehicles



Successful response:



200 OK



Returns vehicle performance report.



\---



\## Vehicle Detail Report



Method:



GET



Endpoint:



/reports/vehicles/{vehicle}



Successful response:



200 OK



Returns detailed vehicle report.



\---



\# Error Responses



\## Unauthorized



HTTP Status:



401 Unauthorized



Response:



{

&#x20;   "error": true,

&#x20;   "message": "Unauthenticated."

}



\---



\## Forbidden



HTTP Status:



403 Forbidden



Response:



{

&#x20;   "message": "Forbidden"

}



\---



\## Not Found



HTTP Status:



404 Not Found



Response:



{

&#x20;   "error": true,

&#x20;   "message": "Resource not found."

}



\---



\## Validation Error



HTTP Status:



422 Unprocessable Entity



Response:



{

&#x20;   "error": true,

&#x20;   "message": "Validation failed",

&#x20;   "errors": {

&#x20;       "field": \[

&#x20;           "Validation message"

&#x20;       ]

&#x20;   }

}



\---



\# Project Status



Automated tests:



61 tests passed



181 assertions



Covered modules:



\- Authentication

\- Trips

\- Tracking

\- Live Monitoring

\- ETA

\- Alerts

\- Dashboard

\- Drivers

\- Vehicles

\- Notifications

\- Reports

