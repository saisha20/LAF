<?php
$pageTitle = 'Verify Matches';
$activeAdminPage = 'matches';
require 'admin_header.php';
require_once 'match_helper.php';

$error = '';
$success = '';

// ---- Propose a new match ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_match'])) {
    $lostId  = (int)($_POST['lost_report_id'] ?? 0);
    $foundId = (int)($_POST['found_report_id'] ?? 0);

    if (!$lostId || !$foundId) {
        $error = 'Select both a lost report and a found report.';
    } else {
        $stmt = $pdo->prepare('SELECT match_id FROM matches WHERE lost_report_id = ? AND found_report_id = ? AND status != "rejected"');
        $stmt->execute([$lostId, $foundId]);
        if ($stmt->fetch()) {
            $error = 'A match between these two reports already exists.';
        } else {
            create_match_with_notification($pdo, $lostId, $foundId);
            $success = 'Match proposed. Compare the descriptions below and verify or reject it.';
        }
    }
}

// ---- Verify a match ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_match_id'])) {
    $matchId = (int)$_POST['verify_match_id'];

    $stmt = $pdo->prepare('SELECT lost_report_id, found_report_id, status FROM matches WHERE match_id = ?');
    $stmt->execute([$matchId]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($m && $m['status'] !== 'verified') {
        $pdo->prepare('UPDATE matches SET status = "verified", verified_by = ?, verified_at = NOW() WHERE match_id = ?')
            ->execute([$_SESSION['user_id'], $matchId]);
        $pdo->prepare('UPDATE reports SET status = "matched" WHERE report_id IN (?, ?)')
            ->execute([$m['lost_report_id'], $m['found_report_id']]);

        notify_match_verified($pdo, $m['lost_report_id'], $m['found_report_id']);

        $success = 'Match verified. Both users can now see each other\'s contact details.';
    }
}

// ---- Reject a match ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_match_id'])) {
    $matchId = (int)$_POST['reject_match_id'];

    $stmt = $pdo->prepare('SELECT lost_report_id, found_report_id, status FROM matches WHERE match_id = ?');
    $stmt->execute([$matchId]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($m && $m['status'] !== 'rejected') {
        $pdo->prepare('UPDATE matches SET status = "rejected" WHERE match_id = ?')->execute([$matchId]);
        notify_match_rejected($pdo, $m['lost_report_id'], $m['found_report_id']);
        $success = 'Match rejected. Both reports remain open.';
    }
}

