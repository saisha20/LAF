<?php
require_once 'auth.php';
require_once 'db.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = 'Please enter both email and password.';

    } else {

        // First check normal users
        $stmt = $pdo->prepare(
            'SELECT user_id, full_name, email, password_hash 
             FROM users 
             WHERE email = ? 
             LIMIT 1'
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = 'user';

            header('Location: user_dashboard.php');
            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT admin_id, full_name, email, password_hash, role
             FROM admin
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {

            $_SESSION['user_id'] = $admin['admin_id'];
            $_SESSION['full_name'] = $admin['full_name'];
            $_SESSION['role'] = 'admin';

            header('Location: admin_dashboard.php');
            exit;
        }

        $error = 'Incorrect email or password.';
    }
}
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
    <h1>Reuniting people<br>with their<br>Belongings</h1>
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