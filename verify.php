<?php
@ini_set('output_buffering', 'On');
ob_start();
session_start();
require 'config.php';

// 🎯 Initialize message variables
$success = '';
$error = '';

// ✅ Check verification link
if (isset($_GET['email'], $_GET['token'])) {
    $email = $_GET['email'];
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id, token_expires, is_verified FROM contacts WHERE email=? AND token=? LIMIT 1");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();

        if ($user['is_verified'] == 1) {
            $success = "✅ Your email is already verified! You can now log in.";
        } elseif (strtotime($user['token_expires']) > time()) {
            // Update verification status
            $stmt = $conn->prepare("UPDATE contacts SET is_verified=1, token=NULL, token_expires=NULL WHERE email=?");
            $stmt->bind_param("s", $email);
            if ($stmt->execute()) {
                $success = "✅ Email verified successfully! You can now log in.";
            } else {
                $error = "⚠️ Something went wrong. Please try again.";
            }
        } else {
            $error = "⚠️ Verification link expired. Please register again.";
        }
    } else {
        $error = "⚠️ Invalid verification link.";
    }
} else {
    $error = "⚠️ Missing verification parameters.";
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Email Verification | CRUD App</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg1: #0f172a;
  --accent: linear-gradient(135deg, #7c3aed 0%, #06b6d4 100%);
  --muted: rgba(255,255,255,0.7);
  --glass-border: rgba(255,255,255,0.12);
}
body {
  margin: 0;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(1200px 600px at 10% 10%, rgba(124,58,237,0.18), transparent 10%),
    radial-gradient(1000px 500px at 90% 90%, rgba(6,182,212,0.10), transparent 15%),
    var(--bg1);
  font-family: 'Inter', sans-serif;
  color: var(--muted);
}
.card {
  background: rgba(255,255,255,0.06);
  border: 1px solid var(--glass-border);
  border-radius: 20px;
  padding: 36px;
  width: 420px;
  text-align: center;
  backdrop-filter: blur(18px);
  box-shadow: 0 8px 40px rgba(0,0,0,0.4);
  animation: fadeIn 0.6s ease;
}
@keyframes fadeIn {from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:none;}}
h2 {
  color: white;
  margin-bottom: 8px;
  font-size: 24px;
}
p {
  font-size: 14px;
  margin-bottom: 16px;
  color: rgba(255,255,255,0.8);
}
.btn {
  display: inline-block;
  background: var(--accent);
  color: white;
  padding: 10px 20px;
  border-radius: 12px;
  text-decoration: none;
  font-weight: 600;
  transition: 0.3s;
}
.btn:hover {
  opacity: 0.9;
}
.icon {
  font-size: 48px;
  margin-bottom: 12px;
  animation: pulse 1.2s infinite alternate;
}
@keyframes pulse {
  from {transform: scale(1);}
  to {transform: scale(1.1);}
}
.toast {
  position: fixed; bottom: 30px; right: 30px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: 14px;
  padding: 14px 22px;
  color: #fff;
  font-weight: 500;
  backdrop-filter: blur(12px);
  box-shadow: 0 8px 30px rgba(0,0,0,0.4);
  opacity: 0; transform: translateY(20px);
  transition: opacity .3s ease, transform .3s ease;
  z-index: 9999;
}
.toast.show { opacity: 1; transform: translateY(0); }
.toast.hidden { opacity: 0; pointer-events: none; }
.toast.success { border-left: 4px solid #22c55e; }
.toast.error { border-left: 4px solid #ef4444; }
</style>
</head>
<body>

<div class="card">
  <?php if ($success): ?>
    <div class="icon">✅</div>
    <h2>Email Verified</h2>
    <p><?php echo htmlspecialchars($success); ?></p>
    <a href="login_index_two.php" class="btn">Go to Login</a>
  <?php else: ?>
    <div class="icon">⚠️</div>
    <h2>Verification Failed</h2>
    <p><?php echo htmlspecialchars($error); ?></p>
    <a href="register.php" class="btn">Try Again</a>
  <?php endif; ?>
</div>

<div id="toast" class="toast hidden"></div>
<script>
function showToast(message, type='info') {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.className = `toast show ${type}`;
  setTimeout(()=>toast.className='toast hidden', 3500);
}
</script>

<?php if ($error): ?>
<script>showToast("<?php echo addslashes($error); ?>", "error");</script>
<?php elseif ($success): ?>
<script>showToast("<?php echo addslashes($success); ?>", "success");</script>
<?php endif; ?>
</body>
</html>
