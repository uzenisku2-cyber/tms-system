<!DOCTYPE html>
<html>

<head>

    <title>TMS Realtime Tracking</title>

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
            margin:15px 0;
        }


        .info {
            margin-top:15px;
        }


        #vehicle-marker {

            width:50px;
            height:50px;

            transform-origin:center center;

            transition:
                transform .2s linear;

        }


    </style>

</head>


<body>


<h1>TMS Realtime Tracking</h1>


<div id="status">
    Connecting websocket...
</div>


<div id="map"></div>



<div class="info">


<div>
Trip:
<span id="trip_id">-</span>
</div>


<div>
Latitude:
<span id="latitude">-</span>
</div>


<div>
Longitude:
<span id="longitude">-</span>
</div>


<div>
Speed:
<span id="speed">-</span>
</div>


<div>
Heading:
<span id="heading">-</span>
</div>


</div>



<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>



<script>


document.addEventListener(
'DOMContentLoaded',
()=>{


// ==========================
// MAP
// ==========================


const map =
L.map('map')
.setView(
    [
        50.0755,
        14.4378
    ],
    13
);



L.tileLayer(
'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
{
    attribution:
    '&copy; OpenStreetMap'
}
)
.addTo(map);




// ==========================
// VEHICLE ICON
// ==========================


const vehicleIcon =
L.divIcon({

html:
`
<div id="vehicle-marker">


<svg
width="50"
height="50"
viewBox="0 0 100 100"
xmlns="http://www.w3.org/2000/svg"
>


<!-- návěs -->

<rect
x="30"
y="35"
width="40"
height="45"
rx="5"
fill="#1976d2"
/>



<!-- kabina -->

<rect
x="30"
y="10"
width="40"
height="30"
rx="5"
fill="#ff9800"
/>



<!-- okno -->

<rect
x="40"
y="15"
width="20"
height="10"
fill="#90caf9"
/>



<!-- kola -->

<circle
cx="40"
cy="85"
r="7"
fill="#222"
/>


<circle
cx="60"
cy="85"
r="7"
fill="#222"
/>



</svg>


</div>
`,

className:'',

iconSize:[
    50,
    50
],

iconAnchor:[
    25,
    25
]

});




// ==========================
// VEHICLE MARKER
// ==========================


window.vehicleMarker =
L.marker(
[
    50.0755,
    14.4378
],
{
    icon:vehicleIcon
}
)
.addTo(map);





// ==========================
// ROUTE
// ==========================


let routePoints=[];



const routeLine =
L.polyline(
[],
{
    weight:5
}
)
.addTo(map);





// ==========================
// UI
// ==========================


function updateInfo(data)
{


document.getElementById('trip_id')
.innerText =
data.trip_id ?? '-';


document.getElementById('latitude')
.innerText =
data.latitude ?? '-';


document.getElementById('longitude')
.innerText =
data.longitude ?? '-';


document.getElementById('speed')
.innerText =
(data.speed ?? '-')
+
' km/h';


document.getElementById('heading')
.innerText =
(data.heading ?? '-')
+
'°';


}






// ==========================
// ROTATION
// ==========================


function rotateVehicle(heading)
{


const icon =
document.getElementById(
'vehicle-marker'
);



if(icon)
{

icon.style.transform =
`rotate(${heading}deg)`;

}


}






// ==========================
// ANIMATION
// ==========================


function animateMarker(target)
{


const start =
window.vehicleMarker
.getLatLng();



const startLat =
start.lat;


const startLng =
start.lng;



const endLat =
target[0];


const endLng =
target[1];



const duration =
1000;



const startTime =
performance.now();




function frame(time)
{


const progress =
Math.min(
(time-startTime)
/duration,
1
);



const lat =
startLat
+
(
endLat-startLat
)
*
progress;



const lng =
startLng
+
(
endLng-startLng
)
*
progress;



window.vehicleMarker
.setLatLng(
[
lat,
lng
]
);



if(progress < 1)
{

requestAnimationFrame(
frame
);

}


}



requestAnimationFrame(
frame
);


}







// ==========================
// HISTORY
// ==========================


fetch(
'/api/vehicles/4/positions'
)

.then(
response =>
response.json()
)

.then(
data =>
{


console.log(
'HISTORY LOADED',
data
);



routePoints =
data.map(
p =>
[
Number(p.latitude),
Number(p.longitude)
]
);



routeLine
.setLatLngs(
routePoints
);



if(routePoints.length)
{


const last =
routePoints[
routePoints.length-1
];



window.vehicleMarker
.setLatLng(
last
);



const lastData =
data[
data.length-1
];



updateInfo(
lastData
);



rotateVehicle(
Number(lastData.heading)
);



document.getElementById('status')
.innerText =
'CONNECTED - HISTORY';



map.fitBounds(
routeLine.getBounds()
);


}


});







// ==========================
// REALTIME
// ==========================


Echo.channel(
'trip.6'
)


.listen(
'.trip.position.updated',
(data)=>
{


console.log(
'REALTIME GPS UPDATE',
data
);



const position =
[
Number(data.latitude),
Number(data.longitude)
];



animateMarker(
position
);



rotateVehicle(
Number(data.heading)
);



routePoints.push(
position
);



routeLine
.setLatLngs(
routePoints
);



map.panTo(
position
);



document.getElementById('status')
.innerText =
'CONNECTED - LIVE GPS';



updateInfo(
data
);


}

);



});



</script>


</body>

</html>