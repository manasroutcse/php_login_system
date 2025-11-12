<?php
require_once 'config.php';

// 🔔 Add a new notification for a user
function addNotification($user_id, $icon, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO user_notifications (user_id, icon, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $icon, $message);
    $stmt->execute();
}
?>
