<?php
@ini_set('output_buffering', 'On');
ob_start();
session_start();
require 'config.php';
require_once 'log_action.php';

$pageTitle = "Login";

// 🛡️ CSRF Setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// 🔐 Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "⚠️ Invalid CSRF token. Please refresh and try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($email && $password) {
            $stmt = $conn->prepare("SELECT id, name, role, password, is_verified, avatar FROM contacts WHERE email=? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows === 1) {
                $user = $res->fetch_assoc();

                if ($user['is_verified'] != 1) {
                    $error = "⚠️ Please verify your email before logging in.";
                } elseif (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    unset($_SESSION['csrf_token']);

                    $redirect = ($user['role'] === 'Admin') ? 'admin_dashboard.php' : 'user_dashboard.php';
                    header("Location: $redirect");
                    exit;
                } else {
                    $error = "❌ Invalid email or password.";
                }
            } else {
                $error = "❌ Invalid email or password.";
            }
        } else {
            $error = "⚠️ Please enter both email and password.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> — Modern Glass UI</title>

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
      overflow-x: hidden;
      color: #fff;
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
    .btn-primary:hover { opacity: 0.9; }
    .social-btn {
      width: 100%;
      padding: 10px;
      border-radius: 10px;
      background: rgba(255,255,255,0.1);
      border: 1px solid rgba(255,255,255,0.25);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .social-btn:hover { background: rgba(255,255,255,0.2); }
    footer a {
      color: #0dcaf0;
      text-decoration: none;
    }
  </style>
</head>

<body>
  <header>
    <h3><i class="bi bi-box-arrow-in-right"></i> Welcome Back 👋</h3>
  </header>

  <main class="d-flex flex-column justify-content-center flex-grow-1">
    <div class="card card-glass p-4">
      <h4 class="text-center mb-3">Login to Your Account</h4>
      <p class="text-center mb-4">Use your email & password </p>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="POST" id="loginForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" name="email" placeholder="you@example.com" required>
          <div class="invalid-feedback">Please enter a valid email.</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" name="password" placeholder="••••••••" required minlength="6">
          <div class="invalid-feedback">Password must be at least 6 characters.</div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Sign In</button>
      </form>

      <div class="mt-3 text-center">
        <a href="forgot_password.php" class="text-light text-decoration-none">Forgot password?</a>
      </div>

      <div class="text-center mt-2">
        <p>Don’t have an account? <a href="register.php" class="text-info text-decoration-none">Register</a></p>
      </div>

     
    </div>
  </main>

  <footer>
    <small>© <?= date('Y') ?> CRUD App — All rights reserved | <a href="#">Privacy Policy</a></small>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // ✅ Client-side validation
    (() => {
      const form = document.getElementById('loginForm');
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
