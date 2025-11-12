<?php
@ini_set('output_buffering', 'On');
ob_start();
session_start();
require 'config.php';

// 🛡️ Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// 🛡️ Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT name, email, avatar, role, is_verified, password FROM contacts WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

$success = $error = "";

// 🧾 Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "⚠️ Invalid CSRF token.";
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        if ($name && $email) {
            $stmt = $conn->prepare("UPDATE contacts SET name=?, email=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $email, $user_id);
            if ($stmt->execute()) {
                $success = "✅ Profile updated successfully!";
                $user['name'] = $name;
                $user['email'] = $email;
            } else {
                $error = "❌ Failed to update profile.";
            }
        }
    }
}

// 🧩 Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "⚠️ Invalid CSRF token.";
    } else {
        $current = trim($_POST['current_password']);
        $new = trim($_POST['new_password']);
        $confirm = trim($_POST['confirm_password']);

        if (password_verify($current, $user['password'])) {
            if ($new === $confirm && strlen($new) >= 6) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE contacts SET password=? WHERE id=?");
                $stmt->bind_param("si", $hashed, $user_id);
                $stmt->execute();
                $success = "🔒 Password updated successfully!";
            } else {
                $error = "⚠️ Passwords don't match or too short.";
            }
        } else {
            $error = "❌ Current password is incorrect.";
        }
    }
}

// 🖼️ Avatar Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "⚠️ Invalid CSRF token.";
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $newName = 'uploads/avatar_' . $user_id . '.' . $ext;
        move_uploaded_file($_FILES['avatar']['tmp_name'], $newName);
        $stmt = $conn->prepare("UPDATE contacts SET avatar=? WHERE id=?");
        $stmt->bind_param("si", $newName, $user_id);
        $stmt->execute();
        $user['avatar'] = $newName;
        $success = "🖼️ Avatar updated successfully!";
    } else {
        $error = "❌ Failed to upload image.";
    }
}

// 🔒 Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
        session_destroy();
        header("Location:index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Dashboard | Glassmorphism</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root {
  --bg: #0f172a;
  --glass: rgba(255,255,255,0.06);
  --accent: linear-gradient(135deg,#7c3aed 0%, #06b6d4 100%);
  --text: rgba(255,255,255,0.85);
  --border: rgba(255,255,255,0.15);
}
body {
   background: url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
  /* background: radial-gradient(1200px 600px at 10% 10%, rgba(124,58,237,0.18), transparent 10%),
              radial-gradient(1000px 500px at 90% 90%, rgba(6,182,212,0.10), transparent 15%),
              var(--bg); */
  color: var(--text);
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  font-family: "Inter", sans-serif;
}
 input[placeholder] {
      color: #fff;;
     
    }
    /* For cross-browser placeholder color support */
input::placeholder {
    color: #fff;
    opacity: 1; /* Ensures solid color in Firefox */
}
input::-webkit-input-placeholder { /* Chrome/Safari/Opera */
    color: #fff;
}
input:-ms-input-placeholder { /* IE 10+ */
    color: #fff;
}
input::-ms-input-placeholder { /* Edge */
    color: #fff;
}
.dashboard {
  background: var(--glass);
  border: 1px solid var(--border);
  border-radius: 20px;
  backdrop-filter: blur(14px);
  box-shadow: 0 8px 40px rgba(0,0,0,0.3);
  width: 700px;
  padding: 2rem;
  animation: fadeUp .8s ease-out;
}
@keyframes fadeUp { from {opacity:0; transform:translateY(20px);} to {opacity:1; transform:none;} }
.avatar { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); }
.btn-custom { background: var(--accent); color: #fff; border: none; border-radius: 10px; padding: 10px 20px; transition: 0.3s; }
.btn-custom:hover { opacity: 0.9; transform: translateY(-1px); }
.input { background: transparent; border: 1px solid var(--border); border-radius: 10px; color: white; padding: 10px; width: 100%; }
label { color: var(--text); font-weight: 500; }
.alert { background: rgba(255,255,255,0.08); border: 1px solid var(--border); color: var(--text); }
</style>
</head>
<body>
<div class="dashboard text-center">
  <img src="<?php echo htmlspecialchars($user['avatar']); ?>" class="avatar mb-3" alt="Avatar">
  <h3><?php echo htmlspecialchars($user['name']); ?></h3>
  <p><?php echo htmlspecialchars($user['email']); ?></p>
  <p>
    <span class="badge <?php echo $user['is_verified'] ? 'bg-success' : 'bg-warning'; ?>">
      <?php echo $user['is_verified'] ? 'Verified' : 'Not Verified'; ?>
    </span>
  </p>

  <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

  <form method="post" class="mt-3">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <div class="mb-2"><input type="text" name="name" class="input" value="<?php echo htmlspecialchars($user['name']); ?>" required></div>
    <div class="mb-2"><input type="email" name="email" class="input" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
    <button name="update_profile" class="btn-custom w-100 mt-2">Save Profile</button>
  </form>

  <hr class="my-4 text-white">

  <form method="post" enctype="multipart/form-data" class="mb-4">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <label>Update Avatar</label>
    <input type="file" name="avatar" class="form-control mb-2" accept="image/*" onchange="previewAvatar(event)">
    <button name="upload_avatar" class="btn-custom w-100">Upload Avatar</button>
  </form>

  <hr class="my-4 text-white">

  <form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <label>Change Password</label>
    <input type="password" name="current_password" class="input mb-2" placeholder="Current Password" required>
    <input type="password" name="new_password" class="input mb-2" placeholder="New Password" required>
    <input type="password" name="confirm_password" class="input mb-3" placeholder="Confirm Password" required>
    <button name="change_password" class="btn-custom w-100">Change Password</button>
  </form>

  <form method="post" class="mt-4">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <button name="logout" class="btn btn-danger w-100">Logout</button>
  </form>
</div>

<script>
function previewAvatar(event) {
  const img = document.querySelector('.avatar');
  img.src = URL.createObjectURL(event.target.files[0]);
}
</script>
</body>
</html>
