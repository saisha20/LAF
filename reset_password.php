<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'validate.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';

// The secret comes from the link (?token=...) and is sent back in a hidden field.
$secret = $_GET['token'] ?? ($_POST['token'] ?? '');
if (!is_string($secret)) {
    $secret = '';
}

// Find the reset request that matches the secret and has not expired.
$reset = null;
if (preg_match('/^[a-f0-9]{64}$/', $secret)) {
    try {
        $stmt = $pdo->prepare(
            'SELECT reset_id, account_type, account_id
             FROM password_resets
             WHERE token_hash = ? AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([hash('sha256', $secret)]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $reset = null;
    }
}

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfInput = $_POST['csrf'] ?? '';
    $newPass   = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';

    if (!is_string($csrfInput) || !hash_equals($_SESSION['csrf'], $csrfInput)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $check = validate_password($newPass);

        if (!$check['valid']) {
            $error = $check['message'];
        } elseif ($newPass !== $confirm) {
            $error = 'The two passwords do not match.';
        } else {
            try {
                // Table and column names come from this fixed choice, never from the user.
                $isAdmin = ($reset['account_type'] === 'admin');
                $table   = $isAdmin ? 'admin' : 'users';
                $idCol   = $isAdmin ? 'admin_id' : 'user_id';

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE $table SET password_hash = ? WHERE $idCol = ?");
                $stmt->execute([password_hash($newPass, PASSWORD_DEFAULT), $reset['account_id']]);

                // The link can only be used once.
                $stmt = $pdo->prepare('DELETE FROM password_resets WHERE account_type = ? AND account_id = ?');
                $stmt->execute([$reset['account_type'], $reset['account_id']]);

                $pdo->commit();

                header('Location: login.php?reset=1');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Something went wrong. Please try again in a moment.';
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
<title>Reset Password - Foundly</title>
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
    <h2>Choose a new password.</h2>
    <p>Pick something strong that you have not used on other websites.</p>
  </section>

  <section class="rec-card">

    <?php if ($reset): ?>

      <div class="rec-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <h1>Reset Password</h1>
      <p class="rec-sub">Enter your new password below.</p>

      <?php if ($error): ?>
        <div class="form-alert error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form id="resetForm" method="POST" action="reset_password.php" novalidate>
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($secret); ?>">

        <div class="field" id="field-password">
          <label for="password">New Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" autocomplete="new-password">
            <button type="button" class="password-toggle" data-target="password">Show</button>
          </div>
          <div class="field-hint">At least 8 characters, with a letter, a number and a symbol.</div>
          <div class="field-msg"></div>
        </div>

        <div class="field" id="field-confirm">
          <label for="confirm">Confirm New Password</label>
          <div class="password-wrap">
            <input type="password" id="confirm" name="confirm" autocomplete="new-password">
            <button type="button" class="password-toggle" data-target="confirm">Show</button>
          </div>
          <div class="field-msg"></div>
        </div>

        <button type="submit" class="btn btn-navy">Update Password</button>
      </form>

    <?php else: ?>

      <div class="rec-icon bad">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
      </div>
      <h1>Link not valid</h1>
      <p class="rec-sub">This reset link is invalid or has expired. Reset links only work for 1 hour and only once.</p>
      <a href="forgot_password.php" class="btn btn-navy">Request a new link</a>

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

<script src="reset_password.js?v=1"></script>

</body>
</html>