// ---- Data for the page ----
$lostOptions = $pdo->query("SELECT report_id, item_name FROM reports WHERE type = 'lost' AND status = 'open' ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);
$foundOptions = $pdo->query("SELECT report_id, item_name FROM reports WHERE type = 'found' AND status = 'open' ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);

$pending = $pdo->query(
    "SELECT m.match_id, m.created_at,
            rl.item_name AS lost_item, rl.description AS lost_desc, rl.color, rl.brand, ul.full_name AS lost_user, ul.phone AS lost_phone,
            rf.item_name AS found_item, rf.description AS found_desc, rf.location, uf.full_name AS found_user, uf.phone AS found_phone
     FROM matches m
     JOIN reports rl ON rl.report_id = m.lost_report_id
     JOIN reports rf ON rf.report_id = m.found_report_id
     JOIN users ul ON ul.user_id = rl.user_id
     JOIN users uf ON uf.user_id = rf.user_id
     WHERE m.status = 'pending'
       AND m.claim_statement IS NOT NULL
       AND m.claim_statement != ''
     ORDER BY m.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$verified = $pdo->query(
    "SELECT m.match_id, m.verified_at,
            rl.item_name AS lost_item, rf.item_name AS found_item
     FROM matches m
     JOIN reports rl ON rl.report_id = m.lost_report_id
     JOIN reports rf ON rf.report_id = m.found_report_id
     WHERE m.status = 'verified'
     ORDER BY m.verified_at DESC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Verify Matches</h1>
<p class="page-sub">Propose a lost/found pair, compare their descriptions, then verify a genuine match or reject a false one. Verifying unlocks contact details between the two students.</p>

<?php if ($error): ?><div class="form-alert error" style="max-width:700px;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="form-alert success" style="max-width:700px;"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="form-card">
  <h2>Propose a Match</h2>
  <form method="POST" action="admin_matches.php">
    <div class="field-row">
      <div class="field">
        <label for="lost_report_id">Lost Report</label>
        <select id="lost_report_id" name="lost_report_id">
          <option value="">Select a lost report</option>
          <?php foreach ($lostOptions as $l): ?>
            <option value="<?php echo $l['report_id']; ?>"><?php echo htmlspecialchars($l['item_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="found_report_id">Found Report</label>
        <select id="found_report_id" name="found_report_id">
          <option value="">Select a found report</option>
          <?php foreach ($foundOptions as $f): ?>
            <option value="<?php echo $f['report_id']; ?>"><?php echo htmlspecialchars($f['item_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button type="submit" name="create_match" value="1" class="btn btn-navy">Propose Match</button>
  </form>
</div>

<div class="section-heading"><span>Pending Claims (<?php echo count($pending); ?>)</span></div>

<?php if (empty($pending)): ?>
  <p style="color:var(--text-muted);font-size:13px;">No pending claims. Propose one above once you spot a likely lost/found pair.</p>
<?php else: ?>
  <?php foreach ($pending as $p): ?>
    <div class="match-compare">
      <div class="match-compare-grid">
        <div class="match-side">
          <span class="badge badge-lost">Lost</span>
          <h4><?php echo htmlspecialchars($p['lost_item']); ?></h4>
          <p><?php echo htmlspecialchars($p['lost_desc']); ?></p>
          <p><?php echo htmlspecialchars(trim(($p['brand'] ?? '') . ' ' . ($p['color'] ?? ''))); ?></p>
          <p style="color:var(--text-dark);">Reported by <?php echo htmlspecialchars($p['lost_user']); ?></p>
        </div>
        <div class="match-vs">VS</div>
        <div class="match-side">
          <span class="badge badge-found">Found</span>
          <h4><?php echo htmlspecialchars($p['found_item']); ?></h4>
          <p><?php echo htmlspecialchars($p['found_desc']); ?></p>
          <p><?php echo htmlspecialchars($p['location']); ?></p>
          <p style="color:var(--text-dark);">Reported by <?php echo htmlspecialchars($p['found_user']); ?></p>
        </div>
      </div>
      <div class="match-actions">
        <a href="admin_claim_review.php?match_id=<?php echo $p['match_id']; ?>" class="btn btn-outline">Full Review</a>
        <form method="POST" action="admin_matches.php" onsubmit="return confirm('Verify this match? Contact details will be unlocked for both users.');">
          <input type="hidden" name="verify_match_id" value="<?php echo $p['match_id']; ?>">
          <button type="submit" class="btn btn-navy">&#9989; Verify Match</button>
        </form>
        <form method="POST" action="admin_matches.php" onsubmit="return confirm('Reject this match?');">
          <input type="hidden" name="reject_match_id" value="<?php echo $p['match_id']; ?>">
          <button type="submit" class="btn btn-danger">Reject</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<div class="section-heading"><span>Recently Verified</span></div>
<?php if (empty($verified)): ?>
  <p style="color:var(--text-muted);font-size:13px;">No verified matches yet.</p>
<?php else: ?>
  <div class="form-card" style="padding:0;overflow:hidden;">
    <table class="admin-table">
      <thead><tr><th>Lost Item</th><th>Found Item</th><th>Verified</th></tr></thead>
      <tbody>
        <?php foreach ($verified as $v): ?>
          <tr>
            <td><?php echo htmlspecialchars($v['lost_item']); ?></td>
            <td><?php echo htmlspecialchars($v['found_item']); ?></td>
            <td><?php echo date('M j, Y', strtotime($v['verified_at'])); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
