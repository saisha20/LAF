<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'validate.php';

/* ------------------------------------------------------------
   SETTINGS
   ------------------------------------------------------------ */
// Address of your site, with no slash at the end.
$APP_URL = 'http://localhost/LAF';

// true  = show the reset link on the page. Use this while testing on
//         your own computer, where no email server is set up.
// false = never show the link (use this on a real website).
$SHOW_LINK_ON_SCREEN = true;
/* ------------------------------------------------------------ */

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error   = '';
$sent    = false;
$devLink = '';
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = trim($_POST['email'] ?? '');
    $csrfInput = $_POST['csrf'] ?? '';

    if (!is_string($csrfInput) || !hash_equals($_SESSION['csrf'], $csrfInput)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $check = validate_email($email);

        if (!$check['valid']) {
            $error = $check['message'];
        } else {
            try {
                // Same lookup order as login.php: admin table first, then users.
                $account = null;

                $stmt = $pdo->prepare('SELECT admin_id AS id FROM admin WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    $account = ['type' => 'admin', 'id' => (int) $row['id']];
                } else {
                    $stmt = $pdo->prepare('SELECT user_id AS id FROM users WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $account = ['type' => 'user', 'id' => (int) $row['id']];
                    }
                }

                // Housekeeping: remove links that have already expired.
                $pdo->exec('DELETE FROM password_resets WHERE expires_at < NOW()');

                if ($account) {
                    // A random secret goes in the link. Only its hash is stored.
                    $secret = bin2hex(random_bytes(32));
                    $hash   = hash('sha256', $secret);

                    // Only the newest link works for an account.
                    $stmt = $pdo->prepare('DELETE FROM password_resets WHERE account_type = ? AND account_id = ?');
                    $stmt->execute([$account['type'], $account['id']]);

                    $stmt = $pdo->prepare(
                        'INSERT INTO password_resets (account_type, account_id, token_hash, expires_at)
                         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
                    );
                    $stmt->execute([$account['type'], $account['id'], $hash]);

                    $link    = $APP_URL . '/reset_password.php?token=' . $secret;
                    $subject = 'Reset your Foundly password';
                    $body    = "Hello,\n\n"
                             . "We received a request to reset your Foundly password.\n"
                             . "Open this link to choose a new one (it works for 1 hour):\n\n"
                             . $link . "\n\n"
                             . "If you did not ask for this, you can ignore this email.\n\n"
                             . "Foundly - Reliable Recovery";
                    $headers = "From: no-reply@kathford.edu.np\r\nContent-Type: text/plain; charset=UTF-8";

                    // The @ hides the warning on computers with no mail server.
                    @mail($email, $subject, $body, $headers);

                    if ($SHOW_LINK_ON_SCREEN) {
                        $devLink = $link;
                    }
                }

                // Same answer whether or not the email exists, so nobody
                // can use this page to find out who has an account.
                $sent = true;
            } catch (PDOException $e) {
                $error = 'Something went wrong. Please try again in a moment.';
                if ($SHOW_LINK_ON_SCREEN) {
                    $error .= ' (Testing note: ' . $e->getMessage() . ')';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot Password - Foundly</title>
<link rel="stylesheet" href="base.css?v=2">
<link rel="stylesheet" href="forgot_password.css">
<link rel="icon" type="image/png" href="image/foundly.png">
</head>
<body class="recovery">

<header class="rec-header">
  <a href="index.php" class="rec-brand">
    <img src="image/foundly.png" alt="Foundly logo">
    <span>Foundly</span>
  </a>
  <a href="#" class="rec-help">Help Center</a>
</header>

<main class="rec-main">

  <section class="rec-hero">
    <h2>We'll help you get back in.</h2>
    <p>Enter your university email to receive a secure password reset link.</p>
  </section>

  <section class="rec-card">

    <?php if ($sent): ?>

      <div class="rec-icon ok">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
      </div>
      <h1>Check your email</h1>
      <p class="rec-sub">
        If an account exists for <strong><?php echo htmlspecialchars($email); ?></strong>,
        we have sent a password reset link to it. The link works for 1 hour.
      </p>

      <?php if ($devLink !== ''): ?>
        <div class="rec-dev">
          Testing mode: email is not set up on this computer, so use this link instead.<br>
          <a href="<?php echo htmlspecialchars($devLink); ?>">Open my reset link</a>
        </div>
      <?php endif; ?>

      <a href="forgot_password.php" class="rec-link-again">Use a different email</a>

    <?php else: ?>

      <div class="rec-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <h1>Forgot Password</h1>
      <p class="rec-sub">No worries! It happens to the best of us. Please enter your registered email address.</p>

      <?php if ($error): ?>
        <div class="form-alert error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form id="forgotForm" method="POST" action="forgot_password.php" novalidate>
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

        <div class="field" id="field-email">
          <label for="email">Email Address</label>
          <div class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
            <input type="text" id="email" name="email" placeholder="name@kathford.edu.np"
                   value="<?php echo htmlspecialchars($email); ?>" autocomplete="username">
          </div>
          <div class="field-msg"></div>
        </div>

        <button type="submit" class="btn btn-navy">Send Reset Link</button>
      </form>

    <?php endif; ?>

    <a href="login.php" class="rec-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
      Back to Login
    </a>

  </section>

</main>

<footer class="rec-footer">
  <div>
    <strong>Foundly</strong>
    &copy; <?php echo date('Y'); ?> University Lost &amp; Found
  </div>
  <div class="rec-footer-links">
    <a href="#">About</a>
    <a href="#">Privacy</a>
    <a href="#">Help</a>
    <a href="#">Terms</a>
  </div>
</footer>

<script src="forgot_password.js?v=2"></script>

</body>
</html>
