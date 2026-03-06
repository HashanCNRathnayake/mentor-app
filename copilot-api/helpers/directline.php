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

    $data = json_decode($response, true);

    /* Detect expired conversation */

    $expired = false;

    if (isset($data['error'])) {

        if (
            $data['error']['code'] == "ConversationNotFound" ||
            $data['error']['code'] == "BadArgument"
        ) {
            $expired = true;
        }
    }

    return [
        "expired" => $expired,
        "response" => $data
    ];
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
    file_put_contents('dl-debug.json', json_encode($data, JSON_PRETTY_PRINT));

    $messages = [];
    $actions = [];
    $attachments = [];

    if (isset($data['activities'])) {

        foreach ($data['activities'] as $activity) {

            if (($activity['from']['role'] ?? '') === "bot") {

                /* BOT TEXT */

                if (!empty($activity['text'])) {

                    $messages[] = [
                        "type" => "text",
                        "content" => $activity['text']
                    ];
                }

                // /* ATTACHMENTS */

                // if (!empty($activity['attachments'])) {

                //     foreach ($activity['attachments'] as $attachment) {

                //         $attachments[] = $attachment;
                //     }
                // }

                /* SUGGESTED ACTIONS */

                if (!empty($activity['suggestedActions']['actions'])) {

                    foreach ($activity['suggestedActions']['actions'] as $action) {

                        $actions[] = $action;
                    }
                }
            }
        }
    }

    return [
        "messages" => $messages,
        "actions" => $actions,
        // "attachments" => $attachments
    ];
}
