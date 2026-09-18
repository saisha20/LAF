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
    'dashboard'     => ['label' => 'Dashboard',     'href' => 'user_dashboard.php', 'icon' => '&#9638;'],
    'search'        => ['label' => 'Search Items',  'href' => 'search_items.php',   'icon' => '&#128269;'],
    'post'          => ['label' => 'Post Item',      'href' => 'report_lost.php',    'icon' => '&#10133;'],
    'reports'       => ['label' => 'Reports',        'href' => 'my_reports.php',     'icon' => '&#128203;'],
    'claims'        => ['label' => 'My Claims',      'href' => 'my_claims.php',      'icon' => '&#128196;'],
    'notifications' => ['label' => 'Notifications',  'href' => 'notifications.php',  'icon' => '&#128276;'],
    'profile'       => ['label' => 'Profile',        'href' => 'profile.php',        'icon' => '&#128100;'],
    'settings'      => ['label' => 'Settings',       'href' => 'settings.php',       'icon' => '&#9881;']

];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($pageTitle); ?> - Foundly</title>
<link rel="stylesheet" href="base.css">
<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="sidebar.css?v=3">

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
      <div class="topbar-search">
        <span>&#128269;</span>
        <input type="text" placeholder="Search for items, locations...">
      </div>
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