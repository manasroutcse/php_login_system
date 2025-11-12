<?php
@ini_set('output_buffering','On');
ob_start();
session_start();
require 'config.php';
date_default_timezone_set('Asia/Kolkata');

// ---------------- AUTH ----------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// ---------------- CSRF ----------------
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf_token'];

// ---------------- STATS ----------------
// Total Users
$totalUsers = $conn->query("SELECT COUNT(*) AS c FROM contacts")->fetch_assoc()['c'] ?? 0;

// Verified Users
$verifiedUsers = $conn->query("SELECT COUNT(*) AS c FROM contacts WHERE is_verified = 1")->fetch_assoc()['c'] ?? 0;

// Pending Verification
$pendingUsers = $conn->query("SELECT COUNT(*) AS c FROM contacts WHERE is_verified = 0")->fetch_assoc()['c'] ?? 0;

// New this week
$newThisWeek = $conn->query("SELECT COUNT(*) AS c FROM contacts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['c'] ?? 0;

// Role distribution
$roleData = $conn->query("SELECT role, COUNT(*) AS count FROM contacts GROUP BY role");
$roleLabels = [];
$roleCounts = [];
while ($r = $roleData->fetch_assoc()) {
    $roleLabels[] = $r['role'];
    $roleCounts[] = (int)$r['count'];
}

// User growth (last 6 months)
$growthQuery = "
  SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS total
  FROM contacts
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY YEAR(created_at), MONTH(created_at)
  ORDER BY YEAR(created_at), MONTH(created_at)
";
$growthRes = $conn->query($growthQuery);
$growthLabels = [];
$growthCounts = [];
while ($g = $growthRes->fetch_assoc()) {
    $growthLabels[] = $g['month'];
    $growthCounts[] = (int)$g['total'];
}

// Daily login or activity placeholder (use contacts.created_at for now)
$activityQuery = "
  SELECT DAYNAME(created_at) AS day, COUNT(*) AS total
  FROM contacts
  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  GROUP BY DAYNAME(created_at)
  ORDER BY FIELD(day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
";
$activityRes = $conn->query($activityQuery);
$activityLabels = [];
$activityCounts = [];
while ($a = $activityRes->fetch_assoc()) {
    $activityLabels[] = $a['day'];
    $activityCounts[] = (int)$a['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>📊 Analytics — Glass Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
body {
  background: radial-gradient(circle at 10% 10%, rgba(124,58,237,0.12), transparent 10%),
              radial-gradient(circle at 90% 90%, rgba(6,182,212,0.08), transparent 15%),
              #0f172a;
  color: #fff;
  font-family: "Inter", system-ui, sans-serif;
  padding: 30px;
  overflow-x: hidden;
  background: url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
}
.container-glass {
  background: rgba(255,255,255,0.05);
  border-radius: 18px;
  padding: 25px;
  backdrop-filter: blur(14px);
  border: 1px solid rgba(255,255,255,0.15);
  box-shadow: 0 10px 40px rgba(0,0,0,0.4);
}
.card-glass {
  background: rgba(255,255,255,0.06);
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.12);
  padding: 20px;
  backdrop-filter: blur(8px);
  box-shadow: 0 4px 20px rgba(0,0,0,0.2);
  transition: transform 0.3s ease;
}
.card-glass:hover { transform: translateY(-4px); }
h4 { margin-bottom: 20px; }
.stat-value {
  font-size: 1.8rem;
  font-weight: 600;
}
.stat-label {
  font-size: 0.9rem;
  color: rgba(255,255,255,0.7);
}
.chart-container {
  background: rgba(255,255,255,0.04);
  border-radius: 16px;
  padding: 20px;
  margin-top: 20px;
}
.btn-glass {
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.15);
  color: #fff;
  padding: 8px 14px;
  border-radius: 10px;
  text-decoration: none;
  transition: background 0.3s ease;
}
.btn-glass:hover { background: rgba(255,255,255,0.2); color:#fff; }
</style>
</head>
<body>

<div class="container container-glass">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4>📊 Analytics Dashboard</h4>
    <a href="admin_dashboard.php" class="btn-glass">← Back to Dashboard</a>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3">
    <div class="col-md-3 col-6">
      <div class="card-glass text-center">
        <div class="stat-value"><?= $totalUsers ?></div>
        <div class="stat-label">Total Users</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card-glass text-center">
        <div class="stat-value text-success"><?= $verifiedUsers ?></div>
        <div class="stat-label">Verified Users</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card-glass text-center">
        <div class="stat-value text-info"><?= $newThisWeek ?></div>
        <div class="stat-label">New This Week</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="card-glass text-center">
        <div class="stat-value text-warning"><?= $pendingUsers ?></div>
        <div class="stat-label">Pending Verification</div>
      </div>
    </div>
  </div>

  <!-- Charts -->
  <div class="chart-container">
    <h5>📈 User Growth (Last 6 Months)</h5>
    <canvas id="userGrowthChart" height="100"></canvas>
  </div>

  <div class="chart-container">
    <h5>🔍 Role Distribution</h5>
    <canvas id="roleChart" height="80"></canvas>
  </div>

  <div class="chart-container">
    <h5>🕐 Weekly Signups</h5>
    <canvas id="activityChart" height="80"></canvas>
  </div>
</div>

<script>
const growthLabels = <?= json_encode($growthLabels) ?>;
const growthData = <?= json_encode($growthCounts) ?>;
const roleLabels = <?= json_encode($roleLabels) ?>;
const roleData = <?= json_encode($roleCounts) ?>;
const activityLabels = <?= json_encode($activityLabels) ?>;
const activityData = <?= json_encode($activityCounts) ?>;

new Chart(document.getElementById('userGrowthChart'), {
  type: 'line',
  data: {
    labels: growthLabels,
    datasets: [{
      label: 'Users',
      data: growthData,
      borderColor: '#7c3aed',
      backgroundColor: 'rgba(124,58,237,0.3)',
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#fff'
    }]
  },
  options: { scales: { x: { ticks: { color: '#fff' } }, y: { ticks: { color: '#fff' } } },
             plugins: { legend: { labels: { color: '#fff' } } } }
});

new Chart(document.getElementById('roleChart'), {
  type: 'doughnut',
  data: {
    labels: roleLabels,
    datasets: [{ data: roleData, backgroundColor: ['#7c3aed', '#06b6d4', '#f59e0b', '#10b981'], borderWidth: 0 }]
  },
  options: { plugins: { legend: { labels: { color: '#fff' } } } }
});

new Chart(document.getElementById('activityChart'), {
  type: 'bar',
  data: {
    labels: activityLabels,
    datasets: [{ label: 'Signups', data: activityData, backgroundColor: 'rgba(6,182,212,0.6)', borderRadius: 6 }]
  },
  options: { scales: { x: { ticks: { color: '#fff' } }, y: { ticks: { color: '#fff' } } },
             plugins: { legend: { labels: { color: '#fff' } } } }
});
</script>
</body>
</html>
