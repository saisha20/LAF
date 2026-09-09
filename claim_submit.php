<?php
require_once 'auth.php';
require_once 'db.php';
requireLogin();

$matchId = (int)($_GET['match_id'] ?? $_POST['match_id'] ?? 0);
$error = '';
$success = '';

// Load the match, but only if it's pending AND the logged-in user
// owns the LOST report side (they're the one claiming ownership).
$stmt = $pdo->prepare(
    'SELECT m.*, rl.item_name AS lost_item, rl.user_id AS owner_id,
            rf.item_name AS found_item, rf.description AS found_desc, rf.location AS found_location
     FROM matches m
     JOIN reports rl ON rl.report_id = m.lost_report_id
     JOIN reports rf ON rf.report_id = m.found_report_id
     WHERE m.match_id = ?'
);
$stmt->execute([$matchId]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

$notOwner = !$match || (int)$match['owner_id'] !== (int)$_SESSION['user_id'];
$notPending = $match && $match['status'] !== 'pending';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$notOwner && !$notPending) {
    $statement = trim($_POST['claim_statement'] ?? '');

    if ($statement === '') {
        $error = 'Please describe why you believe this is your item.';
    } else {
        $photoData = null;
        $photoType = null;
        if (isset($_FILES['evidence_photo']) && $_FILES['evidence_photo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $type = mime_content_type($_FILES['evidence_photo']['tmp_name']);
            if (!in_array($type, $allowed, true)) {
                $error = 'Evidence photo must be a JPG, PNG, or WEBP image.';
            } elseif ($_FILES['evidence_photo']['size'] > 5 * 1024 * 1024) {
                $error = 'Evidence photo must be under 5MB.';
            } else {
                $photoData = file_get_contents($_FILES['evidence_photo']['tmp_name']);
                $photoType = $type;
            }
        }

        if ($error === '') {
            if ($photoData !== null) {
                $stmt = $pdo->prepare('UPDATE matches SET claim_statement = ?, claim_evidence_photo = ?, claim_evidence_type = ? WHERE match_id = ?');
                $stmt->execute([$statement, $photoData, $photoType, $matchId]);
            } else {
                $stmt = $pdo->prepare('UPDATE matches SET claim_statement = ? WHERE match_id = ?');
                $stmt->execute([$statement, $matchId]);
            }
            $success = 'Your claim has been submitted. An admin will review it shortly.';

            // Refresh $match so the page shows what was just saved
            $stmt = $pdo->prepare('SELECT * FROM matches WHERE match_id = ?');
            $stmt->execute([$matchId]);
            $fresh = $stmt->fetch(PDO::FETCH_ASSOC);
            $match['claim_statement'] = $fresh['claim_statement'];
            $match['claim_evidence_photo'] = $fresh['claim_evidence_photo'];
        }
    }
}

$pageTitle  = 'Submit Your Claim';
$activePage = 'reports';
require 'sidebar.php';
?>

<a href="my_reports.php" style="font-size:13px;color:var(--text-muted);display:inline-block;margin-bottom:14px;">&larr; Back to My Reports</a>

<?php if (!$match): ?>

  <h1>Claim not found</h1>
  <p class="page-sub">This match doesn't exist or may have been removed.</p>

<?php elseif ($notOwner): ?>

  <h1>Not your claim</h1>
  <p class="page-sub">Only the person who reported this item as lost can submit ownership evidence for it.</p>

<?php elseif ($notPending): ?>

  <h1>Already <?php echo htmlspecialchars($match['status']); ?></h1>
  <p class="page-sub">This match has already been <?php echo htmlspecialchars($match['status']); ?> by an admin, so it's no longer open for new evidence.</p>
  <a href="report_details.php?id=<?php echo $match['lost_report_id']; ?>" class="btn btn-navy">View Item Details</a>

<?php else: ?>

  <h1>Submit Your Claim</h1>
  <p class="page-sub">An admin found a potential match between your lost report and a found item. Help them confirm it's really yours.</p>

  <?php if ($error): ?>
    <div class="form-alert error" style="max-width:700px;"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="form-alert success" style="max-width:700px;"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <div class="form-card" style="max-width:700px;margin-bottom:16px;">
    <h2>The item an admin found</h2>
    <strong style="display:block;font-size:14px;margin-bottom:4px;"><?php echo htmlspecialchars($match['found_item']); ?></strong>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:6px;"><?php echo htmlspecialchars($match['found_desc']); ?></p>
    <span class="tag">Found at: <?php echo htmlspecialchars($match['found_location']); ?></span>
  </div>

  <form method="POST" enctype="multipart/form-data" class="form-card" style="max-width:700px;">
    <input type="hidden" name="match_id" value="<?php echo $matchId; ?>">

    <div class="field">
      <label for="claim_statement">Statement of Ownership</label>
      <textarea id="claim_statement" name="claim_statement" rows="5" placeholder="Describe unique details only the real owner would know - marks, contents, where/when you lost it..."><?php echo htmlspecialchars($match['claim_statement'] ?? ''); ?></textarea>
    </div>

    <div class="field">
      <label for="evidence_photo">Proof of purchase or serial number photo (optional)</label>
      <input type="file" id="evidence_photo" name="evidence_photo" accept="image/jpeg,image/png,image/webp">
      <?php if (!empty($match['claim_evidence_photo'])): ?>
        <div class="field-hint">You already have a photo on file - uploading a new one will replace it.</div>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-navy btn-block">Submit Claim</button>
  </form>

<?php endif; ?>

<?php require 'dashboard_footer.php'; ?>
