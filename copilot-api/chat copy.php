<?php

require_once "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$conversationId = $data['conversationId'] ?? null;
$message = $data['message'];

$email = $data['email'];
$name = $data['name'];
$role = $data['role'] ?? "user";

/* ---------------------------------------
If conversation does not exist create one
--------------------------------------- */

if (!$conversationId) {

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

    $conversation = json_decode($response, true);

    $conversationId = $conversation['conversationId'];

    /* ---- SEND START MESSAGE ---- */

    $url = DIRECTLINE_ENDPOINT . "/conversations/" . $conversationId . "/activities";

    $payload = [
        "type" => "message",
        "from" => [
            "id" => $email,
            "name" => $name
        ],
        "text" => "Start",
        "channelData" => [
            "systemActivityFromEmail" => $email
        ]
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    curl_exec($ch);

    curl_close($ch);

    sleep(2);
}
/* ---------------------------------------
Send message to Copilot
--------------------------------------- */

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

$headers = [
    "Authorization: Bearer " . DIRECTLINE_SECRET,
    "Content-Type: application/json"
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

curl_exec($ch);
curl_close($ch);

/* ---------------------------------------
Wait for bot response
--------------------------------------- */

$reply = "";
$attempts = 0;

while ($attempts < 6 && $reply == "") {

    sleep(1);

    $url = DIRECTLINE_ENDPOINT . "/conversations/" . $conversationId . "/activities";

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    curl_close($ch);

    $data = json_decode($response, true);

    $reply = "";

    if (isset($data['activities'])) {

        foreach ($data['activities'] as $activity) {

            if (isset($activity['from']['role']) && $activity['from']['role'] === "bot") {

                /* TEXT RESPONSE */

                if (isset($activity['text']) && $activity['text'] != "") {
                    $reply = $activity['text'];
                }

                /* HTML RESPONSE */

                if (isset($activity['attachments'])) {

                    foreach ($activity['attachments'] as $attachment) {

                        if (isset($attachment['contentType'])) {

                            if ($attachment['contentType'] === "text/html") {
                                $reply = $attachment['content'];
                            }

                            if ($attachment['contentType'] === "application/vnd.microsoft.card.adaptive") {

                                if (isset($attachment['content']['body'])) {
                                    $reply = json_encode($attachment['content']);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    $attempts++;
}
$data = json_decode($response, true);

$reply = "";

if (isset($data['activities'])) {

    foreach ($data['activities'] as $activity) {

        if (isset($activity['from']['role']) && $activity['from']['role'] === "bot") {
            if (isset($activity['text'])) {
                $reply = $activity['text'];
            }
        }
    }
}

echo json_encode([
    "conversationId" => $conversationId,
    "reply" => $reply
]);
