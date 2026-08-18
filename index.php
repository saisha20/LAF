<?php
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Foundly - Reliable Recovery</title>
<link rel="stylesheet" href="base.css">
<link rel="stylesheet" href="index.css">
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
    <a href="<?php echo isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>">Dashboard</a>
  </nav>

  <div class="nav-actions">
    <?php if (isLoggedIn()): ?>
      <span class="nav-login">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?></span>
      <a href="logout.php" class="btn btn-navy">Logout</a>
    <?php else: ?>
      <a href="login.php" class="nav-login">LOGIN</a>
      <a href="register.php" class="btn btn-navy">REGISTER</a>
    <?php endif; ?>
  </div>
</header>

<section class="hero">
  <div class="hero-text">
    <h1>Reuniting people<br>With their<br>Belongings</h1>
    <p>Report lost items, submit found objects and track claims, all in one place for Kathford International College.</p>
    <div class="hero-actions">
      <a href="report_lost.php" class="btn btn-navy">Report Lost &rarr;</a>
      <a href="report_found.php" class="btn btn-navy">Report Found &rarr;</a>
    </div>
  </div>
  <div class="hero-art">
    <div class="magnifier-circle">
      <span>&#128181;</span>
      <span>&#128241;</span>
      <span>&#128085;</span>
    </div>
  </div>
</section>

<section class="why-section">
  <h2>Why Use Our System?</h2>
  <div class="why-cards">
    <div class="why-card">
      <div class="why-icon">&#128172;</div>
      <h3>Easy Reporting</h3>
      <p>Report lost or found items in just a few steps</p>
    </div>
    <div class="why-card">
      <div class="why-icon">&#128269;</div>
      <h3>Smart Search</h3>
      <p>Search an item quickly to find what you need</p>
    </div>
    <div class="why-card">
      <div class="why-icon">&#128274;</div>
      <h3>Privacy Protected</h3>
      <p>Contact details are shared only after admin's verification</p>
    </div>
  </div>

</body>
</html>