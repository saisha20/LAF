<?php
require_once 'auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Dashboard - Foundly</title>
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
    <a href="lost_items.php">Lost Items</a>
    <a href="found_items.php">Found Items</a>
    <a href="user_dashboard.php">Dashboard</a>
  </nav>
  <div class="nav-actions">
    <span class="nav-login">Hi, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
    <a href="logout.php" class="btn btn-navy">Logout</a>
  </div>
</header>

<div class="dash-wrap">
  <h1>Welcome, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?></h1>
  <p>This is your personal dashboard. Reporting and search features will be added here next.</p>

  <div class="dash-cards">
    <div class="dash-card">
      <h3>Report a Lost Item</h3>
      <p>Coming next - form to submit a lost item report.</p>
    </div>
    <div class="dash-card">
      <h3>Report a Found Item</h3>
      <p>Coming next - form to submit a found item report.</p>
    </div>
    <div class="dash-card">
      <h3>My Reports</h3>
      <p>Coming next - list of your submitted reports and their status.</p>
    </div>
  </div>
</div>

</body>
</html>