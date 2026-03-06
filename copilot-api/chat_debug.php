<?php

require_once "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$conversationId = $data['conversationId'] ?? null;
$message = $data['message'];

$email = $data['email'];
$name = $data['name'];
$role = $data['role'] ?? "user";

$headers = [
    "Authorization: Bearer " . DIRECTLINE_SECRET,
    "Content-Type: application/json"
];

/* -------------------------------
Create conversation if not exists
-------------------------------- */

if (!$conversationId) {

    $url = DIRECTLINE_ENDPOINT . "/conversations";

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $conversation = json_decode($response, true);

    $conversationId = $conversation['conversationId'];
}

/* -------------------------------
Send user message
-------------------------------- */

$url = DIRECTLINE_ENDPOINT . "/conversations/" . $conversationId . "/activities";

$payload = [
    "type" => "message",
    "from" => [
        "id" => $email,
        "name" => $name
    ],
    "text" => $message,
    "channelData" => [
        "systemActivityFromEmail" => $email,
        "email" => $email,
        "name" => $name,
        "role" => $role
    ]
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$sendResponse = curl_exec($ch);
curl_close($ch);

/* -------------------------------
Wait for bot reply
-------------------------------- */

$botResponse = null;
$attempts = 0;

while ($attempts < 10) {

    sleep(1);

    $url = DIRECTLINE_ENDPOINT . "/conversations/" . $conversationId . "/activities";

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['activities']) && count($data['activities']) > 1) {
        $botResponse = $data;
        break;
    }

    $attempts++;
}
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$botResponse = curl_exec($ch);
curl_close($ch);

/* -------------------------------
Return EVERYTHING
-------------------------------- */

echo json_encode([
    "conversationId" => $conversationId,
    "sendResponse" => json_decode($sendResponse, true),
    "botResponse" => json_decode($botResponse, true)
], JSON_PRETTY_PRINT);
