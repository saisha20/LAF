<?php
require_once 'auth.php';
require_once 'db.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* =========================
       CHANGE PASSWORD
       ========================= */
    if ($action === 'change_password') {

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $error = 'Please fill in all password fields.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirm password do not match.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } else {

            $stmt = $pdo->prepare(
                'SELECT password_hash FROM users WHERE user_id = ?'
            );
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {

                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    'UPDATE users SET password_hash = ? WHERE user_id = ?'
                );
                $stmt->execute([
                    $newHash,
                    $_SESSION['user_id']
                ]);

                $success = 'Your password has been changed successfully.';
            }
        }
    }
}

$pageTitle  = 'Account Settings';
$activePage = '';
require 'sidebar.php';
?>

<div class="settings-page">

    <div class="settings-header">
        <h1>&#9881; Account Settings</h1>
        <p>Manage your account and password.</p>
    </div>

    <?php if ($error): ?>
        <div class="form-alert error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="form-alert success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>


    <!-- CHANGE PASSWORD -->

    <div class="form-card settings-card">

        <h2>&#128273; Change Password</h2>

        <p class="settings-description">
            Update your password to keep your Foundly account secure.
        </p>

        <form method="POST" action="settings.php">

            <input type="hidden" name="action" value="change_password">

            <div class="field">
                <label for="current_password">Current Password</label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                >
            </div>

            <div class="field">
                <label for="new_password">New Password</label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    minlength="8"
                    required
                >
            </div>

            <div class="field">
                <label for="confirm_password">Confirm New Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    required
                >
            </div>

            <button type="submit" class="btn btn-navy">
                Change Password
            </button>

        </form>

    </div>

</div>
 