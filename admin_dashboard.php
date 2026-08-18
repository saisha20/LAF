<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$userCount   = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn();
$reportCount = $pdo->query('SELECT COUNT(*) FROM reports')->fetchColumn();
$pendingCount = $pdo->query('SELECT COUNT(*) FROM matches WHERE status = "pending"')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Foundly</title>
<link rel="stylesheet" href="base.css">
<link rel="stylesheet" href="dashboard.css">
</head>
<body>

<header class="site-header">
  <div class="brand">
    <div class="brand-icon">&#9737;</div>
    <div class="brand-text">
      <span class="brand-name">Foundly</span>
      <span class="brand-tagline">Reliable Recovery</span>
    </div>
  </div>
  <nav class="site-nav">
    <a href="index.php">Home</a>
    <a href="admin_dashboard.php">Admin Dashboard</a>
  </nav>
  <div class="nav-actions">
    <span class="nav-login">Admin: <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
    <a href="logout.php" class="btn btn-navy">Logout</a>
  </div>
</header>

<div class="dash-wrap">
  <h1>Admin Dashboard</h1>
  <p>Overview of the Foundly system.</p>

  <div class="dash-cards">
    <div class="dash-card">
      <h3>Registered Users</h3>
      <p><?php echo (int)$userCount; ?> users registered.</p>
    </div>
    <div class="dash-card">
      <h3>Total Reports</h3>
      <p><?php echo (int)$reportCount; ?> lost/found reports submitted.</p>
    </div>
    <div class="dash-card">
      <h3>Pending Verifications</h3>
      <p><?php echo (int)$pendingCount; ?> matches waiting for review.</p>
    </div>
  </div>
</div>

</body>
</html>