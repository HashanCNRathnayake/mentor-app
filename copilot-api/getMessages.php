<?php

require_once "config.php";

$conversationId = $_GET['conversationId'];

$url = DIRECTLINE_ENDPOINT . "/conversations/" . $conversationId . "/activities";

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$headers = [
    "Authorization: Bearer " . DIRECTLINE_SECRET
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

curl_close($ch);

echo $response;
