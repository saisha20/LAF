<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$matchId = (int)($_GET['match_id'] ?? $_POST['match_id'] ?? 0);
$notice = '';

/**
 * Very simple, honest "match confidence" heuristic - NOT real AI.
 * Compares shared significant words between the two descriptions,
 * plus a bonus if both reports share the same category.
 */
function calc_match_confidence($descA, $descB, $sameCategory) {
    $stopwords = ['a','an','the','is','was','were','in','on','at','to','of','and','my','i','it','with','has','have','had','this','that','near'];
    $normalize = function ($text) use ($stopwords) {
        $text = strtolower($text);
        $words = preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_unique(array_diff($words, $stopwords));
    };
    $wordsA = $normalize($descA);
    $wordsB = $normalize($descB);
    $union = array_unique(array_merge($wordsA, $wordsB));
    $intersection = array_intersect($wordsA, $wordsB);

    $score = count($union) > 0 ? (count($intersection) / count($union)) * 100 : 0;
    if ($sameCategory) $score += 15;

    return (int) max(5, min(100, round($score)));
}

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 3600) return max(1, floor($diff / 60)) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    return date('M j, g:i A', strtotime($datetime));
}

// ---- Handle admin adding/updating evidence they collected ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_evidence'])) {
    $statement = trim($_POST['claim_statement'] ?? '');

    $photoData = null;
    $photoType = null;
    if (isset($_FILES['evidence_photo']) && $_FILES['evidence_photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $type = mime_content_type($_FILES['evidence_photo']['tmp_name']);
        if (in_array($type, $allowed, true) && $_FILES['evidence_photo']['size'] <= 5 * 1024 * 1024) {
            $photoData = file_get_contents($_FILES['evidence_photo']['tmp_name']);
            $photoType = $type;
        }
    }

    if ($photoData !== null) {
        $stmt = $pdo->prepare('UPDATE matches SET claim_statement = ?, claim_evidence_photo = ?, claim_evidence_type = ? WHERE match_id = ?');
        $stmt->execute([$statement, $photoData, $photoType, $matchId]);
    } else {
        $stmt = $pdo->prepare('UPDATE matches SET claim_statement = ? WHERE match_id = ?');
        $stmt->execute([$statement, $matchId]);
    }
    $notice = 'Evidence saved.';
}

// ---- Handle Approve ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve'])) {
    $stmt = $pdo->prepare('SELECT lost_report_id, found_report_id FROM matches WHERE match_id = ?');
    $stmt->execute([$matchId]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($m) {
        $stmt = $pdo->prepare('UPDATE matches SET status = "verified", verified_by = ?, verified_at = NOW() WHERE match_id = ?');
        $stmt->execute([$_SESSION['user_id'], $matchId]);

        $stmt = $pdo->prepare('UPDATE reports SET status = "matched" WHERE report_id IN (?, ?)');
        $stmt->execute([$m['lost_report_id'], $m['found_report_id']]);

        // Notify both owners
        $stmt = $pdo->prepare('SELECT report_id, user_id, item_name FROM reports WHERE report_id IN (?, ?)');
        $stmt->execute([$m['lost_report_id'], $m['found_report_id']]);
        $bothReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($bothReports as $r) {
            $otherReportId = ($r['report_id'] == $m['lost_report_id']) ? $m['found_report_id'] : $m['lost_report_id'];
            $stmt = $pdo->prepare(
                'INSERT INTO notifications (user_id, type, title, message, link_report_id)
                 VALUES (?, "match_approved", "Match Approved", ?, ?)'
            );
            $stmt->execute([
                $r['user_id'],
                "Your claim for '{$r['item_name']}' has been approved. Contact details are now unlocked.",
                $otherReportId,
            ]);
        }
    }
    header('Location: admin_matches.php?approved=1');
    exit;
}

// ---- Handle Reject ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject'])) {
    $stmt = $pdo->prepare('UPDATE matches SET status = "rejected" WHERE match_id = ?');
    $stmt->execute([$matchId]);
    header('Location: admin_matches.php?rejected=1');
    exit;
}

