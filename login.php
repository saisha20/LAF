<?php
require_once 'auth.php';
require_once 'db.php';

// Already logged in? send them straight to their dashboard.
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        // Prepared statement - protects against SQL injection
        $stmt = $pdo->prepare('SELECT user_id, full_name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Correct credentials - start session
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            header('Location: ' . ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
            exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Foundly</title>
<link rel="stylesheet" href="base.css">
<link rel="stylesheet" href="auth.css">
<link rel="stylesheet" href="login.css">
</head>
<body>

<div class="auth-wrap">

  <aside class="auth-side">
    <div class="brand">
      <div class="brand-icon">&#9737;</div>
      <div class="brand-text">
        <span class="brand-name">Foundly</span>
        <span class="brand-tagline">Reliable Recovery</span>
      </div>
    </div>

    <h2>Reuniting people<br>With their<br>Belongings</h2>

    <div class="auth-feature"><span class="icon-box">&#128221;</span> Report lost or found items instantly</div>
    <div class="auth-feature"><span class="icon-box">&#128269;</span> Search and filter the item registry</div>
    <div class="auth-feature"><span class="icon-box">&#9989;</span> Claim verified item with ease</div>
    <div class="auth-feature"><span class="icon-box">&#128276;</span> Get notified on status update</div>
  </aside>

  <section class="auth-form-side">
    <div class="auth-form-box">
      <h1>Welcome Back</h1>
      <p>Sign in to access the lost and found dashboard.</p>

      <?php if ($error): ?>
        <div class="form-alert error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['registered'])): ?>
        <div class="form-alert success">Account created successfully. Please sign in.</div>
      <?php endif; ?>

      <form id="loginForm" method="POST" action="login.php" novalidate>

        <div class="field" id="field-email">
          <label for="email">Email Address</label>
          <input type="text" id="email" name="email" autocomplete="username">
          <div class="field-msg"></div>
        </div>

        <div class="field" id="field-password">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" autocomplete="current-password">
            <button type="button" class="password-toggle" data-target="password">Show</button>
          </div>
          <div class="field-msg"></div>
        </div>

        <div class="auth-row-inline">
          <label><input type="checkbox" name="remember"> Remember me</label>
          <a href="#">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-indigo btn-block">Sign In</button>
      </form>

      <p class="auth-switch">Don't have an account? <a href="register.php">Create one free</a></p>
    </div>
  </section>

</div>

<script src="login.js"></script>
</body>
</html>