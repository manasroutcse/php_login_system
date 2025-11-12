<?php
@ini_set('output_buffering', 'On');
ob_start();
session_start();
require 'config.php';

// 🛡️ Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$pageTitle = "Logs & Activity";

// Try to detect if login_time column exists
$hasLoginTime = false;
$res = $conn->query("SHOW COLUMNS FROM contacts LIKE 'login_time'");
if ($res && $res->num_rows > 0) {
    $hasLoginTime = true;
}

// --- Count Total Users ---
$totalUsers = $conn->query("SELECT COUNT(*) AS c FROM contacts")->fetch_assoc()['c'] ?? 0;

// --- Count Verified Users ---
$totalVerified = $conn->query("SELECT COUNT(*) AS c FROM contacts WHERE is_verified = 1")->fetch_assoc()['c'] ?? 0;

// --- Count Admins ---
$totalAdmins = $conn->query("SELECT COUNT(*) AS c FROM contacts WHERE role = 'Admin'")->fetch_assoc()['c'] ?? 0;

// --- Recent Logins / Registrations Chart ---
if ($hasLoginTime) {
    $chartQuery = $conn->query("
        SELECT DATE(login_time) as day, COUNT(*) as total
        FROM contacts
        WHERE login_time >= DATE_SUB(NOW(), INTERVAL 14 DAY)
        GROUP BY DATE(login_time)
        ORDER BY day ASC
    ");
} else {
    $chartQuery = $conn->query("
        SELECT DATE(created_at) as day, COUNT(*) as total
        FROM contacts
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");
}

$chartData = [];
while ($row = $chartQuery->fetch_assoc()) {
    $chartData[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Logs & Activity (Glass)</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
  background: radial-gradient(circle at top left, #7c3aed44, #06b6d444), #0f172a;
  background: url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
  color: #fff;
  font-family: 'Inter', system-ui;
  min-height: 100vh;
  padding: 30px;
}
.glass-card {
  background: rgba(255, 255, 255, 0.06);
  border-radius: 16px;
  padding: 24px;
  backdrop-filter: blur(16px);
  box-shadow: 0 8px 40px rgba(0,0,0,0.5);
  border: 1px solid rgba(255,255,255,0.1);
  margin-bottom: 24px;
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
  gap: 16px;
  margin-bottom: 30px;
}
.stat-box {
  background: rgba(255,255,255,0.08);
  border-radius: 12px;
  padding: 16px;
  text-align: center;
}
.stat-number {
  font-size: 1.8rem;
  font-weight: bold;
  margin-top: 6px;
}
a.btn-glass {
  background: rgba(255,255,255,0.08);
  color: #fff;
  padding: 8px 14px;
  border-radius: 10px;
  text-decoration: none;
  border: 1px solid rgba(255,255,255,0.1);
}
a.btn-glass:hover {
  background: rgba(255,255,255,0.15);
}
canvas {
  max-height: 340px;
}
</style>
</head>
<body>

<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>📜 Logs & Activity</h3>
    <a href="admin_dashboard.php" class="btn-glass">← Back to Dashboard</a>
  </div>

  <div class="stats-grid">
    <div class="stat-box">
      <div>Total Users</div>
      <div class="stat-number"><?= $totalUsers ?></div>
    </div>
    <div class="stat-box">
      <div>Verified Users</div>
      <div class="stat-number"><?= $totalVerified ?></div>
    </div>
    <div class="stat-box">
      <div>Admin Accounts</div>
      <div class="stat-number"><?= $totalAdmins ?></div>
    </div>
  </div>

  <div class="glass-card">
    <h5><?= $hasLoginTime ? 'Recent Logins' : 'New Registrations' ?> (Last 14 Days)</h5>
    <canvas id="logChart"></canvas>
  </div>
</div>

<script>
const ctx = document.getElementById('logChart');
const chartData = <?= json_encode($chartData) ?>;
new Chart(ctx, {
  type: 'line',
  data: {
    labels: chartData.map(r => r.day),
    datasets: [{
      label: '<?= $hasLoginTime ? 'Logins' : 'Registrations' ?>',
      data: chartData.map(r => r.total),
      borderColor: '#38bdf8',
      backgroundColor: 'rgba(56,189,248,0.2)',
      tension: 0.3,
      fill: true,
    }]
  },
  options: {
    plugins: { legend: { display: true, labels: { color: '#fff' } } },
    scales: {
      x: { ticks: { color: '#fff' }, grid: { color: 'rgba(255,255,255,0.1)' } },
      y: { ticks: { color: '#fff' }, grid: { color: 'rgba(255,255,255,0.1)' } }
    }
  }
});
</script>

</body>
</html>
