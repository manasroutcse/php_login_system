<?php
@ini_set('output_buffering','On');
ob_start();
session_start();
require 'config.php';
date_default_timezone_set('Asia/Kolkata');

// 🔐 Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// 🛡️ CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf_token'];

// 📄 Fetch current user
$id = $_SESSION['user_id'] ?? 0;
$stmt = $conn->prepare("SELECT name, email, avatar, dark_mode, email_alerts, new_user_alerts, weekly_reports FROM contacts WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $error = "⚠️ User not found or invalid session.";
    $name = $email = "";
    $avatar = "uploads/default.png";
    $dark_mode = $email_alerts = $new_user_alerts = $weekly_reports = 0;
} else {
    $name = htmlspecialchars($user['name']);
    $email = htmlspecialchars($user['email']);
    $avatar = $user['avatar'] ?: 'uploads/default.png';
    $dark_mode = $user['dark_mode'] ?? 0;
    $email_alerts = $user['email_alerts'] ?? 0;
    $new_user_alerts = $user['new_user_alerts'] ?? 0;
    $weekly_reports = $user['weekly_reports'] ?? 0;
}

$success = $error ?? "";

// 🧾 Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "⚠️ Invalid CSRF token.";
    } else {
        $name_new = trim($_POST['name']);
        $email_new = trim($_POST['email']);
        $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
        $email_alerts = isset($_POST['email_alerts']) ? 1 : 0;
        $new_user_alerts = isset($_POST['new_user_alerts']) ? 1 : 0;
        $weekly_reports = isset($_POST['weekly_reports']) ? 1 : 0;
        $new_pass = $_POST['password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';
        $avatar_path = $avatar;

        // ✅ Handle file upload
        if (!empty($_FILES['avatar']['name'])) {
            $file = $_FILES['avatar'];
            $allowed = ['jpg','jpeg','png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $error = "❌ Only JPG, JPEG, PNG files are allowed.";
            } elseif ($file['size'] > 2*1024*1024) {
                $error = "⚠️ File too large. Max 2MB.";
            } else {
                $dir = "uploads/";
                if (!is_dir($dir)) mkdir($dir,0777,true);
                $newName = "avatar_" . $id . "_" . time() . "." . $ext;
                $path = $dir . $newName;
                if (move_uploaded_file($file['tmp_name'], $path)) {
                    $avatar_path = $path;
                } else {
                    $error = "⚠️ Failed to upload file.";
                }
            }
        }

        if (!$error) {
            if ($name_new === '' || $email_new === '') {
                $error = "Name and Email required.";
            } elseif ($new_pass && $new_pass !== $confirm_pass) {
                $error = "Passwords do not match.";
            } else {
                // 🧩 Update query — works even if dark_mode etc. are added
                $sql = "UPDATE contacts 
                        SET name=?, email=?, dark_mode=?, email_alerts=?, 
                            new_user_alerts=?, weekly_reports=?, avatar=?";
                $params = [$name_new, $email_new, $dark_mode, $email_alerts, $new_user_alerts, $weekly_reports, $avatar_path];
                $types = "ssiiiis";

                if ($new_pass) {
                    $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
                    $sql .= ", password=?";
                    $params[] = $hashed;
                    $types .= "s";
                }

                $sql .= " WHERE id=?";
                $params[] = $id;
                $types .= "i";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $success = "✅ Settings updated successfully.";
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $error = "❌ Failed to update.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>⚙️ Admin Settings</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
body {
  background: radial-gradient(circle at 10% 10%, rgba(124,58,237,0.12), transparent 10%),
              radial-gradient(circle at 90% 90%, rgba(6,182,212,0.08), transparent 15%),
              #0f172a;
  color:#fff;font-family:"Inter",system-ui,sans-serif;padding:30px;
  background: url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
}
.container-glass {
  background:rgba(255,255,255,0.06);
  border-radius:18px;padding:25px;
  backdrop-filter:blur(14px);
  border:1px solid rgba(255,255,255,0.12);
  box-shadow:0 10px 40px rgba(0,0,0,0.45);
}
.section {
  background:rgba(255,255,255,0.05);
  border:1px solid rgba(255,255,255,0.1);
  border-radius:16px;
  padding:20px;margin-bottom:20px;
}
.input-glass {
  background:rgba(255,255,255,0.08);
  border:none;color:#fff;
  padding:10px 12px;border-radius:10px;width:100%;
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
.btn-glass {
  background:rgba(255,255,255,0.08);
  border:1px solid rgba(255,255,255,0.15);
  color:#fff;padding:8px 16px;border-radius:10px;transition:0.3s;
}
.btn-glass:hover { background:rgba(255,255,255,0.2); }
.avatar { width:90px; height:90px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,0.2); }
.alert-glass {
  background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
  border-radius:10px; padding:10px 15px; margin-bottom:15px;
}
.profile-preview {
  background:rgba(255,255,255,0.08);
  border-radius:16px;
  border:1px solid rgba(255,255,255,0.15);
  padding:15px; text-align:center;
  transition:all .3s ease;
}
.profile-preview:hover { background:rgba(255,255,255,0.12); transform:scale(1.02); }
.profile-preview img { width:90px; height:90px; border-radius:50%; margin-bottom:10px; border:2px solid rgba(255,255,255,0.2); }
.profile-preview h6 { margin:0; font-weight:600; }
.profile-preview small { color:rgba(255,255,255,0.6); }
</style>
</head>
<body>
<div class="container container-glass">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4>⚙️ Admin Settings</h4>
    <a href="admin_dashboard.php" class="btn-glass">← Back</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert-glass text-danger"><?= htmlspecialchars($error) ?></div>
  <?php elseif (!empty($success)): ?>
    <div class="alert-glass text-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($CSRF) ?>">

    <div class="row">
      <div class="col-md-8">
        <div class="section">
          <h5>👤 Profile</h5>
          <div class="mb-3">
            <label class="form-label">Profile Picture</label><br>
            <input type="file" name="avatar" id="avatarInput" accept="image/*" class="form-control" style="background:rgba(255,255,255,0.08);color:#fff;border:none;">
          </div>
          <div class="row g-2">
            <div class="col-md-6"><input type="text" name="name" id="nameInput" class="input-glass" value="<?= htmlspecialchars($name) ?>" placeholder="Full Name"></div>
            <div class="col-md-6"><input type="email" name="email" id="emailInput" class="input-glass" value="<?= htmlspecialchars($email) ?>" placeholder="Email"></div>
            <div class="col-md-6"><input type="password" name="password" class="input-glass" placeholder="New Password"></div>
            <div class="col-md-6"><input type="password" name="confirm_password" class="input-glass" placeholder="Confirm Password"></div>
          </div>
        </div>

        <div class="section">
          <h5>🎨 Preferences</h5>
          <?php
          $opts = [
            ['Dark Mode','dark_mode',$dark_mode],
            ['Email Alerts','email_alerts',$email_alerts],
            ['New User Alerts','new_user_alerts',$new_user_alerts],
            ['Weekly Reports','weekly_reports',$weekly_reports],
          ];
          foreach($opts as [$label,$field,$checked]): ?>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span><?= $label ?></span>
            <input type="checkbox" name="<?= $field ?>" <?= $checked ? 'checked' : '' ?>>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="text-end mt-3">
          <button type="submit" class="btn-glass">💾 Save Settings</button>
        </div>
      </div>

      <div class="col-md-4">
        <div class="profile-preview" id="profilePreview">
          <img src="<?= htmlspecialchars($avatar) ?>" id="previewAvatar" alt="Avatar">
          <h6 id="previewName"><?= htmlspecialchars($name ?: 'Your Name') ?></h6>
          <small id="previewEmail"><?= htmlspecialchars($email ?: 'you@example.com') ?></small>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
const nameInput = document.getElementById('nameInput');
const emailInput = document.getElementById('emailInput');
const avatarInput = document.getElementById('avatarInput');
const previewName = document.getElementById('previewName');
const previewEmail = document.getElementById('previewEmail');
const previewAvatar = document.getElementById('previewAvatar');

nameInput.addEventListener('input', ()=> previewName.textContent = nameInput.value || 'Your Name');
emailInput.addEventListener('input', ()=> previewEmail.textContent = emailInput.value || 'you@example.com');
avatarInput.addEventListener('change', e=>{
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = ev => previewAvatar.src = ev.target.result;
    reader.readAsDataURL(file);
  }
});
</script>
</body>
</html>
