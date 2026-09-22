<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$adminNav = [
    'dashboard' => ['href' => 'admin_dashboard.php', 'label' => 'Dashboard', 'icon' => '<img src="image/dashboard.png" alt="">'],
    'users'     => ['href' => 'admin_users.php',     'label' => 'Users',     'icon' => '<img src="image/profile.png" alt="">'],
    'reports'   => ['href' => 'admin_reports.php',   'label' => 'Reports',   'icon' => '<img src="image/report.png" alt="">'],
    'matches'   => ['href' => 'admin_verifications.php', 'label' => 'Verify Matches', 'icon' => '<img src="image/claim.png" alt="">'],
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
<link rel="icon" type="image/png" href="image/foundly.png">
</head>
<body>

<div class="shell">

  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><img src="image/foundly.png" alt="Foundly logo"></div>
      <div class="brand-text">
        <span class="brand-name">Foundly</span>
        <span class="brand-tagline">Admin Panel</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($adminNav as $key => $item): ?>
        <a href="<?php echo $item['href']; ?>" class="sidebar-link <?php echo ($activeAdminPage ?? '') === $key ? 'active' : ''; ?>">
          <span class="sidebar-icon"><?php echo $item['icon']; ?></span>
          <?php echo htmlspecialchars($item['label']); ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <div class="main-area">

    <header class="topbar">
      <div></div>
      <div class="topbar-actions">
        <span class="nav-login">Admin: <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
        <a href="logout.php" class="logout-btn">Logout</a>
      </div>
    </header>

    <main class="dash-main">