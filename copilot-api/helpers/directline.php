<?php

function logRaw($type, $data)
{
    $log = [
        "time" => date("Y-m-d H:i:s"),
        "type" => $type,
        "data" => $data
    ];

    file_put_contents(
        "directline-raw.log",
        json_encode($log, JSON_PRETTY_PRINT) . PHP_EOL,
        FILE_APPEND
    );
}

function createConversation()
{

    $url = DIRECTLINE_ENDPOINT . "/conversations";

    // logRaw("REQUEST_CREATE_CONVERSATION", [
    //     "url" => $url
    // ]);


    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    $headers = [
        "Authorization: Bearer " . DIRECTLINE_SECRET,
        "Content-Type: application/json"
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    // logRaw("RESPONSE_CREATE_CONVERSATION", $response);


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

    logRaw("REQUEST_SEND_MESSAGE", [
        "url" => $url,
        "payload" => $payload
    ]);

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
    // logRaw("RESPONSE_SEND_MESSAGE", $response);

    curl_close($ch);

    $data = json_decode($response, true);

    /* Detect expired conversation */


    // logRaw("RESPONSE_SEND_MESSAGE_data", $data);

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

    // logRaw("REQUEST_GET_REPLY", [
    //     "url" => $url
    // ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        "Authorization: Bearer " . DIRECTLINE_SECRET
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);


    curl_close($ch);

    $data = json_decode($response, true);

    // logRaw("RESPONSE_GET_REPLY_data", $data);

    // file_put_contents('dl-debug.json', json_encode($data, JSON_PRETTY_PRINT));

    $messages = [];
    $actions = [];

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
                /* SUGGESTED ACTIONS */

                if (!empty($activity['suggestedActions']['actions'])) {

                    foreach ($activity['suggestedActions']['actions'] as $action) {

                        $actions[] = $action;
                    }
                }
            }
        }
    }

    logRaw("msg", $messages);
    logRaw("actions", $actions);



    return [
        "messages" => $messages,
        "actions" => $actions,
    ];
}

function waitForBotMessages($conversationId)
{
    $lastCount = 0;
    $stableCount = 0;
    $reply = ["messages" => [], "actions" => []];

    for ($i = 0; $i < 10; $i++) {

        $reply = getBotReply($conversationId);

        $currentCount = count($reply['messages']);

        if ($currentCount === $lastCount) {
            $stableCount++;
        } else {
            $stableCount = 0;
        }

        $lastCount = $currentCount;

        /* if messages stop increasing for 2 checks, assume finished */

        if ($stableCount >= 2) {
            return $reply;
        }

        sleep(1);
    }

    return $reply;
}

function startConversationFlow($email, $name, $role)
{
    /* STEP 1 — create conversation */

    $conv = createConversation();
    // logRaw("createConversation_res", $conv);


    $conversationId = $conv['data']['conversationId'];

    logRaw("conversationId", $conversationId);

    if (!$conversationId) {
        return ["error" => "Conversation creation failed"];
    }

    /* STEP 2 — send first start */

    sendMessageToCopilot(
        $conversationId,
        "start",
        $email,
        $name,
        $role
    );

    /* STEP 3 — wait for bot messages (ignore them) */

    // waitForBotMessages($conversationId);

    /* STEP 4 — send second start */

    sendMessageToCopilot(
        $conversationId,
        "start",
        $email,
        $name,
        $role
    );

    /* STEP 5 — get the real response */

    $reply = getBotReply($conversationId);

    return [
        "conversationId" => $conversationId,
        "reply" => $reply
    ];
}
