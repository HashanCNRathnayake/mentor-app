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

/* --------------------------------
Create conversation if needed
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

/* --------------------------------
Send message
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

curl_exec($ch);

curl_close($ch);


/* --------------------------------
Wait and fetch bot messages
-------------------------------- */

sleep(5);

$url = DIRECTLINE_ENDPOINT . "/conversations/" . $conversationId . "/activities";

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

curl_close($ch);

$data = json_decode($response, true);

$messages = [];

/* --------------------------------
Extract bot messages
-------------------------------- */

// foreach ($data['activities'] as $activity) {

//     if (isset($activity['from']['role']) && $activity['from']['role'] == "bot") {

//         /* TEXT OR HTML MESSAGE */

//         if (isset($activity['text']) && $activity['text'] != "") {

//             if (str_contains($activity['text'], "<table") || str_contains($activity['text'], "<h")) {

//                 $messages[] = [
//                     "type" => "html",
//                     "content" => $activity['text']
//                 ];
//             } else {

//                 $messages[] = [
//                     "type" => "text",
//                     "content" => $activity['text']
//                 ];
//             }
//         }

//         /* SUGGESTED ACTIONS */

//         if (isset($activity['suggestedActions'])) {

//             $buttons = [];

//             foreach ($activity['suggestedActions']['actions'] as $action) {

//                 $buttons[] = [
//                     "title" => $action['title'],
//                     "value" => $action['value']
//                 ];
//             }

//             $messages[] = [
//                 "type" => "actions",
//                 "buttons" => $buttons
//             ];
//         }
//     }
// }

echo json_encode([
    // "conversationId" => $conversationId,
    // "messages" => $messages
    $data
]);
