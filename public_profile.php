<?php
/**
 * public_profile.php
 *
 * A read-only page showing what other students see about a user:
 * their name, member type, contribution stats, and their PUBLIC
 * listings only. Never shows email or phone - contact details are
 * only shared once an admin verifies a match (see contact_info.php).
 */
require_once 'auth.php';
require_once 'db.php';
require_once 'category_icon.php';

$viewedId = (int) ($_GET['id'] ?? 0);
$viewingOwn = $viewedId === (int) ($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare('SELECT user_id, full_name, user_type, created_at FROM users WHERE user_id = ?');
$stmt->execute([$viewedId]);
$person = $stmt->fetch(PDO::FETCH_ASSOC);

if ($person) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'closed'");
    $stmt->execute([$viewedId]);
    $itemsReturned = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT m.match_id)
         FROM matches m
         JOIN reports rl ON rl.report_id = m.lost_report_id
         JOIN reports rf ON rf.report_id = m.found_report_id
         WHERE (rl.user_id = ? OR rf.user_id = ?) AND m.status = 'verified'"
    );
    $stmt->execute([$viewedId, $viewedId]);
    $successfulMatches = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE user_id = ?');
    $stmt->execute([$viewedId]);
    $totalReports = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status IN ('matched','closed')");
    $stmt->execute([$viewedId]);
    $resolvedReports = (int) $stmt->fetchColumn();

    $trustScore = $totalReports > 0 ? round(($resolvedReports / $totalReports) * 100) : 0;

    // Only PUBLIC, still-open listings - never anything the owner marked private.
    $stmt = $pdo->prepare(
        "SELECT report_id, type, item_name, location, status
         FROM reports
         WHERE user_id = ? AND is_public = 1 AND status != 'closed'
         ORDER BY created_at DESC
         LIMIT 6"
    );
    $stmt->execute([$viewedId]);
    $publicListings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle  = $person ? htmlspecialchars($person['full_name']) . ' - Public Profile' : 'Profile Not Found';
$activePage = 'profile';
require 'sidebar.php';
?>

<?php if ($viewingOwn): ?>
  <div class="form-alert success" style="max-width:700px;">
    This is how other students see your profile. Contact details are never shown here - they're only shared after an admin verifies a match.
  </div>
<?php endif; ?>

<?php if (!$person): ?>

  <h1>Profile Not Found</h1>
  <p class="page-sub">This user doesn't exist, or their account has been removed.</p>
  <a href="user_dashboard.php" class="btn btn-navy">Back to Dashboard</a>

<?php else: ?>

  <div class="profile-banner">
    <div class="profile-avatar"><?php echo strtoupper(substr($person['full_name'], 0, 1)); ?></div>
    <div class="profile-banner-info">
      <h1><?php echo htmlspecialchars($person['full_name']); ?></h1>
      <span class="badge badge-member"><?php echo htmlspecialchars(ucfirst($person['user_type'])); ?> Member</span>
      <div style="font-size:12px;color:var(--text-muted);margin-top:6px;">
        Member since <?php echo date('F Y', strtotime($person['created_at'])); ?>
      </div>
    </div>
    <?php if ($viewingOwn): ?>
      <div class="profile-banner-actions">
        <a href="profile.php" class="btn btn-outline">&larr; Back to My Profile</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="form-grid" style="margin-top:22px;">
    <div class="form-card stats-card" style="max-width:340px;">
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

    <div class="form-card">
      <h2>Public Listings</h2>
      <?php if (empty($publicListings)): ?>
        <p style="color:var(--text-muted);font-size:12.5px;">No public listings right now.</p>
      <?php else: ?>
        <?php foreach ($publicListings as $item): ?>
          <div class="listing-row">
            <span class="listing-icon"><?php echo category_icon(''); ?></span>
            <div class="listing-info">
              <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
              <small><?php echo ucfirst($item['type']); ?> &bull; <?php echo htmlspecialchars($item['location'] ?: 'Location not specified'); ?></small>
            </div>
            <span class="badge <?php echo $item['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>"><?php echo ucfirst($item['type']); ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>
