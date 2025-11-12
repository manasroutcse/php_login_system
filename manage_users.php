<?php
@ini_set('output_buffering', 'On');
ob_start();
session_start();
require 'config.php';

// 🛡️ Allow only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// 🛡️ CSRF setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// 🗑️ Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = '⚠️ Invalid CSRF token.';
    } else {
        $delete_id = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $success = '✅ User deleted successfully.';
        } else {
            $error = '❌ Failed to delete user.';
        }
    }
}

// 🔍 Fetch all users
$users = $conn->query("SELECT id, name, email, role, status, is_verified, avatar FROM contacts ORDER BY id DESC");


// Pagination + Filters
$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$verified = $_GET['verified'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

// Build WHERE
$where = "WHERE 1=1";
$params = []; $types = '';
if ($search !== '') { $where .= " AND (name LIKE ? OR email LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; $types.="ss"; }
if ($role !== '') { $where .= " AND role = ?"; $params[]=$role; $types.="s"; }
if ($status !== '') { $where .= " AND status = ?"; $params[]=$status; $types.="s"; }
if ($verified !== '') { $where .= " AND is_verified = ?"; $params[] = intval($verified); $types.="i"; }

// Count
$countSql = "SELECT COUNT(*) AS total FROM contacts $where";
$stmt = $conn->prepare($countSql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Fetch users
$sql = "SELECT id, name, email, role, status, is_verified, created_at FROM contacts $where ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $types .= "ii";
    $stmt->bind_param($types, ...array_merge($params, [$perPage, $offset]));
} else {
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    background: radial-gradient(circle at top left, rgba(120,80,250,0.25), transparent 50%), 
                radial-gradient(circle at bottom right, rgba(0,200,255,0.15), transparent 60%), 
                #0f172a;
                background: url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
    min-height: 100vh;
    font-family: "Inter", sans-serif;
    color: #fff;
    padding: 40px;
  }
  .glass {
    background: rgba(255,255,255,0.07);
    border-radius: 18px;
    padding: 25px;
    border: 1px solid rgba(255,255,255,0.15);
    backdrop-filter: blur(14px);
    box-shadow: 0 8px 40px rgba(0,0,0,0.35);
  }
  table {
    color: #fff;
    border-collapse: separate;
    border-spacing: 0 8px;
  }
  th {
    background: rgba(255,255,255,0.1);
    border: none;
    padding: 12px;
  }
  td {
    background: rgba(255,255,255,0.05);
    border: none;
    padding: 12px;
    vertical-align: middle;
  }
  .btn-edit, .btn-delete {
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 14px;
  }
  .btn-edit {
    background: rgba(0, 255, 110, 0.89);
    color: #fff;
  }
  .btn-delete {
    background: rgba(255, 0, 0, 0.87);
    color: #fff;
  }
  .btn-edit:hover { background: rgba(0,224,255,0.45); }
  .btn-delete:hover { background: rgba(255,0,0,0.45); }
  .avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid rgba(255,255,255,0.2);
  }
  .alert { border-radius: 10px; font-size: 14px; }
  .alert-success { background: rgba(0,255,0,0.12); color: #b8ffb8; }
  .alert-danger { background: rgba(255,0,0,0.15); color: #ffb6b6; }
  .search-box {
    background: rgba(255,255,255,0.08);
    border: none;
    border-radius: 10px;
    color: #fff;
    padding: 10px 15px;
    width: 300px;
  }
  
.glass-container {
  background: rgba(255,255,255,0.06);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.4);
}
h2 { font-weight: 600; margin-bottom: 20px; }
.table-glass th, .table-glass td {
  background: rgba(255,255,255,0.04);
  border: none;
  color: #fff;
}
.btn-glass {
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.1);
  color: #fff;
  border-radius: 8px;
  transition: 0.2s;
}
.btn-glass:hover { background: rgba(255,255,255,0.2); }
.filter-bar select, .filter-bar input {
  background: rgba(255,255,255,0.08);
  border: none; color: #fff;
  border-radius: 8px; padding: 6px 10px;
}
.pagination .page-link { background: rgba(255,255,255,0.08); border: none; color: #fff; }
.pagination .active .page-link { background: #7c3aed; }

</style>
</head>
<body>

<div class="glass">
  <h3 class="mb-4 text-center">👥 Manage Users</h3>

  <?php if ($error): ?><div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <input type="text" id="search" class="search-box" placeholder="🔍 Search by name or email...">
    <a href="admin_dashboard.php" class="btn btn-sm btn-light">⬅ Back to Dashboard</a>
  </div>

<!-- <form method="get" class="filter-bar mb-3 d-flex flex-wrap gap-2">
    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
    <select name="role">
      <option value="">All Roles</option>
      <option value="Admin" <?= $role=='Admin'?'selected':'' ?>>Admin</option>
      <option value="User" <?= $role=='User'?'selected':'' ?>>User</option>
    </select>
    <select name="status">
      <option value="">All Status</option>
      <option value="Active" <?= $status=='Active'?'selected':'' ?>>Active</option>
      <option value="Pending" <?= $status=='Pending'?'selected':'' ?>>Pending</option>
      <option value="Suspended" <?= $status=='Suspended'?'selected':'' ?>>Suspended</option>
    </select>
    <select name="verified">
      <option value="">Verified?</option>
      <option value="1" <?= $verified==='1'?'selected':'' ?>>Yes</option>
      <option value="0" <?= $verified==='0'?'selected':'' ?>>No</option>
    </select>
    <button class="btn btn-glass" type="submit">Apply</button>
  </form> -->
  <table class="table table-borderless align-middle" id="userTable">
    <thead>
      <tr>
        <th>Avatar</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Verified</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($u = $users->fetch_assoc()): ?>
      <tr>
        <td><img src="<?= htmlspecialchars($u['avatar']) ?>" class="avatar"></td>
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= htmlspecialchars($u['role']) ?></td>
        <td><?= htmlspecialchars($u['status']) ?></td>
        <td><?= $u['is_verified'] ? '✅' : '❌' ?></td>
        <td>
          <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn-edit btn-sm">Edit</a>
          <button type="button" class="btn-delete btn-sm" data-id="<?= $u['id'] ?>" data-name="<?= htmlspecialchars($u['name']) ?>" onclick="confirmDelete(this)">Delete</button>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <nav>
    <ul class="pagination justify-content-center">
      <?php for ($i=1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i==$page?'active':'' ?>">
          <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&status=<?= urlencode($status) ?>&verified=<?= urlencode($verified) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<!-- 🧊 Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);border-radius:16px;border:1px solid rgba(255,255,255,0.2);color:#fff;">
      <div class="modal-header border-0">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
      </div>
      <div class="modal-body">
        <p id="deleteMsg">Are you sure you want to delete this user?</p>
      </div>
      <div class="modal-footer border-0">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="delete_id" id="deleteUserId">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(btn) {
  const id = btn.getAttribute('data-id');
  const name = btn.getAttribute('data-name');
  document.getElementById('deleteUserId').value = id;
  document.getElementById('deleteMsg').innerText = `Are you sure you want to delete "${name}"?`;
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('search').addEventListener('input', function() {
  const term = this.value.toLowerCase();
  document.querySelectorAll('#userTable tbody tr').forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
  });
});
</script>

</body>
</html>
