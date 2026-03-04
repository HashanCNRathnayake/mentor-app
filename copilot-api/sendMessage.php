<?php

require_once "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$conversationId = $data['conversationId'];
$message = $data['message'];

$url = DIRECTLINE_ENDPOINT . "/conversations/" . $conversationId . "/activities";

$payload = [
    "type" => "message",
    "from" => [
        "id" => "user"
    ],
    "text" => $message
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

$headers = [
    "Authorization: Bearer " . DIRECTLINE_SECRET,
    "Content-Type: application/json"
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);

curl_close($ch);

echo $response;
