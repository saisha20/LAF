<?php
require_once 'auth.php';
requireLogin();

$navItems = [
    'dashboard'     => ['label' => 'Dashboard',     'href' => 'user_dashboard.php', 'icon' => '&#9638;'],
    'search'        => ['label' => 'Search Items',  'href' => 'search_items.php',   'icon' => '&#128269;'],
    'post'          => ['label' => 'Post Item',      'href' => 'report_lost.php',    'icon' => '&#10133;'],
    'reports'       => ['label' => 'Reports',        'href' => 'my_reports.php',     'icon' => '&#128203;'],
    'notifications' => ['label' => 'Notifications',  'href' => 'notifications.php',  'icon' => '&#128276;'],
    'profile'       => ['label' => 'Profile',        'href' => 'profile.php',        'icon' => '&#128100;'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($pageTitle); ?> - Foundly</title>
<link rel="stylesheet" href="base.css">
<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="sidebar.css?v=2">
</head>
<body>

<div class="shell">

  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon">&#9737;</div>
      <div class="brand-text">
        <span class="brand-name">Foundly</span>
        <span class="brand-tagline">Reliable Recovery</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($navItems as $key => $item): ?>
        <a href="<?php echo $item['href']; ?>" class="sidebar-link <?php echo $activePage === $key ? 'active' : ''; ?>">
          <span class="sidebar-icon"><?php echo $item['icon']; ?></span>
          <?php echo htmlspecialchars($item['label']); ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <?php
      // On the Report Lost page, the quick button offers Report Found (and vice versa).
      // On every other page, it defaults to Report Lost.
      $currentFile = basename($_SERVER['PHP_SELF']);
      if ($currentFile === 'report_lost.php') {
          $postBtnHref = 'report_found.php';
          $postBtnLabel = '+ Post Found Item';
      } elseif ($currentFile === 'report_found.php') {
          $postBtnHref = 'report_lost.php';
          $postBtnLabel = '+ Post Lost Item';
      } else {
          $postBtnHref = 'report_lost.php';
          $postBtnLabel = '+ Post Item';
      }
    ?>
    <a href="<?php echo $postBtnHref; ?>" class="sidebar-post-btn">
      <?php echo htmlspecialchars($postBtnLabel); ?>
    </a>
  </aside>

  <div class="main-area">

    <header class="topbar">
      <div class="topbar-search">
        <span>&#128269;</span>
        <input type="text" placeholder="Search for items, locations...">
      </div>
      <div class="topbar-actions">
        <span class="topbar-icon">&#128276;</span>
        <span class="topbar-icon">&#9881;</span>
        <a href="logout.php" class="topbar-avatar" title="Logout (<?php echo htmlspecialchars($_SESSION['full_name']); ?>)">
          <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
        </a>
      </div>
    </header>

    <main class="dash-main">
