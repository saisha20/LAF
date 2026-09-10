<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'category_icon.php';
requireLogin();

$reportId = (int)($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];
$claimError = '';

// ---- Handle "Claim this item" submission ----
// A logged-in user can propose that one of THEIR OWN open lost reports
// matches this found report, without waiting for an admin to do it first.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_lost_report_id'])) {
    $lostReportId = (int)$_POST['claim_lost_report_id'];

    // Confirm that lost report really belongs to the logged-in user
    $stmt = $pdo->prepare('SELECT report_id FROM reports WHERE report_id = ? AND user_id = ? AND type = "lost"');
    $stmt->execute([$lostReportId, $uid]);

    if (!$stmt->fetch()) {
        $claimError = 'Please select one of your own lost reports.';
    } else {
        // Reuse an existing match if one already exists between this pair
        $stmt = $pdo->prepare('SELECT match_id FROM matches WHERE lost_report_id = ? AND found_report_id = ? AND status != "rejected"');
        $stmt->execute([$lostReportId, $reportId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $matchId = $existing['match_id'];
        } else {
            $stmt = $pdo->prepare('INSERT INTO matches (lost_report_id, found_report_id, status) VALUES (?, ?, "pending")');
            $stmt->execute([$lostReportId, $reportId]);
            $matchId = $pdo->lastInsertId();

            // Let the finder know someone believes this is their item
            $stmt = $pdo->prepare('SELECT user_id, item_name FROM reports WHERE report_id = ?');
            $stmt->execute([$reportId]);
            $foundReport = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($foundReport) {
                $pdo->prepare(
                    'INSERT INTO notifications (user_id, type, title, message, link_report_id)
                     VALUES (?, "claim_review", "Someone Claimed Your Found Item", ?, ?)'
                )->execute([
                    $foundReport['user_id'],
                    "Someone believes the \"{$foundReport['item_name']}\" you found is theirs. An admin will review their evidence shortly.",
                    $lostReportId,
                ]);
            }
        }

        header('Location: claim_submit.php?match_id=' . $matchId);
        exit;
    }
}

