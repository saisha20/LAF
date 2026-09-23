<?php
/**
 * not_my_item.php
 *
 * Handles the "Not My Item" button on a potential-match notification.
 * The report owner is telling us the auto-suggested match is wrong,
 * so the pending match between their report and the other report is
 * rejected. This never touches VERIFIED matches - only ones still
 * waiting for admin review.
 */
require_once 'auth.php';
require_once 'db.php';
requireLogin();
if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit;
}

$uid            = $_SESSION['user_id'];
$otherReportId  = (int) ($_GET['report_id'] ?? 0);
$notificationId = (int) ($_GET['notification_id'] ?? 0);

$allowedTabs = ['all', 'unread', 'matches', 'account'];
$tab = $_GET['tab'] ?? 'all';
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'all';
}

$dismissed = 0;

if ($otherReportId > 0) {
    // Find a still-pending match that actually pairs the other report
    // with a report belonging to the person clicking the button. This
    // is what stops someone from dismissing a match that isn't theirs.
    $stmt = $pdo->prepare(
        "SELECT match_id
         FROM matches
         WHERE status = 'pending'
           AND (
                (lost_report_id = ? AND found_report_id IN (SELECT report_id FROM reports WHERE user_id = ?))
             OR (found_report_id = ? AND lost_report_id  IN (SELECT report_id FROM reports WHERE user_id = ?))
           )
         LIMIT 1"
    );
    $stmt->execute([$otherReportId, $uid, $otherReportId, $uid]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($match) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE matches
                 SET status = 'rejected', verified_at = NOW(), rejection_reason = ?
                 WHERE match_id = ? AND status = 'pending'"
            );
            $stmt->execute(['Marked as not their item by the user.', $match['match_id']]);
        } catch (PDOException $e) {
            // rejection_reason column not present yet - reject anyway.
            $stmt = $pdo->prepare(
                "UPDATE matches
                 SET status = 'rejected', verified_at = NOW()
                 WHERE match_id = ? AND status = 'pending'"
            );
            $stmt->execute([$match['match_id']]);
        }
        $dismissed = 1;
    }
}

if ($notificationId > 0) {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?');
    $stmt->execute([$notificationId, $uid]);
}

header('Location: notifications.php?tab=' . urlencode($tab) . ($dismissed ? '&dismissed=1' : '&dismissed=0'));
exit;