// ---- Load the match with both reports and both users ----
$stmt = $pdo->prepare(
    'SELECT m.*,
            rl.item_name AS lost_item, rl.description AS lost_desc, rl.category_id AS lost_cat,
            rf.item_name AS found_item, rf.description AS found_desc, rf.category_id AS found_cat,
            rf.location AS found_location, rf.date_reported AS found_date,
            (rf.photo_data IS NOT NULL) AS found_has_photo,
            ul.user_id AS owner_id, ul.full_name AS owner_name, ul.user_type AS owner_type, ul.phone AS owner_phone,
            uf.user_id AS finder_id, uf.full_name AS finder_name, uf.user_type AS finder_type, uf.phone AS finder_phone
     FROM matches m
     JOIN reports rl ON rl.report_id = m.lost_report_id
     JOIN reports rf ON rf.report_id = m.found_report_id
     JOIN users ul ON ul.user_id = rl.user_id
     JOIN users uf ON uf.user_id = rf.user_id
     WHERE m.match_id = ?'
);
$stmt->execute([$matchId]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle  = 'Claim Verification';
$activeAdminPage = 'matches';
require 'admin_header.php';

if (!$match) {
    echo '<h1>Claim not found</h1><p class="page-sub">This match may have been removed.</p><a href="admin_matches.php" class="btn btn-navy">Back to Verify Matches</a>';
    require 'admin_footer.php';
    exit;
}

$confidence = calc_match_confidence($match['lost_desc'], $match['found_desc'], $match['lost_cat'] === $match['found_cat']);
?>

<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
  <div>
    <h1>Claim Verification: #CLM-<?php echo str_pad($matchId, 4, '0', STR_PAD_LEFT); ?></h1>
    <p class="page-sub">&#9989; Match Intelligence Scoring: <?php echo $confidence; ?>% confidence <span style="color:var(--text-muted);">(based on shared description keywords and category match - not real AI)</span></p>
  </div>
  <div style="text-align:right;">
    <span class="status-pill status-<?php echo $match['status'] === 'pending' ? 'pending' : ($match['status'] === 'verified' ? 'matched' : 'closed'); ?>">
      <?php echo $match['status'] === 'pending' ? 'Pending Review' : ucfirst($match['status']); ?>
    </span><br>
    <small style="color:var(--text-muted);font-size:11px;">Initiated <?php echo time_ago($match['created_at']); ?></small>
  </div>
</div>

<?php if ($notice): ?>
  <div class="form-alert success" style="max-width:700px;"><?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>

<div class="claim-grid">

  <div class="claim-main">

    <div class="form-card">
      <h2>Item Overview</h2>
      <div style="display:flex;gap:16px;">
        <?php if ($match['found_has_photo']): ?>
          <img src="serve_photo.php?id=<?php echo $match['found_report_id']; ?>" style="width:110px;height:110px;object-fit:cover;border-radius:10px;flex-shrink:0;">
        <?php else: ?>
          <div style="width:110px;height:110px;background:#eef0f6;border-radius:10px;flex-shrink:0;"></div>
        <?php endif; ?>
        <div>
          <div class="claim-label">Item Name</div>
          <h3 style="font-size:15px;margin-bottom:8px;"><?php echo htmlspecialchars($match['found_item']); ?></h3>
          <div class="claim-label">Description</div>
          <p style="font-size:13px;color:var(--text-dark);margin-bottom:10px;"><?php echo htmlspecialchars($match['found_desc']); ?></p>
          <span class="tag">Location: <?php echo htmlspecialchars($match['found_location']); ?></span>
          <span class="tag">Found Date: <?php echo date('M j, Y', strtotime($match['found_date'])); ?></span>
        </div>
      </div>
    </div>

    <div class="form-card">
      <h2 style="display:flex;justify-content:space-between;align-items:center;">
        Evidence Submitted
        <span class="badge badge-member">Owner's Claim</span>
      </h2>

      <div class="evidence-grid">
        <div>
          <div class="claim-label">Statement of Ownership</div>
          <div class="evidence-box">
            <?php echo $match['claim_statement'] ? nl2br(htmlspecialchars($match['claim_statement'])) : '<span style="color:var(--text-muted);">No statement submitted yet.</span>'; ?>
          </div>
        </div>
        <div>
          <div class="claim-label">Proof of Purchase / Serial Number</div>
          <?php if ($match['claim_evidence_photo']): ?>
            <img src="serve_evidence_photo.php?match_id=<?php echo $matchId; ?>" style="width:100%;height:140px;object-fit:cover;border-radius:8px;">
          <?php else: ?>
            <div class="evidence-box" style="height:140px;display:flex;align-items:center;justify-content:center;">
              <span style="color:var(--text-muted);">No evidence photo uploaded.</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" style="margin-top:16px;border-top:1px solid var(--border-gray);padding-top:14px;">
        <input type="hidden" name="match_id" value="<?php echo $matchId; ?>">
        <div class="field">
          <label>Add / update statement (collected from claimant)</label>
          <textarea name="claim_statement" rows="3"><?php echo htmlspecialchars($match['claim_statement'] ?? ''); ?></textarea>
        </div>
        <div class="field">
          <label>Attach evidence photo</label>
          <input type="file" name="evidence_photo" accept="image/jpeg,image/png,image/webp">
        </div>
        <button type="submit" name="save_evidence" value="1" class="btn btn-outline">Save Evidence</button>
      </form>
    </div>

  </div>

  <div class="claim-side">

    <div class="form-card">
      <h2>&#128100; Finder</h2>
      <strong style="display:block;font-size:14px;margin-bottom:2px;"><?php echo htmlspecialchars($match['finder_name']); ?></strong>
      <span style="font-size:12px;color:var(--text-muted);"><?php echo ucfirst($match['finder_type']); ?></span>
      <div class="admin-phone-row">
        <span>&#128274; Phone</span>
        <strong><?php echo htmlspecialchars($match['finder_phone']); ?></strong>
      </div>
      <small style="color:var(--text-muted);">Visible to admin only until approved.</small>
    </div>

    <div class="form-card">
      <h2>&#128100; Owner</h2>
      <strong style="display:block;font-size:14px;margin-bottom:2px;"><?php echo htmlspecialchars($match['owner_name']); ?></strong>
      <span style="font-size:12px;color:var(--text-muted);"><?php echo ucfirst($match['owner_type']); ?></span>
      <div class="admin-phone-row">
        <span>&#128274; Phone</span>
        <strong><?php echo htmlspecialchars($match['owner_phone']); ?></strong>
      </div>
      <small style="color:var(--text-muted);">Visible to admin only until approved.</small>
    </div>

    <div class="notice-card" style="background:#eef0f6;color:var(--text-dark);">
      <strong>&#8505; Privacy Restriction Policy</strong>
      Contact details are restricted to prevent unauthorized harassment. Approving this match will securely reveal both phone numbers to each other.
    </div>

    <?php if ($match['status'] === 'pending'): ?>
      <form method="POST">
        <input type="hidden" name="match_id" value="<?php echo $matchId; ?>">
        <button type="submit" name="approve" value="1" class="btn btn-navy btn-block" style="margin-top:14px;" onclick="return confirm('Approve this match? Contact details will be unlocked for both parties.');">&#9989; Approve Match</button>
      </form>
      <form method="POST">
        <input type="hidden" name="match_id" value="<?php echo $matchId; ?>">
        <button type="submit" name="reject" value="1" class="btn-danger btn-block" style="margin-top:10px;padding:12px;border-radius:8px;" onclick="return confirm('Reject this match?');">&#10060; Reject Match</button>
      </form>
    <?php else: ?>
      <p class="form-card" style="margin-top:14px;font-size:13px;color:var(--text-muted);">This match has already been <?php echo htmlspecialchars($match['status']); ?>.</p>
    <?php endif; ?>

  </div>

</div>

<?php require 'admin_footer.php'; ?>
