<?php
@ini_set('output_buffering', 'On');
ob_start();
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_glass.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['toast_message'] = "⚠️ Invalid CSRF token!";
        $_SESSION['toast_type'] = "error";
        header("Location: user_dashboard.php");
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Handle avatar upload
    $avatarPath = '';
    if (!empty($_FILES['avatar']['name'])) {
        $uploadDir = "uploads/";
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES['avatar']['name']);
        $targetFile = $uploadDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($imageFileType, $allowed) && move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
            $avatarPath = $targetFile;
        }
    }

    // Update database
    if ($avatarPath) {
        $stmt = $conn->prepare("UPDATE contacts SET name=?, email=?, avatar=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $avatarPath, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE contacts SET name=?, email=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $email, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['toast_message'] = "✅ Profile updated successfully!";
        $_SESSION['toast_type'] = "success";
    } else {
        $_SESSION['toast_message'] = "❌ Failed to update profile.";
        $_SESSION['toast_type'] = "error";
    }

    header("Location: user_dashboard.php");
    exit;
}
?>
