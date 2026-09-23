<?php
require_once 'auth.php';
require_once 'db.php';
requireLogin();
if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit;
}

$uid = $_SESSION['user_id'];

if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->execute([$uid]);
    header('Location: notifications.php');
    exit;
}

if (isset($_GET['read']) && isset($_GET['redirect'])) {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?');
    $stmt->execute([(int)$_GET['read'], $uid]);
    header('Location: ' . $_GET['redirect']);
    exit;
}

$tab = $_GET['tab'] ?? 'all'; 
$where  = ['user_id = ?'];
$params = [$uid];

if ($tab === 'unread') {
    $where[] = 'is_read = 0';
} elseif ($tab === 'matches') {
    $where[] = "type IN ('match_approved','potential_match','claim_review')";
} elseif ($tab === 'account') {
    $where[] = "type = 'system'";
}

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC');
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$groups = ['Today' => [], 'Yesterday' => [], 'Older' => []];
foreach ($notifications as $n) {
    $date = date('Y-m-d', strtotime($n['created_at']));
    if ($date === date('Y-m-d')) {
        $groups['Today'][] = $n;
    } elseif ($date === date('Y-m-d', strtotime('-1 day'))) {
        $groups['Yesterday'][] = $n;
    } else {
        $groups['Older'][] = $n;
    }
}

$typeIcon = [
    'match_approved'  => ['&#9989;', '#e0f7e9', '#1f9d55'],
    'potential_match' => ['&#128161;', '#e4e9fb', '#3949ab'],
    'claim_review'    => ['&#8635;', '#f0f0f0', '#6b7280'],
    'system'          => ['&#128274;', '#f0f0f0', '#6b7280'],
];

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 3600) return max(1, floor($diff / 60)) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    return date('M j, g:i A', strtotime($datetime));
}

$pageTitle  = 'Notifications';
$activePage = 'notifications';
require 'sidebar.php';
?>

<?php if (isset($_GET['dismissed'])): ?>
  <?php if ($_GET['dismissed'] === '1'): ?>
    <div class="form-alert success" style="margin-bottom:16px;">Got it - that match has been removed. Your report is still open.</div>
  <?php else: ?>
    <div class="form-alert error" style="margin-bottom:16px;">This match has already been resolved (verified or rejected by an admin), so there was nothing left to dismiss. Your report is unaffected.</div>
  <?php endif; ?>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
  <div>
    <h1>Notifications Hub</h1>
    <p class="page-sub">Stay updated on your lost and found items' status.</p>
  </div>
  <a href="?mark_all_read=1&tab=<?php echo htmlspecialchars($tab); ?>" style="font-size:12.5px;color:var(--indigo);white-space:nowrap;">&#10003; Mark all as read</a>
</div>

<div class="type-tabs" style="margin-bottom:0;">
  <a href="?tab=all" class="<?php echo $tab === 'all' ? 'active' : ''; ?>">All</a>
  <a href="?tab=unread" class="<?php echo $tab === 'unread' ? 'active' : ''; ?>">Unread</a>
  <a href="?tab=matches" class="<?php echo $tab === 'matches' ? 'active' : ''; ?>">Matches</a>
  <a href="?tab=account" class="<?php echo $tab === 'account' ? 'active' : ''; ?>">Account Updates</a>
</div>
<div style="border-bottom:1px solid var(--border-gray);margin-bottom:22px;"></div>

<?php if (empty($notifications)): ?>
  <p style="color:var(--text-muted);font-size:13px;">
    No notifications yet. Once an admin verifies a match on one of your reports, it will appear here.
  </p>
<?php else: ?>
  <?php foreach ($groups as $groupLabel => $items):
      if (empty($items)) continue;
  ?>
    <p style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);font-weight:700;margin-bottom:10px;"><?php echo $groupLabel; ?></p>

    <?php foreach ($items as $n):
        [$icon, $bg, $fg] = $typeIcon[$n['type']];
        $redirect = 'report_details.php?id=' . (int)$n['link_report_id'];
        $contactRedirect = 'contact_info.php?report_id=' . (int)$n['link_report_id'];
    ?>
      <div class="notif-card <?php echo $n['is_read'] ? '' : 'notif-unread'; ?>">
        <span class="notif-icon" style="background:<?php echo $bg; ?>;color:<?php echo $fg; ?>;"><?php echo $icon; ?></span>
        <div class="notif-body">
          <div class="notif-head">
            <strong><?php echo htmlspecialchars($n['title']); ?></strong>
            <span class="notif-time"><?php echo time_ago($n['created_at']); ?><?php echo $n['is_read'] ? '' : ' <span class="notif-dot"></span>'; ?></span>
          </div>
          <p><?php echo htmlspecialchars($n['message']); ?></p>

          <div class="notif-actions">
            <?php if ($n['type'] === 'match_approved'): ?>
              <a href="?read=<?php echo $n['notification_id']; ?>&redirect=<?php echo urlencode($contactRedirect); ?>&tab=<?php echo $tab; ?>" class="btn btn-navy" style="padding:8px 14px;font-size:12px;">Contact Info</a>
              <a href="?read=<?php echo $n['notification_id']; ?>&redirect=<?php echo urlencode($redirect); ?>&tab=<?php echo $tab; ?>" class="btn btn-outline" style="padding:8px 14px;font-size:12px;">View Item Details</a>
            <?php elseif ($n['type'] === 'potential_match'): ?>
              <a href="?read=<?php echo $n['notification_id']; ?>&redirect=<?php echo urlencode($redirect); ?>&tab=<?php echo $tab; ?>" class="btn btn-navy" style="padding:8px 14px;font-size:12px;">View Match</a>
              <a href="not_my_item.php?report_id=<?php echo (int)$n['link_report_id']; ?>&notification_id=<?php echo $n['notification_id']; ?>&tab=<?php echo urlencode($tab); ?>" class="btn btn-outline" style="padding:8px 14px;font-size:12px;" onclick="return confirm('Mark this as not your item? The match will be removed and both reports stay open.');">Not My Item</a>
            <?php elseif ($n['type'] === 'claim_review'): ?>
              <a href="?read=<?php echo $n['notification_id']; ?>&redirect=<?php echo urlencode($redirect); ?>&tab=<?php echo $tab; ?>" style="font-size:12px;color:var(--indigo);">Track Status</a>
            <?php else: ?>
              <a href="?read=<?php echo $n['notification_id']; ?>&redirect=profile.php&tab=<?php echo $tab; ?>" style="font-size:12px;color:var(--indigo);">Manage Account</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endforeach; ?>
<?php endif; ?>
