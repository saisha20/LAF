<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'validate.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit;
}

$error = '';

$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $old['phone']     = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';

    $nameCheck  = validate_full_name($old['full_name']);
    $emailCheck = validate_email($old['email']);
    $phoneCheck = validate_phone($old['phone']);
    $passCheck  = validate_password($password);

    if (!$nameCheck['valid'])       $error = $nameCheck['message'];
    elseif (!$emailCheck['valid'])  $error = $emailCheck['message'];
    elseif (!$phoneCheck['valid'])  $error = $phoneCheck['message'];
    elseif (!$passCheck['valid'])   $error = $passCheck['message'];

    if ($error === '') {
    
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        }
    }

       if ($error === '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
       
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$old['full_name'], $old['email'], $old['phone'], $hash]);

     
        $_SESSION['user_id']   = $pdo->lastInsertId();
        $_SESSION['full_name'] = $old['full_name'];
        $_SESSION['role']      = 'user';

        header('Location: user_dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Account - Foundly</title>
<link rel="stylesheet" href="base.css">
<link rel="stylesheet" href="auth.css?v=2">
<link rel="stylesheet" href="register.css">
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

    <h2>Reuniting people<br>with their<br>Belongings</h2>

    <div class="auth-feature"><span class="icon-box">&#128221;</span> Report lost or found items instantly</div>
    <div class="auth-feature"><span class="icon-box">&#128269;</span> Search and filter the item registry</div>
    <div class="auth-feature"><span class="icon-box">&#9989;</span> Claim verified item with ease</div>
    <div class="auth-feature"><span class="icon-box">&#128276;</span> Get notified on status update</div>
  </aside>

  <section class="auth-form-side">
    <div class="auth-form-box">
      <h1>Create your account</h1>
      <p>Enter your details to join the University recovery network.</p>

      <?php if ($error): ?>
        <div class="form-alert error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form id="registerForm" method="POST" action="register.php" novalidate>

        <div class="field" id="field-full_name">
          <label for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($old['full_name']); ?>" autocomplete="name">
          <div class="field-msg"></div>
        </div>

        <div class="field" id="field-email">
          <label for="email">University Email</label>
          <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($old['email']); ?>" placeholder="student@kathford.edu.np" autocomplete="email">
          <div class="field-msg"></div>
        </div>

        <div class="field" id="field-phone">
          <label for="phone">Phone Number</label>
          <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($old['phone']); ?>" placeholder="98XXXXXXXX" maxlength="10" autocomplete="tel">
          <div class="field-msg"></div>
        </div>

        <div class="field" id="field-password">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" autocomplete="new-password">
            <button type="button" class="password-toggle" data-target="password">Show</button>
          </div>
          <div class="field-msg"></div>
          <div class="field-hint">Must be at least 8 characters, with letters, numbers and a symbol.</div>
        </div>

        <button type="submit" class="btn btn-indigo btn-block">Create Account &rarr;</button>
      </form>

      <p class="auth-switch">Already have an account? <a href="login.php">Log in here</a></p>
    </div>
  </section>

</div>

<script src="register.js?v=6"></script>
</body>
</html>
