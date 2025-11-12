<?php
@ini_set('output_buffering', 'On');
ob_start();
session_start();
require 'config.php';

// 🛡️ Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// 🛡️ CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$toast = null;

// 📊 Stats queries (basic)
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM contacts WHERE role='User'")->fetch_assoc()['total'] ?? 0;
$verifiedUsers = $conn->query("SELECT COUNT(*) AS total FROM contacts WHERE is_verified=1 AND role='User'")->fetch_assoc()['total'] ?? 0;
$pendingUsers = $conn->query("SELECT COUNT(*) AS total FROM contacts WHERE is_verified=0 AND role='User'")->fetch_assoc()['total'] ?? 0;
$totalAdmins = $conn->query("SELECT COUNT(*) AS total FROM contacts WHERE role='Admin'")->fetch_assoc()['total'] ?? 0;

// 🔒 Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $toast = ['type'=>'error','msg'=>'⚠️ Invalid CSRF token.'];
    } else {
        session_destroy();
        header("Location:index.php");
        exit;
    }
}

// fetch admin info
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email, avatar FROM contacts WHERE id=? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc() ?: ['name'=>'Admin','email'=>'','avatar'=>'uploads/default.png'];
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Admin Dashboard — Glassmorphism</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg-dark: radial-gradient(circle at 10% 10%, #0b1220 0%, #0f172a 50%);
  --bg-light: radial-gradient(circle at 10% 10%, #f0f7ff 0%, #ffffff 50%);
  --glass-dark: rgba(255,255,255,0.06);
  --glass-light: rgba(255,255,255,0.9);
  --muted-dark: rgba(255,255,255,0.85);
  --muted-light: #0f172a;
  --accent: linear-gradient(135deg,#7c3aed 0%,#06b6d4 100%);
}
html,body{height:100%;margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial;}
body{background:var(--bg-dark);display:flex;align-items:center;justify-content:center;padding:28px;color:var(--muted-dark);transition:background .5s ease,color .3s ease;}
[data-theme="light"] body{background:var(--bg-light);color:var(--muted-light);}

.dashboard {
  width: 980px;
  border-radius:20px;
  padding:28px;
  background:var(--glass-dark);
  border:1px solid rgba(255,255,255,0.08);
  backdrop-filter:blur(14px);
  box-shadow:0 12px 50px rgba(2,6,23,0.6);
  transition:background .4s ease,color .3s ease;
}
[data-theme="light"] .dashboard{background:var(--glass-light);border-color:rgba(0,0,0,0.08);box-shadow:0 8px 30px rgba(2,6,23,0.06);}

.header{display:flex;align-items:center;justify-content:space-between;gap:16px}
.admin-info{display:flex;align-items:center;gap:14px}
.avatar{width:72px;height:72px;border-radius:14px;object-fit:cover;border:2px solid rgba(255,255,255,0.12)}
[data-theme="light"] .avatar{border-color:rgba(0,0,0,0.08)}
.h1{font-size:20px;margin:0}
.p-muted{opacity:.85;margin:0;font-weight:500}

.top-actions{display:flex;gap:10px;align-items:center}
.btn{padding:10px 14px;border-radius:12px;border:none;cursor:pointer;font-weight:700}
.btn-outline{background:transparent;border:1px solid rgba(255,255,255,0.08);color:inherit}
.btn-primary{background:var(--accent);color:#04203a}
[data-theme="light"] .btn-primary{color:#fff}

.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:20px}
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.card{background:rgba(255,255,255,0.04);border-radius:14px;padding:18px;border:1px solid rgba(255,255,255,0.06);backdrop-filter:blur(8px);min-height:86px;display:flex;flex-direction:column;justify-content:space-between}
[data-theme="light"] .card{background:rgba(255,255,255,0.9);border-color:rgba(0,0,0,0.04)}
.card h3{margin:0;font-size:13px;font-weight:700}
.stat{font-size:28px;font-weight:800;margin-top:6px}
.card .muted{opacity:.85;font-size:13px}

/* quick action cards (left column) */
.quick-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
.quick{padding:18px;border-radius:12px;background:linear-gradient(180deg,rgba(255,255,255,0.02),rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.04);display:flex;flex-direction:column;gap:10px;align-items:flex-start}
.quick h4{margin:0}

/* center area (right column) */
.area{padding-left:16px}

/* cards for nav */
.nav-card{background:linear-gradient(180deg,rgba(255,255,255,0.02),rgba(255,255,255,0.01));border-radius:14px;padding:18px;border:1px solid rgba(255,255,255,0.04);display:flex;flex-direction:column;gap:12px;cursor:pointer}
.nav-card h4{margin:0}
.nav-card p{margin:0;font-size:13px}

/* small helpers */
.row{display:flex;gap:12px;align-items:center}
.kpi{font-size:12px;opacity:.9}

/* theme toggle */
.theme-toggle{background:transparent;border:1px solid rgba(255,255,255,0.06);width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer}
[data-theme="light"] .theme-toggle{border-color:rgba(0,0,0,0.08)}

/* toast */
.toast{position:fixed;top:22px;right:22px;padding:12px 16px;border-radius:12px;color:#fff;font-weight:700;z-index:9999;box-shadow:0 6px 30px rgba(0,0,0,0.3)}
.toast.success{background:linear-gradient(135deg,#16a34a,#22c55e)}
.toast.error{background:linear-gradient(135deg,#dc2626,#ef4444)}

/* responsive */
@media (max-width:1024px){
  .dashboard{width:92vw;padding:18px}
  .cards{grid-template-columns:repeat(2,1fr)}
  .grid{grid-template-columns:1fr;gap:14px}
}
body{
  background: url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
}
</style>
</head>
<body>

<div class="dashboard" role="main">
  <div class="header">
    <div class="admin-info">
      <img src="<?php echo htmlspecialchars($admin['avatar']); ?>" class="avatar" alt="Admin avatar">
      <div>
        <div class="h1">Hello, <?php echo htmlspecialchars($admin['name']); ?> — Admin</div>
        <div class="p-muted"><?php echo htmlspecialchars($admin['email']); ?></div>
      </div>
    </div>

    <div class="top-actions">
      <button class="theme-toggle" id="themeBtn" title="Toggle theme" aria-label="Toggle theme">
        <svg id="themeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none"><path fill="#fff" d="M12 4.354a1 1 0 0 1 1 0l1.732 1a1 1 0 0 1 .366 1.366l-1 1.732a1 1 0 0 1-1.366.366l-1.732-1a1 1 0 0 1-.366-1.366l1-1.732a1 1 0 0 1 1-.366zM12 20a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/></svg>
      </button>

      <a href="manage_users.php" class="btn btn-outline">Manage Users</a>

      <form method="post" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <button name="logout" class="btn btn-primary" type="submit">Logout</button>
      </form>
    </div>
  </div>

  <div class="grid" style="margin-top:18px">
    <div>
      <div class="cards">
        <div class="card">
          <div>
            <h3>Total Users</h3>
            <div class="stat" id="statTotal">0</div>
            <div class="muted">All registered standard users</div>
          </div>
        </div>

        <div class="card">
          <div>
            <h3>Verified Users</h3>
            <div class="stat" id="statVerified">0</div>
            <div class="muted">Users with verified emails</div>
          </div>
        </div>

        <div class="card">
          <div>
            <h3>Pending</h3>
            <div class="stat" id="statPending">0</div>
            <div class="muted">Awaiting email verification</div>
          </div>
        </div>

        <div class="card">
          <div>
            <h3>Admins</h3>
            <div class="stat" id="statAdmins">0</div>
            <div class="muted">Administrative accounts</div>
          </div>
        </div>
      </div>

      <div style="margin-top:18px" class="quick-grid">
        <div class="quick">
          <h4>Quick Actions</h4>
          <div class="kpi">Create user, import, or run mass actions.</div>
          <div style="margin-top:8px">
            <a href="manage_users.php" class="btn btn-outline">Open Manage Users</a>
          </div>
        </div>
        <div class="quick">
          <h4>System Status</h4>
          <div class="kpi">DB: connected — Mailer: configured</div>
          <div style="margin-top:8px">
            <a href="#" class="btn btn-outline">Run Health Check</a>
          </div>
        </div>
      </div>
    </div>

    <div class="area">
      <div style="display:flex;gap:12px;margin-bottom:14px">
        <div class="nav-card" style="flex:1" onclick="location.href='manage_users.php'">
          <h4>👥 Manage Users</h4>
          <p>View, edit, verify, or remove users.</p>
        </div>
        <div class="nav-card" style="flex:1" onclick="location.href='log_placeholder.php'">
          <h4>🧾 View Logs</h4>
          <p>Audit and system logs (placeholder).</p>
        </div>
      </div>

      <div style="display:flex;gap:12px">
        <div class="nav-card" style="flex:1" onclick="location.href='settings_placeholder.php'">
          <h4>⚙️ Settings</h4>
          <p>Application settings and integrations.</p>
        </div>
        <div class="nav-card" style="flex:1" onclick="location.href='analytics_placeholder.php'">
          <h4>📊 Analytics</h4>
          <p>Traffic & usage insights (placeholder).</p>
        </div>
      </div>

      <div style="margin-top:18px">
        <div class="card" style="padding:14px">
          <h3 style="margin-bottom:8px">Admin Notes</h3>
          <div class="muted">Use the Manage Users panel to approve or remove accounts. Placeholders link to pages you can implement next.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($toast): ?>
  <div class="toast <?php echo $toast['type']; ?>"><?php echo htmlspecialchars($toast['msg']); ?></div>
<?php endif; ?>

<script>
// theme (persist)
const root = document.documentElement;
const themeBtn = document.getElementById('themeBtn');
const themeIcon = document.getElementById('themeIcon');

function setLightIcon(){ themeIcon.innerHTML = '<path fill=\"#001E6C\" d=\"M12 3v2a7 7 0 0 0 0 14v2a9 9 0 0 1 0-18z\"/>'; }
function setDarkIcon(){ themeIcon.innerHTML = '<path fill=\"#fff\" d=\"M12 4.354a1 1 0 0 1 1 0l1.732 1a1 1 0 0 1 .366 1.366l-1 1.732a1 1 0 0 1-1.366.366l-1.732-1a1 1 0 0 1-.366-1.366l1-1.732a1 1 0 0 1 1-.366zM12 20a8 8 0 1 1 0-16 8 8 0 0 1 0 16z\"/>'; }

(function(){
  const saved = localStorage.getItem('theme');
  if(saved === 'light'){ root.setAttribute('data-theme','light'); setLightIcon(); } else { root.setAttribute('data-theme','dark'); setDarkIcon(); }
})();

themeBtn.addEventListener('click', () => {
  const cur = root.getAttribute('data-theme');
  if(cur === 'light'){ root.setAttribute('data-theme','dark'); localStorage.setItem('theme','dark'); setDarkIcon(); }
  else { root.setAttribute('data-theme','light'); localStorage.setItem('theme','light'); setLightIcon(); }
});

// animated count-up
function animateCount(id, end) {
  const el = document.getElementById(id);
  if(!el) return;
  const duration = 900;
  const start = 0;
  const stepTime = Math.max(Math.floor(duration / (end || 1)), 12);
  let current = start;
  const step = Math.ceil((end - start) / (duration / stepTime));
  const timer = setInterval(() => {
    current += step;
    if(current >= end) { el.textContent = end; clearInterval(timer); }
    else el.textContent = current;
  }, stepTime);
}

// initialize stats (server-side values)
const totals = {
  total: <?php echo (int)$totalUsers; ?>,
  verified: <?php echo (int)$verifiedUsers; ?>,
  pending: <?php echo (int)$pendingUsers; ?>,
  admins: <?php echo (int)$totalAdmins; ?>
};

animateCount('statTotal', totals.total);
animateCount('statVerified', totals.verified);
animateCount('statPending', totals.pending);
animateCount('statAdmins', totals.admins);

// auto-hide toast after a while
setTimeout(()=> {
  const t = document.querySelector('.toast');
  if(t) t.style.display = 'none';
}, 3800);
</script>
</body>
</html>
