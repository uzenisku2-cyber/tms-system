<!DOCTYPE html>
<html>

<head>

<title>
TMS Fleet Realtime Tracking
</title>


@vite('resources/js/app.js')


<link
rel="stylesheet"
href="https://unpkg.com/leaflet/dist/leaflet.css"
/>



<style>


body {

    font-family: Arial, sans-serif;

    padding:20px;

}



#map {

    width:900px;

    height:600px;

}



#status {

    font-weight:bold;

    margin-bottom:10px;

}



.info {

    margin-top:15px;

}



.vehicle-icon {

    background:none;

    border:none;

}



.vehicle-box {

    width:45px;

    height:45px;

    font-size:32px;

    display:flex;

    align-items:center;

    justify-content:center;

    transform-origin:center;

    transition:
        transform .3s linear;


}


.vehicle-popup {

    font-size:13px;

}



.moving {

    color:green;

    font-weight:bold;

}


.stopped {

    color:gray;

    font-weight:bold;

}




.auth-panel {
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    align-items:flex-end;
    max-width:900px;
    margin-bottom:15px;
    padding:12px;
    border:1px solid #d1d5db;
    border-radius:6px;
}

.auth-panel label {
    display:flex;
    flex-direction:column;
    gap:4px;
}

.auth-panel input {
    min-width:170px;
    padding:6px;
}

.auth-panel button {
    padding:7px 12px;
}

#auth_status {
    flex-basis:100%;
    font-weight:bold;
}
</style>


</head>


<body>


<h1>
TMS Fleet Realtime Tracking
</h1>


<div class="auth-panel">

<form id="auth-form">

<label>
Email
<input
    id="auth_email"
    type="email"
    autocomplete="username"
    required
/>
</label>

<label>
Password
<input
    id="auth_password"
    type="password"
    autocomplete="current-password"
    required
/>
</label>

<label>
Trip ID
<input
    id="auth_trip_id"
    type="number"
    min="1"
    step="1"
    value="6"
    required
/>
</label>

<button
    id="login_button"
    type="submit">
Log in
</button>

<button
    id="subscribe_button"
    type="button"
    disabled>
Connect trip
</button>

<button
    id="logout_button"
    type="button"
    disabled>
Log out
</button>

</form>

<div
    id="auth_status"
    role="status">
Not authenticated.
</div>

</div>

<div id="status">
AUTHENTICATION REQUIRED
</div>


<div id="map"></div>



<div class="info">


<div>
Vehicle:
<span id="vehicle_name">
-
</span>
</div>


<div>
Trip:
<span id="trip_id">
-
</span>
</div>


<div>
Latitude:
<span id="latitude">
-
</span>
</div>


<div>
Longitude:
<span id="longitude">
-
</span>
</div>


<div>
Speed:
<span id="speed">
-
</span>
</div>


<div>
Heading:
<span id="heading">
-
</span>
</div>


</div>



<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>



<script>





