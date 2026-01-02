<?php
require_once '../../includes/init.php';
requireLogin();

header('Content-Type: application/json');

$user = new User();
$messageClass = new Message();

$currentUserId = $user->getCurrentUserId();
$otherUserId = intval($_POST['other_user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

if ($otherUserId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID'
    ]);
    exit;
}

if ($currentUserId === $otherUserId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid conversation'
    ]);
    exit;
}

// Permanent delete for both users
$result = $messageClass->deleteConversation($currentUserId, $otherUserId);

echo json_encode($result);
exit;
