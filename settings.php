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

    /* =========================
       NOTIFICATION PREFERENCES
       ========================= */
    if ($action === 'notifications') {

        $emailNotifications =
            isset($_POST['email_notifications']) ? 1 : 0;

        $matchNotifications =
            isset($_POST['match_notifications']) ? 1 : 0;

        $statusNotifications =
            isset($_POST['status_notifications']) ? 1 : 0;

        /*
         * Notification preferences will be stored here.
         * This requires the notification_preferences table
         * described below.
         */

        $stmt = $pdo->prepare(
            "INSERT INTO notification_preferences
                (user_id, email_notifications, match_notifications, status_notifications)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                email_notifications = VALUES(email_notifications),
                match_notifications = VALUES(match_notifications),
                status_notifications = VALUES(status_notifications)"
        );

        $stmt->execute([
            $_SESSION['user_id'],
            $emailNotifications,
            $matchNotifications,
            $statusNotifications
        ]);

        $success = 'Notification preferences have been saved.';
    }
}


/* =========================
   LOAD NOTIFICATION SETTINGS
   ========================= */

$stmt = $pdo->prepare(
    'SELECT email_notifications, match_notifications, status_notifications
     FROM notification_preferences
     WHERE user_id = ?'
);

$stmt->execute([$_SESSION['user_id']]);
$preferences = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$preferences) {
    $preferences = [
        'email_notifications' => 1,
        'match_notifications' => 1,
        'status_notifications' => 1
    ];
}

$pageTitle  = 'Account Settings';
$activePage = '';
require 'sidebar.php';
?>

<div class="settings-page">

    <div class="settings-header">
        <h1>&#9881; Account Settings</h1>
        <p>Manage your account, password, and notification preferences.</p>
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


    <!-- NOTIFICATION PREFERENCES -->

    <div class="form-card settings-card">

        <h2>&#128276; Notification Preferences</h2>

        <p class="settings-description">
            Choose which notifications you would like to receive.
        </p>

        <form method="POST" action="settings.php">

            <input type="hidden" name="action" value="notifications">

            <label class="setting-option">
                <span>
                    <strong>Match Notifications</strong>
                    <small>Get notified when a possible item match is found.</small>
                </span>

                <input
                    type="checkbox"
                    name="match_notifications"
                    <?php echo $preferences['match_notifications'] ? 'checked' : ''; ?>
                >
            </label> <br> <br>


            <label class="setting-option">
                <span>
                    <strong>Report Status Notifications</strong>
                    <small>Get updates when the status of your report changes.</small>
                </span>

                <input
                    type="checkbox"
                    name="status_notifications"
                    <?php echo $preferences['status_notifications'] ? 'checked' : ''; ?>
                >
            </label> <br> <br>


            <button type="submit" class="btn btn-navy">
                Save Preferences
            </button>

        </form>

    </div>

</div>