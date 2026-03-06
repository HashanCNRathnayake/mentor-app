<?php

function createConversation()
{

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

    return json_decode($response, true);
}



function sendMessageToCopilot($conversationId, $message, $email, $name, $role)
{

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

    $response = curl_exec($ch);

    curl_close($ch);

    return json_decode($response, true);
}



function getBotReply($conversationId)
{

    $url = DIRECTLINE_ENDPOINT . "/conversations/" . $conversationId . "/activities";

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        "Authorization: Bearer " . DIRECTLINE_SECRET
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    curl_close($ch);

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

    return $reply;
}
