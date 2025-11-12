<?php
@ini_set('output_buffering', 'On');
ob_start();
session_start();
require 'config.php';

// 🛡️ Admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// 🛡️ CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$userData = null;

// 🧾 Fetch user data
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT id, name, email, role, is_verified, status FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    if (!$userData) {
        $error = "⚠️ User not found.";
    }
} else {
    $error = "⚠️ Invalid request.";
}

// 🧩 Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userData) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "⚠️ Invalid CSRF token.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'User';
        $status = $_POST['status'] ?? 'Active';
        $is_verified = isset($_POST['is_verified']) ? 1 : 0;
        $password = trim($_POST['password'] ?? '');

        if ($name && $email) {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE contacts SET name=?, email=?, password=?, role=?, is_verified=?, status=? WHERE id=?");
                $stmt->bind_param("ssssisi", $name, $email, $hashedPassword, $role, $is_verified, $status, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE contacts SET name=?, email=?, role=?, is_verified=?, status=? WHERE id=?");
                $stmt->bind_param("sssisi", $name, $email, $role, $is_verified, $status, $user_id);
            }

            if ($stmt->execute()) {
                $success = "✅ User updated successfully.";
            } else {
                $error = "❌ Failed to update user.";
            }
        } else {
            $error = "⚠️ Name and email are required.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User | Admin Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    background: url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
    /* background: radial-gradient(circle at top left, rgba(120,80,250,0.25), transparent 50%),
                radial-gradient(circle at bottom right, rgba(0,200,255,0.15), transparent 60%),
                #0f172a; */
    font-family: "Inter", sans-serif;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 40px;
  }
  .glass-card {
    width: 460px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 2rem;
    border: 1px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(16px);
    box-shadow: 0 8px 40px rgba(0,0,0,0.35);
    animation: fadeUp .8s ease;
  }
  @keyframes fadeUp {
    from {transform: translateY(10px); opacity: 0;}
    to {transform: translateY(0); opacity: 1;}
  }
  .input, select {
    background: rgba(255,255,255,0.1);
    border: none;
    color: #fff;
    padding: 10px 14px;
    border-radius: 10px;
    width: 100%;
    margin-bottom: 10px;
  }
  .input::placeholder { color: rgba(255,255,255,0.7); }
  .btn-primary {
    background: linear-gradient(135deg,#7c3aed,#06b6d4);
    border: none;
    color: #fff;
    font-weight: 600;
    border-radius: 10px;
    width: 100%;
    padding: 10px;
  }
  .btn-primary:hover {
    opacity: 0.9;
  }
  .alert {border-radius: 10px; font-size: 14px;}
  .alert-success {background: rgba(0,255,0,0.15); color: #b6ffb6;}
  .alert-danger {background: rgba(255,0,0,0.15); color: #ffb6b6;}
  .link {color: #0ff; text-decoration: none;}
</style>
</head>
<body>

<div class="glass-card">
  <h3 class="text-center mb-3">✏️ Edit User</h3>

  <?php if ($error): ?><div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <?php if ($userData): ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <input type="text" name="name" class="input" value="<?= htmlspecialchars($userData['name']) ?>" required>
    <input type="email" name="email" class="input" value="<?= htmlspecialchars($userData['email']) ?>" required>
    <input type="password" name="password" class="input" placeholder="Leave blank to keep current password">

    <select name="role" class="input">
      <option value="User" <?= $userData['role'] === 'User' ? 'selected' : '' ?>>User</option>
      <option value="Admin" <?= $userData['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
    </select>

    <select name="status" class="input">
      <option value="Active" <?= $userData['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
      <option value="Suspended" <?= $userData['status'] === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
    </select>

    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="is_verified" value="1" <?= $userData['is_verified'] ? 'checked' : '' ?>>
      <label class="form-check-label text-white">Verified Account</label>
    </div>

    <button type="submit" class="btn-primary mt-2">Update User</button>
  </form>

  <div class="text-center mt-3">
    <a href="manage_users.php" class="link">⬅ Back to Manage Users</a>
  </div>
  <?php endif; ?>
</div>

</body>
</html>
