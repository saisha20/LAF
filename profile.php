<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'validate.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName  = trim($_POST['full_name'] ?? '');
    $newPhone = trim($_POST['phone'] ?? '');

    $nameCheck  = validate_full_name($newName);
    $phoneCheck = validate_phone($newPhone);

    if (!$nameCheck['valid']) {
        $error = $nameCheck['message'];
    } elseif (!$phoneCheck['valid']) {
        $error = $phoneCheck['message'];
    } else {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?');
        $stmt->execute([$newName, $newPhone, $_SESSION['user_id']]);
        $_SESSION['full_name'] = $newName;
        $success = 'Your profile has been updated.';
    }
}

$stmt = $pdo->prepare('SELECT full_name, email, phone, user_type, created_at FROM users WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

// ---- Contribution stats, derived from real data ----
// "Items Returned" = the user's own reports that reached status 'closed'
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'closed'");
$stmt->execute([$_SESSION['user_id']]);
$itemsReturned = (int)$stmt->fetchColumn();

// "Successful Matches" = verified matches linking any of the user's reports
$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT m.match_id)
     FROM matches m
     JOIN reports rl ON rl.report_id = m.lost_report_id
     JOIN reports rf ON rf.report_id = m.found_report_id
     WHERE (rl.user_id = ? OR rf.user_id = ?) AND m.status = 'verified'"
);
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$successfulMatches = (int)$stmt->fetchColumn();

// "Community Trust Score" - % of the user's own reports that reached matched/closed
$stmt = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$totalReports = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status IN ('matched','closed')");
$stmt->execute([$_SESSION['user_id']]);
$resolvedReports = (int)$stmt->fetchColumn();

$trustScore = $totalReports > 0 ? round(($resolvedReports / $totalReports) * 100) : 0;

// ---- Active listings (open or pending, not closed) ----
$stmt = $pdo->prepare(
    "SELECT report_id, type, item_name, status, location, category_id
     FROM reports
     WHERE user_id = ? AND status != 'closed'
     ORDER BY created_at DESC
     LIMIT 3"
);
$stmt->execute([$_SESSION['user_id']]);
$activeListings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle  = 'User Profile';
$activePage = 'profile';
require 'sidebar.php';
?>

<?php if ($error): ?>
  <div class="form-alert error" style="max-width:700px;"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="form-alert success" style="max-width:700px;"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="profile-banner">
  <div class="profile-avatar"><?php echo strtoupper(substr($me['full_name'], 0, 1)); ?></div>
  <div class="profile-banner-info">
    <h1><?php echo htmlspecialchars($me['full_name']); ?></h1>
    <span class="badge badge-member"><?php echo htmlspecialchars(ucfirst($me['user_type'])); ?> Member</span>
  </div>
  <div class="profile-banner-actions">
    <button type="button" class="btn btn-outline" disabled title="Coming soon">View Public Profile</button>
    <button type="submit" form="profileForm" class="btn btn-navy">Save Changes</button>
  </div>
</div>

<div class="form-grid">
  <div>
    <form id="profileForm" method="POST" action="profile.php" class="form-card">
      <h2>&#128100; Personal Information</h2>

      <div class="field-row">
        <div class="field">
          <label for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($me['full_name']); ?>">
        </div>
        <div class="field">
          <label for="email">University Email</label>
          <input type="text" id="email" value="<?php echo htmlspecialchars($me['email']); ?>" disabled>
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="phone">Phone Number</label>
          <input type="text" id="phone" name="phone" maxlength="10" value="<?php echo htmlspecialchars($me['phone']); ?>">
        </div>
        <div class="field">
          <label for="student_id">Student ID</label>
          <input type="text" id="student_id" value="Not collected" disabled>
        </div>
      </div>
    </form>

    <div class="form-card stats-card">
      <h2 style="color:#fff;">Contribution Stats</h2>
      <div class="stats-row">
        <span>Items Returned</span>
        <strong><?php echo $itemsReturned; ?></strong>
      </div>
      <div class="stats-row">
        <span>Successful Matches</span>
        <strong><?php echo $successfulMatches; ?></strong>
      </div>
      <div class="stats-row" style="border:none;padding-bottom:0;">
        <span>Community Trust Score</span>
        <strong><?php echo $trustScore; ?>%</strong>
      </div>
      <div class="trust-bar"><div class="trust-bar-fill" style="width:<?php echo $trustScore; ?>%;"></div></div>
    </div>
  </div>

  <div>
    <div class="form-card">
      <h2 style="display:flex;justify-content:space-between;align-items:center;">
        My Active Listings
        <a href="my_reports.php" style="font-size:12px;color:var(--indigo);">See All</a>
      </h2>

      <?php if (empty($activeListings)): ?>
        <p style="font-size:12.5px;color:var(--text-muted);">No active listings yet.</p>
      <?php else: ?>
        <?php foreach ($activeListings as $listing):
            $badgeClass = $listing['status'] === 'matched' ? 'badge-pending' : ($listing['type'] === 'lost' ? 'badge-lost' : 'badge-found');
            $badgeLabel = $listing['status'] === 'matched' ? 'Pending' : ucfirst($listing['type']);
        ?>
          <a href="report_details.php?id=<?php echo $listing['report_id']; ?>" class="listing-row">
            <span class="listing-icon"><?php require_once 'category_icon.php'; echo category_icon(''); ?></span>
            <span class="listing-info">
              <strong><?php echo htmlspecialchars($listing['item_name']); ?></strong>
              <small><?php echo ucfirst($listing['type']); ?> &bull; <?php echo htmlspecialchars($listing['location'] === 'Not specified' ? '' : $listing['location']); ?></small>
            </span>
            <span class="badge <?php echo $badgeClass; ?>" style="color:#fff;"><?php echo $badgeLabel; ?></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>

      <a href="report_lost.php" class="new-report-btn">+ New Report</a>
    </div>

    <div class="form-card" style="margin-top:16px;">
      <h2>&#9881; Account Settings</h2>
      <div class="settings-row">
        <span>&#128273; Change Password</span>
        <span style="color:var(--text-muted);">&rsaquo;</span>
      </div>
      <div class="settings-row">
        <span>&#128276; Notification Preferences</span>
        <span style="color:var(--text-muted);">&rsaquo;</span>
      </div>
      <p style="font-size:11px;color:var(--text-muted);margin-top:8px;">Not available yet.</p>
    </div>
  </div>
</div>
