<?php
@ini_set('output_buffering', 'On');
ob_start();
session_start();
require 'config.php';
require 'vendor/autoload.php';

// 🛡️ CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

// 🧩 Validate token on load
if ($email && $token) {
    $stmt = $conn->prepare("SELECT id, reset_expires FROM contacts WHERE email=? AND reset_token=? LIMIT 1");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows !== 1) {
        $error = "⚠️ Invalid or expired link.";
    } else {
        $user = $res->fetch_assoc();
        if (strtotime($user['reset_expires']) < time()) {
            $error = "⚠️ Reset link has expired. Please request again.";
        }
    }
} else {
    $error = "⚠️ Invalid password reset request.";
}

// 🧾 Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "⚠️ Invalid CSRF token.";
    } else {
        $new_password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        if ($new_password && $confirm_password) {
            if ($new_password !== $confirm_password) {
                $error = "⚠️ Passwords do not match.";
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE contacts SET password=?, reset_token=NULL, reset_expires=NULL WHERE email=?");
                $stmt->bind_param("ss", $hashed, $email);
                if ($stmt->execute()) {
                    $success = "✅ Password reset successful! You can now <a href='login_glass.php' style='color:#fff;text-decoration:underline;'>login</a>.";
                    unset($_SESSION['csrf_token']);
                } else {
                    $error = "❌ Failed to update password.";
                }
            }
        } else {
            $error = "⚠️ Please fill all fields.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reset Password — Glassmorphism UI</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      background: url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
      font-family: 'Inter', sans-serif;
      margin: 0;
      /* background: radial-gradient(circle at top left, #94b3d5ff, #7e94cdff); */
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh; color: #fff;
    }
    .card {
      
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      width: 400px; padding: 2rem;
      text-align: center;
      box-shadow: 0 10px 40px rgba(0,0,0,0.4);
      animation: fadeSlide .6s ease forwards;
      opacity: 0; transform: translateY(15px);
    }
    @keyframes fadeSlide {
      to {opacity: 1; transform: translateY(0);}
    }
    .input {
      width: 100%; padding: 10px 14px;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 10px; background: transparent;
       margin: 10px 0;
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
    .btn {
      width: 100%; padding: 12px;
      border: none; border-radius: 10px;
      background: linear-gradient(135deg,#7c3aed,#06b6d4);
      color: white; font-weight: 600; cursor: pointer;
    }
    .btn:hover {opacity: .9;}
    .toast {
      position: fixed; top: 20px; right: 20px;
      padding: 14px 18px; border-radius: 10px;
      background: rgba(0,0,0,0.7); color: white;
      animation: fadeInOut 4s ease forwards;
    }
    @keyframes fadeInOut {
      0%{opacity:0;transform:translateY(-20px);}
      10%,90%{opacity:1;transform:none;}
      100%{opacity:0;transform:translateY(-20px);}
    }
    a {color: #fff; text-decoration: none;}
  </style>
</head>
<body>
  <div class="card">
    <h2>Reset Password 🔐</h2>
    <p>Enter your new password below.</p>

    <?php if ($error): ?><div class="toast" style="background:#ef4444;"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="toast" style="background:#10b981;"><?php echo $success; ?></div><?php endif; ?>

    <?php if (!$success && !$error): ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      <input type="password" name="password" class="input" placeholder="New Password" required>
      <input type="password" name="confirm_password" class="input"  placeholder="Confirm Password" required>
      <button type="submit" class="btn">Update Password</button>
    </form>
    <?php endif; ?>

    <p style="margin-top:18px;">Back to <a href="index.php">Login</a></p>
  </div>
</body>
</html>
