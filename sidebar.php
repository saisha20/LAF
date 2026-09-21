<?php
/**
 * sidebar.php
 * Shared include for every logged-in page (dashboard, post item,
 * search, reports, notifications, profile, claims).
 *
 * Before including this file, the calling page must set:
 *   $pageTitle   - shown in <title> and browser tab
 *   $activePage  - one of: dashboard, search, post, reports, claims, notifications, profile
 */

require_once 'auth.php';
requireLogin();

$navItems = [

    'dashboard'     => ['label' => 'Dashboard',     'href' => 'user_dashboard.php', 'icon' => '<img src="image/dashboard.png" alt="">'],
    'search'        => ['label' => 'Search Items',  'href' => 'search_items.php',   'icon' => '<img src="image/search.png" alt="">'],
    'post'          => ['label' => 'Post Item',     'href' => 'report_lost.php',    'icon' => '<img src="image/post.png" alt="">'],
    'reports'       => ['label' => 'Reports',       'href' => 'my_reports.php',     'icon' => '<img src="image/report.png" alt="">'],
    'claims'        => ['label' => 'My Claims',     'href' => 'my_claims.php',      'icon' => '<img src="image/claim.png" alt="">'],
    'notifications' => ['label' => 'Notifications', 'href' => 'notifications.php',  'icon' => '<img src="image/notification.png" alt="">'],
    'profile'       => ['label' => 'Profile',       'href' => 'profile.php',        'icon' => '<img src="image/profile.png" alt="">'],
    'settings'      => ['label' => 'Settings',      'href' => 'settings.php',       'icon' => '<img src="image/setting.png" alt="">']

];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($pageTitle); ?> - Foundly</title>
<link rel="stylesheet" href="base.css?v=2">
<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="sidebar.css?v=5">

<link rel="icon" type="image/png" href="image/foundly.png">
</head>
<body>

<div class="shell">

  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><img src="image/foundly.png" alt="Foundly logo"></div>
      <div class="brand-text">
        <span class="brand-name">Foundly</span>
        <span class="brand-tagline">Reliable Recovery</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($navItems as $key => $navItem): ?>
        <a href="<?php echo $navItem['href']; ?>" class="sidebar-link <?php echo $activePage === $key ? 'active' : ''; ?>">
          <span class="sidebar-icon"><?php echo $navItem['icon']; ?></span>
          <?php echo htmlspecialchars($navItem['label']); ?>
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
            <form class="topbar-search" action="search_items.php" method="GET">
        <span>&#128269;</span>
        <input type="text" name="q" placeholder="Search for items, locations..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
      </form>
<div class="topbar-actions">
    <a href="settings.php" class="topbar-icon" title="Account Settings">&#9881;</a>

    <a href="notifications.php" class="topbar-icon" title="Notifications">&#128276;</a>

    <a href="profile.php" class="topbar-avatar" title="Profile">
        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
    </a>

    <a href="logout.php" class="logout-btn">Logout</a>
</div>
    </header>

    <main class="dash-main">