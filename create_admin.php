<?php
require 'config.php'; // ✅ Make sure config.php connects to your DB

$adminEmail = "ADD YOUR EMAIL ID";
$adminPassword = "123456"; // you can change this if you want
$adminName = "Admin";

$hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

// Check if admin already exists
$stmt = $conn->prepare("SELECT id FROM contacts WHERE email = ?");
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "⚠️ Admin already exists with email: $adminEmail";
} else {
    $stmt = $conn->prepare("
        INSERT INTO contacts (name, email, password, avatar, role, is_verified, status, created_at, updated_at)
        VALUES (?, ?, ?, 'uploads/default.png', 'Admin', 1, 'Active', NOW(), NOW())
    ");
    $stmt->bind_param("sss", $adminName, $adminEmail, $hashedPassword);

    if ($stmt->execute()) {
        echo "✅ Admin account created successfully!<br>";
        echo "📧 Email: $adminEmail<br>";
        echo "🔑 Password: $adminPassword<br>";
    } else {
        echo "❌ Error creating admin: " . $stmt->error;
    }
}
?>
