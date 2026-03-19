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
Handle NEW conversation start
------------------------------*/

if (!$conversationId) {

    $result = startConversationFlow($email, $name, $role);

    echo json_encode([
        "conversationId" => $result['conversationId'],
        "messages" => $result['reply']['messages'],
        "actions" => $result['reply']['actions']
    ]);

    exit;
}

/* -----------------------------
Create conversation if needed
------------------------------*/

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

    $result = startConversationFlow($email, $name, $role);

    echo json_encode([
        "conversationId" => $result['conversationId'],
        "messages" => $result['reply']['messages'],
        "actions" => $result['reply']['actions']
    ]);

    exit;
}

/* wait for bot response */

// $response = waitForBotMessages($conversationId);
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
