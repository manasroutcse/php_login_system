<?php
@ini_set('output_buffering', 'On');
ob_start();
session_start();
require 'config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 🛡️ CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "⚠️ Invalid CSRF token. Please refresh and try again.";
    } else {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "⚠️ Please enter a valid email address.";
        } else {
            $stmt = $conn->prepare("SELECT id, name FROM contacts WHERE email=? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows === 1) {
                $user = $res->fetch_assoc();
                $resetToken = bin2hex(random_bytes(16));
                $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

                $stmt = $conn->prepare("UPDATE contacts SET reset_token=?, reset_expires=? WHERE email=?");
                $stmt->bind_param("sss", $resetToken, $expires, $email);
                $stmt->execute();

                // ✉️ Send reset email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = '$HOST_NAME';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = '$USER_NAME';
                    $mail->Password   = '$PASSWORD';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('$EMAIL', 'CRUD App Team');
                    $mail->addAddress($email, $user['name']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Reset Your Password';

                    $resetLink = "http://localhost/login_registration_system/reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($resetToken);
                    $mail->Body = "
                        <h2>Hello, {$user['name']} 👋</h2>
                        <p>We received a request to reset your password.</p>
                        <p><a href='$resetLink' style='padding:10px 18px;background:#6366f1;color:#fff;border-radius:8px;text-decoration:none;'>Reset Password</a></p>
                        <p>This link will expire in 1 hour. If you didn’t request a reset, you can ignore this email.</p>
                    ";

                    $mail->send();
                    $success = "✅ A password reset link has been sent to your email.";
                } catch (Exception $e) {
                    $error = "❌ Failed to send reset email: {$mail->ErrorInfo}";
                }
            } else {
                $error = "⚠️ No account found with that email.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forgot Password — Modern Glass UI</title>

  <!-- ✅ Bootstrap 5 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      color: #fff;
      overflow-x: hidden;
    }
    header, footer {
      text-align: center;
      padding: 1rem;
      backdrop-filter: blur(12px);
      background: rgba(0,0,0,0.25);
    }
    .card-glass {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      border: 1px solid rgba(255,255,255,0.15);
      box-shadow: 0 8px 32px rgba(0,0,0,0.4);
      max-width: 400px;
      margin: auto;
      animation: fadeIn 0.8s ease-in-out forwards;
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(30px);}
      to {opacity: 1; transform: translateY(0);}
    }
    .form-control {
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.3);
      color: #fff;
    }
    .form-control:focus {
      border-color: #7c3aed;
      box-shadow: 0 0 0 0.25rem rgba(124,58,237,0.25);
      background: rgba(255,255,255,0.2);
      color: #fff;
    }
    .btn-primary {
      background: linear-gradient(135deg,#7c3aed,#06b6d4);
      border: none;
      border-radius: 10px;
    }
    .btn-primary:hover {
      opacity: 0.9;
    }
    footer a {
      color: #0dcaf0;
      text-decoration: none;
    }
  </style>
</head>

<body>
  <header>
    <h3><i class="bi bi-shield-lock"></i> Secure Forgot Password</h3>
  </header>

  <main class="d-flex flex-column justify-content-center flex-grow-1">
    <div class="card card-glass p-4">
      <h4 class="text-center mb-3">Forgot Your Password?</h4>
      <p class="text-center mb-4">Enter your registered email and we’ll send a reset link.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($success) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="post" id="forgotForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="mb-3">
          <label for="email" class="form-label">Email address</label>
          <input type="email" class="form-control" name="email" id="email" placeholder="you@example.com" required>
          <div class="invalid-feedback">Please enter a valid email.</div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
      </form>

      <p class="text-center mt-3 mb-0">
        <a href="index.php" class="text-decoration-none text-light">
          <i class="bi bi-arrow-left-circle"></i> Back to Login
        </a>
      </p>
    </div>
  </main>

  <footer>
    <small>© <?= date('Y') ?> CRUD App — All rights reserved | <a href="#">Privacy Policy</a></small>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // ✅ Client-side validation
    (() => {
      const form = document.getElementById('forgotForm');
      form.addEventListener('submit', e => {
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
        }
        form.classList.add('was-validated');
      });
    })();
  </script>
</body>
</html>
