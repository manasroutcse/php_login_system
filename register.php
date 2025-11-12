<?php
// Start output buffering and session
@ini_set('output_buffering', 'On');
ob_start();
session_start();
require 'config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// CSRF token setup
if (empty($_SESSION['csrftoken'])) {
    $_SESSION['csrftoken'] = bin2hex(random_bytes(32));
}
$error = '';
$success = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrftoken']) || !hash_equals($_SESSION['csrftoken'], $_POST['csrftoken'])) {
        $error = "⚠️ Invalid CSRF token. Please refresh and try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($name && $email && $password) {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM contacts WHERE email=? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $error = "⚠️ Email already registered.";
            } else {
                // Register user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $token = bin2hex(random_bytes(16));
                $expires = date('Y-m-d H:i:s', strtotime('+1 day'));
                $stmt = $conn->prepare("INSERT INTO contacts (name, email, password, token, token_expires, is_verified, role, avatar, status) VALUES (?, ?, ?, ?, ?, 0, 'User', 'uploads/default.png', 'Pending')");
                $stmt->bind_param("sssss", $name, $email, $hashedPassword, $token, $expires);
                
                if ($stmt->execute()) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = '$HOST_NAME';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = '$USER_NAME';
                        $mail->Password   = '$PASSWORD';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('EMAIL', 'CRUD App Team');
                        $mail->addAddress($email, $name);
                        $mail->isHTML(true);
                        $mail->Subject = 'Verify Your Email Address';

                        $verifyLink = "http://localhost/php_login_system/verify.php?email=" . urlencode($email) . "&token=" . urlencode($token);
                        $mail->Body = "
                            <h2>Hello, $name 👋</h2>
                            <p>Thank you for registering! Please verify your email by clicking the button below:</p>
                            <p><a href='$verifyLink' style='padding:10px 20px; background:#007bff; color:#fff; border-radius:5px; text-decoration:none;'>Verify Account</a></p>
                            <p>This link will expire in 24 hours.</p>
                        ";

                        $mail->send();
                        $success = '✅ Registration successful! Please check your email to verify your account.';

                        // 🔁 Regenerate CSRF token after success
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } catch (Exception $e) {
                        $error = "Account created, but email not sent: {$mail->ErrorInfo}";
                    }
                } else {
                    $error = "❌ Failed to register user.";
                }
            }
        } else {
            $error = "⚠️ All fields are required.";
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Modern Glass UI</title>
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: Inter, sans-serif;
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
            width: 400px;
            margin: auto;
            animation: fadeIn 0.8s ease-in-out forwards;
        }
        @keyframes fadeIn {
            from {opacity:0; transform:translateY(30px);}
            to {opacity:1; transform:translateY(0);}
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
        <h3><i class="bi bi-person-plus"></i> Create Account</h3>
    </header>
    <main class="d-flex flex-column justify-content-center flex-grow-1">
        <div class="card card-glass p-4">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <form method="POST" id="registerForm" novalidate autocomplete="off">
                <input type="hidden" name="csrftoken" value="<?= htmlspecialchars($_SESSION['csrftoken'] ?? '') ?>">
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" class="form-control" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    <div class="invalid-feedback">Please enter your name.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <div class="invalid-feedback">Please enter a valid email.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required minlength="6">
                    <div class="invalid-feedback">Password must be at least 6 characters.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Create Account</button>
                <div class="mt-3 text-center">
                    Already have an account? <a href="index.php" style="color: #fff; font-weight:600;">Sign In</a>
                </div>
            </form>
        </div>
    </main>
    <footer>
        <small>&copy; <?= date('Y') ?> CRUD App. All rights reserved. <a href="#">Privacy Policy</a></small>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const form = document.getElementById('registerForm');
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    </script>
</body>
</html>