document.addEventListener(
'DOMContentLoaded',
()=>{





const map =
L.map('map')
.setView(
[
50.0755,
14.4378
],
11
);



L.tileLayer(
'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
{
attribution:
'&copy; OpenStreetMap'
}
)
.addTo(map);





// =================================
// STORAGE
// =================================


const vehicles = {};

const pendingRealtime = {};

const routes = {};
let bearerToken = null;
let authenticatedUser = null;
let subscribedTripId = null;
let mapCentered = false;

const authForm =
document.getElementById(
    'auth-form'
);

const emailInput =
document.getElementById(
    'auth_email'
);

const passwordInput =
document.getElementById(
    'auth_password'
);

const tripInput =
document.getElementById(
    'auth_trip_id'
);

const loginButton =
document.getElementById(
    'login_button'
);

const subscribeButton =
document.getElementById(
    'subscribe_button'
);

const logoutButton =
document.getElementById(
    'logout_button'
);

const authStatus =
document.getElementById(
    'auth_status'
);

function setAuthMessage(message)
{
    authStatus.textContent =
    String(message);
}

function setTrackingStatus(message)
{
    document.getElementById(
        'status'
    ).textContent =
    String(message);
}

function setAuthenticatedState(
    authenticated
)
{
    emailInput.disabled =
    authenticated;

    passwordInput.disabled =
    authenticated;

    loginButton.disabled =
    authenticated;

    subscribeButton.disabled =
    !authenticated;

    logoutButton.disabled =
    !authenticated;
}

function apiHeaders(
    includeJson = false
)
{
    const headers = {
        Accept:
        'application/json'
    };

    if(includeJson)
    {
        headers[
            'Content-Type'
        ] =
        'application/json';
    }

    if(bearerToken)
    {
        headers.Authorization =
        `Bearer ${bearerToken}`;
    }

    return headers;
}

function escapeHtml(value)
{
    const entities = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return String(
        value ?? ''
    ).replace(
        /[&<>"']/g,
        character =>
        entities[character]
    );
}

function clearVehicleMarkers()
{
    Object.values(
        vehicles
    ).forEach(
        vehicle =>
        {
            if(
                vehicle.marker &&
                map.hasLayer(
                    vehicle.marker
                )
            )
            {
                map.removeLayer(
                    vehicle.marker
                );
            }
        }
    );

    Object.keys(
        vehicles
    ).forEach(
        key =>
        {
            delete vehicles[key];
        }
    );

    Object.keys(
        pendingRealtime
    ).forEach(
        key =>
        {
            delete pendingRealtime[key];
        }
    );
}





// =================================
// FIT ACTIVE VEHICLES ON MAP
// =================================


function fitVehiclesOnMap()
{


const points = [];



Object.values(
vehicles
)
.forEach(
vehicle =>
{


if(
vehicle.active &&
vehicle.lastUpdate
)
{


const pos =
vehicle.marker.getLatLng();



if(
pos.lat !== 0 &&
pos.lng !== 0
)
{

points.push(
[
pos.lat,
pos.lng
]
);

}


}


}

);





if(
points.length === 0
)
{

    return;
}










if(
points.length === 1
)
{


map.setView(
    points[0],
    14
);


return;


}





map.fitBounds(
points,
{
padding:
[
80,
80
]
}
);


}

// =================================
// HELPERS
// =================================


function setText(
    id,
    value
)
{

    const el =
        document.getElementById(id);


    if(el)
    {

        el.innerText =
            value ?? '-';

    }

}





function getVehicleIcon(
    type
)
{


    switch(type)
    {


        case 'truck':

            return '🚚';



        case 'car':

            return '🚗';



        case 'bus':

            return '🚌';



        default:

            return '🚙';


    }


}
// =================================
// CREATE VEHICLE MARKER
// =================================

function createVehicleMarker(
vehicle
)
{


const id =
Number(vehicle.id);



if(
vehicles[id]
)
{

    return;
}




const icon =
L.divIcon({

    className:
    'vehicle-icon',

    html:
    `
<div
    id="vehicle-${id}"
    class="vehicle-box">
    ${getVehicleIcon(vehicle.vehicle_type)}
</div>
`,

    iconSize:
    [
        45,
        45
    ],

    iconAnchor:
    [
        22,
        22
    ]

});






// =================================
// START WITHOUT GPS
// marker is hidden until first GPS
// =================================


const marker =
L.marker(
[
50.0755,
14.4378
],
{
    icon:icon,

    opacity:0

}
).addTo(map);







const line =
L.polyline(
[],
{
    weight:4
}

)
.addTo(map);







vehicles[id] =
{

    id:id,

    data:vehicle,

    marker:marker,

    line:line,

    route:[],

    lastUpdate:null,

    active:false

};







marker.bindPopup(

`
<div class="vehicle-popup">

<b>
${escapeHtml(vehicle.manufacturer)}
${escapeHtml(vehicle.model)}
</b>

<br>

${escapeHtml(vehicle.registration_number)}

<br>

Status:
WAITING GPS

</div>
`

);












// čekající realtime data

if(
pendingRealtime[id]
)
{

    updateVehiclePosition(
        pendingRealtime[id]
    );


    delete pendingRealtime[id];

}



}


// =================================
// LOAD VEHICLES
// =================================

async function loadVehicles()
{
    if(!bearerToken)
    {
        throw new Error(
            'Authentication is required.'
        );
    }

    const response =
    await fetch(
        '/api/vehicles',
        {
            headers:
            apiHeaders()
        }
    );

    const payload =
    await response
        .json()
        .catch(
            () => null
        );

    if(!response.ok)
    {
        throw new Error(
            payload?.message ??
            'Vehicle API error'
        );
    }

    const vehicleList =
    Array.isArray(payload)
        ? payload
        : payload?.data;

    if(!Array.isArray(vehicleList))
    {
        throw new Error(
            'Vehicle API returned an invalid response.'
        );
    }

    clearVehicleMarkers();


    vehicleList.forEach(
        vehicle =>
        {
            createVehicleMarker(
                vehicle
            );
        }
    );
}
// =================================
// ANIMATE VEHICLE
// =================================


function animateVehicle(
vehicleId,
position
)
{


const vehicle =
vehicles[
Number(vehicleId)
];



if(!vehicle)
{


return;

}




const marker =
vehicle.marker;



const start =
marker.getLatLng();



const end =
{
    lat: Number(position[0]),
    lng: Number(position[1])
};




const steps =
20;



const duration =
1000;



let step =
0;



const latStep =
(
    end.lat -
    start.lat
)
/
steps;



const lngStep =
(
    end.lng -
    start.lng
)
/
steps;





const timer =
setInterval(
()=>{


    step++;



    marker.setLatLng(

        [
            start.lat +
            (
                latStep *
                step
            ),

            start.lng +
            (
                lngStep *
                step
            )

        ]

    );





    if(step >= steps)
    {

        clearInterval(
            timer
        );


        marker.setLatLng(
            position
        );

    }



},
duration / steps
);






// first GPS received

marker.setOpacity(
1
);



vehicle.active =
true;



vehicle.lastUpdate =
new Date();



}

// =================================
// ROTATE VEHICLE
// =================================


function rotateVehicle(
vehicleId,
heading
)
{


const vehicle =
vehicles[
Number(vehicleId)
];



if(!vehicle)
{
    return;
}



const element =
document.getElementById(
`vehicle-${vehicleId}`
);



if(element)
{

element.style.transform =
`
rotate(${heading}deg)
`;

}



}






// =================================
// UPDATE INFO PANEL
// =================================


function updateInfo(
data,
vehicle
)
{


setText(
'vehicle_name',
`${escapeHtml(vehicle.manufacturer)} ${escapeHtml(vehicle.model)}`
);



setText(
'trip_id',
data.trip_id
);



setText(
'latitude',
data.latitude
);



setText(
'longitude',
data.longitude
);



setText(
'speed',
`${data.speed ?? 0} km/h`
);



setText(
'heading',
`${data.heading ?? 0}°`
);



}
// =================================
// UPDATE VEHICLE POSITION
// =================================


function updateVehiclePosition(
data
)
{


const vehicleId =
Number(data.vehicle_id);



const vehicle =
vehicles[vehicleId];



if(!vehicle)
{




pendingRealtime[vehicleId] =
data;



return;

}






// =================================
// UPDATE VEHICLE DATA
// =================================


vehicle.data.vehicle_type =
data.vehicle_type;


vehicle.data.manufacturer =
data.manufacturer;


vehicle.data.model =
data.model;


vehicle.data.registration_number =
data.registration_number;


vehicle.data.color =
data.color;


vehicle.data.speed =
data.speed;








// =================================
// GPS POSITION
// =================================


const position =
[
    Number(data.latitude),
    Number(data.longitude)
];





animateVehicle(
vehicleId,
position
);





rotateVehicle(
vehicleId,
Number(
data.heading ?? 0
)

);






// =================================
// ROUTE
// =================================


vehicle.route.push(
position
);



vehicle.line.setLatLngs(
vehicle.route
);







// =================================
// POPUP UPDATE
// =================================


vehicle.marker.setPopupContent(

`
<div class="vehicle-popup">


<b>
${escapeHtml(vehicle.data.manufacturer)}
${escapeHtml(vehicle.data.model)}
</b>


<br>


${escapeHtml(vehicle.data.registration_number)}


<br>


Type:
${vehicle.data.vehicle_type ?? '-'}


<br>


Speed:
${data.speed ?? 0}
km/h


<br>


Heading:
${data.heading ?? 0}°


<br>


Status:
${data.status ?? '-'}


<br>


Last:
${data.last_seen_at ?? '-'}


</div>
`

);







// =================================
// INFO PANEL
// =================================


updateInfo(
data,
vehicle.data
);







// =================================
// STATUS
// =================================


document.getElementById(
'status'
)
.innerText =

'CONNECTED - LIVE GPS';



}









// =================================
// REALTIME GPS
// =================================

function subscribeToTrip()
{
    if(!bearerToken)
    {
        throw new Error(
            'Log in before connecting to a trip.'
        );
    }

    if(!window.TmsRealtime)
    {
        throw new Error(
            'Realtime client is not available.'
        );
    }

    const tripId =
    Number.parseInt(
        tripInput.value,
        10
    );

    if(
        !Number.isInteger(tripId) ||
        tripId <= 0
    )
    {
        throw new Error(
            'Trip ID must be a positive integer.'
        );
    }

    mapCentered = false;

    setTrackingStatus(
        `SUBSCRIBING TO PRIVATE TRIP ${tripId}`
    );

    const channel =
    window.TmsRealtime
        .subscribeTrip(
            tripId,
            data =>
            {

                updateVehiclePosition(
                    data
                );

                if(!mapCentered)
                {
                    const active =
                    Object.values(
                        vehicles
                    ).filter(
                        vehicle =>
                        vehicle.active
                    );

                    if(
                        active.length >= 2
                    )
                    {
                        setTimeout(
                            () =>
                            {
                                fitVehiclesOnMap();

                                mapCentered = true;
                            },
                            500
                        );
                    }
                }
            }
        );

    if(
        channel &&
        typeof channel.error ===
        'function'
    )
    {
        channel.error(
            error =>
            {

                setTrackingStatus(
                    'PRIVATE CHANNEL AUTHORIZATION FAILED'
                );
            }
        );
    }

    subscribedTripId =
    tripId;

    setAuthMessage(
        `Authenticated. Private trip ${tripId} selected.`
    );
}

async function login()
{
    const email =
    emailInput.value.trim();

    const password =
    passwordInput.value;

    if(
        email === '' ||
        password === ''
    )
    {
        throw new Error(
            'Email and password are required.'
        );
    }

    loginButton.disabled = true;

    setAuthMessage(
        'Signing in...'
    );

    const response =
    await fetch(
        '/api/v1/auth/login',
        {
            method:
            'POST',

            headers: {
                Accept:
                'application/json',

                'Content-Type':
                'application/json'
            },

            body:
            JSON.stringify({
                email,
                password
            })
        }
    );

    const payload =
    await response
        .json()
        .catch(
            () => null
        );

    if(!response.ok)
    {
        throw new Error(
            payload?.message ??
            'Login failed.'
        );
    }

    const token =
    payload?.data?.token ??
    payload?.token;

    if(
        typeof token !== 'string' ||
        token.trim() === ''
    )
    {
        throw new Error(
            'Login response does not contain a bearer token.'
        );
    }

    bearerToken =
    token;

    authenticatedUser =
    payload?.data?.user ??
    payload?.user ??
    null;

    if(!window.TmsRealtime)
    {
        throw new Error(
            'Realtime application module is unavailable.'
        );
    }

    window.TmsRealtime.connect(
        bearerToken
    );

    await loadVehicles();

    setAuthenticatedState(
        true
    );

    subscribeToTrip();

    setAuthMessage(
        `Authenticated as ${
            authenticatedUser?.email ??
            email
        }.`
    );
}

function resetAuthentication()
{
    if(window.TmsRealtime)
    {
        window.TmsRealtime.disconnect();
    }

    bearerToken = null;
    authenticatedUser = null;
    subscribedTripId = null;
    mapCentered = false;

    passwordInput.value = '';

    clearVehicleMarkers();

    setAuthenticatedState(
        false
    );

    setTrackingStatus(
        'AUTHENTICATION REQUIRED'
    );
}

async function logout()
{
    if(!bearerToken)
    {
        resetAuthentication();

        setAuthMessage(
            'Not authenticated.'
        );

        return;
    }

    setAuthMessage(
        'Signing out...'
    );

    try
    {
        await fetch(
            '/api/v1/auth/logout',
            {
                method:
                'POST',

                headers:
                apiHeaders(
                    true
                )
            }
        );
    }
    finally
    {
        resetAuthentication();

        setAuthMessage(
            'Logged out.'
        );
    }
}

authForm.addEventListener(
    'submit',
    async event =>
    {
        event.preventDefault();

        try
        {
            await login();
        }
        catch(error)
        {

            resetAuthentication();

            setAuthMessage(
                error.message
            );
        }
        finally
        {
            if(!bearerToken)
            {
                loginButton.disabled =
                false;
            }
        }
    }
);

subscribeButton.addEventListener(
    'click',
    () =>
    {
        try
        {
            subscribeToTrip();
        }
        catch(error)
        {

            setAuthMessage(
                error.message
            );
        }
    }
);

logoutButton.addEventListener(
    'click',
    async () =>
    {
        try
        {
            await logout();
        }
        catch(error)
        {

            resetAuthentication();

            setAuthMessage(
                error.message
            );
        }
    }
);

setAuthenticatedState(
    false
);

setTrackingStatus(
    'AUTHENTICATION REQUIRED'
);
// =================================
// OFFLINE WATCHDOG
// =================================


setInterval(
()=>{


Object.values(
vehicles
)
.forEach(
vehicle =>
{


if(!vehicle.lastUpdate)
{
    return;
}



const diff =
Date.now()
-
vehicle.lastUpdate.getTime();




if(
diff > 60000
)
{


vehicle.marker.setPopupContent(

`
<div class="vehicle-popup">


<b>
${escapeHtml(vehicle.data.manufacturer)}
${escapeHtml(vehicle.data.model)}
</b>


<br>


${escapeHtml(vehicle.data.registration_number)}


<br>


<span class="stopped">
● OFFLINE
</span>


<br>


Last:
${escapeHtml(vehicle.lastUpdate?.toISOString?.() ?? vehicle.lastUpdate)}

</div>

`

);


}



}

);


},
10000
);



// =================================
// SCRIPT READY
// =================================




// =================================
// CLOSE DOM CONTENT LOADED
// =================================

}
);


</script>