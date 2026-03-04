<?php

require_once "config.php";

$url = DIRECTLINE_ENDPOINT . "/conversations";

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

$headers = [
    "Authorization: Bearer " . DIRECTLINE_SECRET,
    "Content-Type: application/json"
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

curl_close($ch);

echo $response;
