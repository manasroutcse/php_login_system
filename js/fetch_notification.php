<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT icon, message, created_at FROM user_notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications);
?>
