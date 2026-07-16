<?php

$points = [

    [
        "latitude" => 50.100,
        "longitude" => 14.450,
        "speed" => 80,
        "heading" => 90,
    ],

    [
        "latitude" => 50.105,
        "longitude" => 14.460,
        "speed" => 70,
        "heading" => 90,
    ],

    [
        "latitude" => 50.110,
        "longitude" => 14.470,
        "speed" => 65,
        "heading" => 90,
    ],

    [
        "latitude" => 50.115,
        "longitude" => 14.480,
        "speed" => 60,
        "heading" => 90,
    ],

];


foreach ($points as $point) {


    $data = array_merge(
        [
            "trip_id" => 6
        ],
        $point
    );


    echo "Sending GPS:\n";

    print_r($data);



    $ch = curl_init(
        "http://localhost/api/gps/update"
    );


    curl_setopt(
        $ch,
        CURLOPT_POST,
        true
    );


    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        [
            "Content-Type: application/json"
        ]
    );


    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        json_encode($data)
    );


    curl_setopt(
        $ch,
        CURLOPT_RETURNTRANSFER,
        true
    );


    $response = curl_exec($ch);


    curl_close($ch);


    echo $response . PHP_EOL;


    sleep(5);

}