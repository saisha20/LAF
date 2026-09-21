<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$adminNav = [
    'dashboard' => ['href' => 'admin_dashboard.php', 'label' => 'Dashboard'],
    'users'     => ['href' => 'admin_users.php',     'label' => 'Users'],
    'reports'   => ['href' => 'admin_reports.php',   'label' => 'Reports'],
    'matches'   => ['href' => 'admin_matches.php',   'label' => 'Verify Matches'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($pageTitle ?? 'Admin'); ?> - Foundly Admin</title>
<link rel="stylesheet" href="base.css?v=2">
<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="sidebar.css">
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="admin_claim.css">
<link rel="stylesheet" href="admin_claim.css">
<link rel="icon" type="image/png" href="image/foundly.png">
</head>
<body>

<header class="site-header">
  <div class="brand">
    <div class="brand-icon"><img src="image/foundly.png" alt="Foundly logo"></div>
    <div class="brand-text">
      <span class="brand-name">Foundly</span>
      <span class="brand-tagline">Admin Panel</span>
    </div>
  </div>
  <nav class="site-nav">
    <?php foreach ($adminNav as $key => $item): ?>
      <a href="<?php echo $item['href']; ?>" class="<?php echo ($activeAdminPage ?? '') === $key ? 'active-nav' : ''; ?>">
        <?php echo htmlspecialchars($item['label']); ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="nav-actions">
    <span class="nav-login">Admin: <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
    <a href="logout.php" class="btn btn-navy">Logout</a>
  </div>
</header>

<div class="admin-main">
