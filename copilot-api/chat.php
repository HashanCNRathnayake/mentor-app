<?php

require_once "config.php";
require_once "helpers/directline.php";

$data = json_decode(file_get_contents("php://input"), true);

$message = $data['message'];
$email = $data['email'];
$name = $data['name'];
$role = $data['role'] ?? "user";

$conversationId = $data['conversationId'] ?? null;


/* -----------------------------
Create conversation if needed
------------------------------*/

if (!$conversationId) {

    $conversation = createConversation();

    $conversationId = $conversation['conversationId'];
}


/* -----------------------------
Send message to Copilot
------------------------------*/

$result = sendMessageToCopilot(
    $conversationId,
    $message,
    $email,
    $name,
    $role
);

/* If conversation expired create new one */

if ($result['expired']) {

    $conversation = createConversation();

    $conversationId = $conversation['conversationId'];

    sendMessageToCopilot(
        $conversationId,
        $message,
        $email,
        $name,
        $role
    );
}


/* wait for bot response */

sleep(2);


/* -----------------------------
Get all bot responses
------------------------------*/

$response = getBotReply($conversationId);


/* -----------------------------
Return structured response
------------------------------*/

echo json_encode([

    "conversationId" => $conversationId,

    "messages" => $response['messages'],

    "actions" => $response['actions']

    // "attachments" => $response['attachments']
]);