$stmt = $pdo->prepare(
    'SELECT r.report_id, r.user_id, r.type, r.item_name, r.category_id, r.description,
            r.color, r.brand, r.reward_amount, r.condition_status, r.notes, r.location,
            r.date_reported, r.status, r.is_public, r.photo_data, r.photo_type, r.created_at,
            c.category_name, u.full_name AS reporter_name
     FROM reports r
     LEFT JOIN categories c ON c.category_id = r.category_id
     LEFT JOIN users u ON u.user_id = r.user_id
     WHERE r.report_id = ?'
);
$stmt->execute([$reportId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle  = 'Item Details';
$activePage = 'search';
require 'sidebar.php';

if (!$item) {
    echo '<h1>Item not found</h1><p class="page-sub">This report may have been removed.</p>';
    echo '<a href="search_items.php" class="btn btn-navy">Back to Search</a>';
    require 'dashboard_footer.php';
    exit;
}

// ---- Defensive defaults so a missing/odd column never throws a warning ----
$type            = $item['type'] ?? 'lost';
$status          = $item['status'] ?? 'open';
$itemName        = $item['item_name'] ?? 'Untitled item';
$description     = $item['description'] ?? '';
$categoryName    = $item['category_name'] ?? 'Uncategorized';
$dateReported    = $item['date_reported'] ?? null;
$color           = $item['color'] ?? '';
$brand           = $item['brand'] ?? '';
$rewardAmount    = $item['reward_amount'] ?? null;
$location        = $item['location'] ?? '';
$conditionStatus = $item['condition_status'] ?? '';
$notes           = $item['notes'] ?? '';
$photoData       = $item['photo_data'] ?? null;
$reporterName    = $item['reporter_name'] ?? 'Someone';
$ownerUserId     = $item['user_id'] ?? null;

$badgeLabel = $status === 'matched' ? 'Pending Verification' : ucfirst($type);
$isOwnReport = $ownerUserId !== null && (int)$ownerUserId === (int)$uid;

// If this is a found report and it's not the viewer's own, offer their
// open lost reports as candidates to claim against.
$myLostReports = [];
if ($type === 'found' && !$isOwnReport) {
    $stmt = $pdo->prepare("SELECT report_id, item_name FROM reports WHERE user_id = ? AND type = 'lost' AND status = 'open' ORDER BY created_at DESC");
    $stmt->execute([$uid]);
    $myLostReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<a href="search_items.php" style="font-size:13px;color:var(--text-muted);display:inline-block;margin-bottom:14px;">&larr; Back to Search</a>

<div class="form-grid">
  <div class="form-card">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
      <span class="badge <?php echo $type === 'lost' ? 'badge-lost' : 'badge-found'; ?>"><?php echo htmlspecialchars($badgeLabel); ?></span>
      <h1 style="font-size:20px;margin:0;"><?php echo htmlspecialchars($itemName); ?></h1>
    </div>

    <?php if ($photoData): ?>
      <img src="serve_photo.php?id=<?php echo $reportId; ?>" alt="" style="width:100%;max-height:280px;object-fit:cover;border-radius:10px;margin-bottom:18px;">
    <?php else: ?>
      <div style="height:180px;background:#eef0f6;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:40px;margin-bottom:18px;">
        <?php echo category_icon($categoryName); ?>
      </div>
    <?php endif; ?>

    <h2 style="font-size:14px;">Description</h2>
    <p style="font-size:13.5px;color:var(--text-dark);margin-bottom:18px;"><?php echo nl2br(htmlspecialchars($description)); ?></p>

    <div class="field-row">
      <div>
        <label style="font-size:12px;color:var(--text-muted);">Category</label>
        <p style="font-size:13.5px;"><?php echo htmlspecialchars($categoryName); ?></p>
      </div>
      <div>
        <label style="font-size:12px;color:var(--text-muted);">Date</label>
        <p style="font-size:13.5px;"><?php echo $dateReported ? date('M j, Y', strtotime($dateReported)) : 'Not specified'; ?></p>
      </div>
    </div>

    <?php if ($type === 'lost'): ?>
      <div class="field-row" style="margin-top:14px;">
        <?php if ($color): ?><div><label style="font-size:12px;color:var(--text-muted);">Color</label><p style="font-size:13.5px;"><?php echo htmlspecialchars($color); ?></p></div><?php endif; ?>
        <?php if ($brand): ?><div><label style="font-size:12px;color:var(--text-muted);">Brand</label><p style="font-size:13.5px;"><?php echo htmlspecialchars($brand); ?></p></div><?php endif; ?>
      </div>
      <?php if ($rewardAmount): ?>
        <p style="font-size:13px;margin-top:10px;"><strong>Reward:</strong> $<?php echo number_format($rewardAmount, 2); ?></p>
      <?php endif; ?>
    <?php else: ?>
      <div class="field-row" style="margin-top:14px;">
        <div><label style="font-size:12px;color:var(--text-muted);">Found Location</label><p style="font-size:13.5px;"><?php echo htmlspecialchars($location); ?></p></div>
        <?php if ($conditionStatus): ?><div><label style="font-size:12px;color:var(--text-muted);">Condition</label><p style="font-size:13.5px;"><?php echo htmlspecialchars(ucfirst($conditionStatus)); ?></p></div><?php endif; ?>
      </div>
      <?php if ($notes): ?>
        <p style="font-size:13px;margin-top:10px;"><strong>Notes:</strong> <?php echo htmlspecialchars($notes); ?></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div>
    <div class="notice-card green">
      <strong>&#128274; Contact Protected</strong>
      This item was reported by <?php echo htmlspecialchars(explode(' ', $reporterName)[0]); ?>. Contact details are only shared with the verified counterpart after an admin confirms a match between a lost and found report.
    </div>

    <?php if ($isOwnReport): ?>
      <div class="form-card" style="margin-top:16px;">
        <p style="font-size:13px;color:var(--text-muted);">This is your own report. Manage it from <a href="my_reports.php">My Reports</a>.</p>
      </div>

    <?php elseif ($type === 'found'): ?>
      <div class="form-card" style="margin-top:16px;">
        <h2 style="font-size:14px;">Is this yours?</h2>

        <?php if ($claimError): ?>
          <div class="form-alert error"><?php echo htmlspecialchars($claimError); ?></div>
        <?php endif; ?>

        <?php if (empty($myLostReports)): ?>
          <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:10px;">
            You don't have a matching lost report yet. Report the item as lost first, then come back here to claim it.
          </p>
          <a href="report_lost.php" class="btn btn-navy btn-block">Report a Lost Item</a>
        <?php else: ?>
          <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:10px;">
            If you believe this is something you lost, select which of your reports it matches and submit ownership evidence for an admin to review.
          </p>
          <form method="POST" action="report_details.php?id=<?php echo $reportId; ?>">
            <div class="field">
              <label for="claim_lost_report_id">Which of your lost reports is this?</label>
              <select id="claim_lost_report_id" name="claim_lost_report_id">
                <?php foreach ($myLostReports as $lr): ?>
                  <option value="<?php echo $lr['report_id']; ?>"><?php echo htmlspecialchars($lr['item_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-navy btn-block">Claim This Item</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
