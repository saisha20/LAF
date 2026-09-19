<?php
require_once 'auth.php';
require_once 'db.php';
requireLogin();

$reportId = (int)($_GET['report_id'] ?? 0);
$uid = $_SESSION['user_id'];

// Find the most recent VERIFIED match involving this report - contact
// details only ever come from a verified match, never a pending one.
$stmt = $pdo->prepare(
    "SELECT m.match_id, m.verified_at,
            rl.report_id AS lost_report_id, rl.item_name AS lost_item, rl.user_id AS lost_user_id,
            rf.report_id AS found_report_id, rf.item_name AS found_item, rf.user_id AS found_user_id,
            ul.full_name AS owner_name, ul.phone AS owner_phone,
            uf.full_name AS finder_name, uf.phone AS finder_phone
     FROM matches m
     JOIN reports rl ON rl.report_id = m.lost_report_id
     JOIN reports rf ON rf.report_id = m.found_report_id
     JOIN users ul ON ul.user_id = rl.user_id
     JOIN users uf ON uf.user_id = rf.user_id
     WHERE (m.lost_report_id = ? OR m.found_report_id = ?) AND m.status = 'verified'
     ORDER BY m.verified_at DESC
     LIMIT 1"
);
$stmt->execute([$reportId, $reportId]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

$isAdminViewer = isAdmin();
$isParty = $match && ((int)$uid === (int)$match['lost_user_id'] || (int)$uid === (int)$match['found_user_id']);

$pageTitle  = 'Contact Information';
$activePage = 'notifications';
require 'sidebar.php';
?>

<link rel="stylesheet" href="contact_info.css">

<?php if (!$match): ?>

  <h1>No Verified Match</h1>
  <p class="page-sub">Contact details are only shown once an admin has verified a match between a lost and found report. This report doesn't have a verified match yet.</p>
  <a href="notifications.php" class="btn btn-navy">Back to Notifications</a>

<?php elseif (!$isParty && !$isAdminViewer): ?>

  <h1>Not Authorized</h1>
  <p class="page-sub">You aren't one of the two people involved in this match.</p>
  <a href="user_dashboard.php" class="btn btn-navy">Back to Dashboard</a>

<?php else: ?>

  <div class="contact-card-wrap">
    <div class="contact-check-icon">&#10003;</div>
    <h1 style="text-align:center;">Match Verified!</h1>
    <p class="page-sub" style="text-align:center;">Contact details have been securely shared between parties to facilitate the item return.</p>

    <div class="contact-cards-row">
      <div class="contact-card">
        <div class="contact-card-label">&#128100; PROPERTY OWNER</div>
        <div class="contact-sub">Owner's Phone</div>
        <div class="contact-phone"><?php echo htmlspecialchars($match['owner_phone']); ?></div>
        <div class="contact-actions">
          <a href="tel:<?php echo htmlspecialchars($match['owner_phone']); ?>" class="btn btn-outline">&#128222; Call</a>
          <button type="button" class="btn btn-outline" onclick="copyPhone(this, '<?php echo htmlspecialchars($match['owner_phone']); ?>')">&#128203; Copy</button>
        </div>
      </div>
      <div class="contact-card">
        <div class="contact-card-label">&#128269; ITEM FINDER</div>
        <div class="contact-sub">Finder's Phone</div>
        <div class="contact-phone"><?php echo htmlspecialchars($match['finder_phone']); ?></div>
        <div class="contact-actions">
          <a href="tel:<?php echo htmlspecialchars($match['finder_phone']); ?>" class="btn btn-outline">&#128222; Call</a>
          <button type="button" class="btn btn-outline" onclick="copyPhone(this, '<?php echo htmlspecialchars($match['finder_phone']); ?>')">&#128203; Copy</button>
        </div>
      </div>
    </div>

    <div class="sms-note">
      &#128276; Both parties have been notified via the app's Notifications page.
      <span class="verified-admin-badge">&#128274; Verified Admin Action</span>
    </div>

    <div class="print-actions">
      <button type="button" class="btn btn-navy" onclick="window.print()">&#128424; Print Receipt</button>
      <a href="notifications.php" class="btn btn-outline">&larr; Back</a>
    </div>

    <div class="info-tiles-row">
      <div class="info-tile">
        <strong>&#128274; Secure Transfer</strong>
        <p>Data is only shared with the two verified members of this match.</p>
      </div>
      <div class="info-tile">
        <strong>&#128203; Audit Logged</strong>
        <p>This verification is recorded and tied to the admin who approved it.</p>
      </div>
      <div class="info-tile">
        <strong>&#128172; Need Help?</strong>
        <p>Use the Support Center link on your Reports page if something's wrong.</p>
      </div>
    </div>
  </div>

  <script>
  function copyPhone(btn, phone) {
    navigator.clipboard.writeText(phone).then(function () {
      const original = btn.textContent;
      btn.textContent = 'Copied!';
      setTimeout(function () { btn.textContent = original; }, 1500);
    });
  }
  </script>

<?php endif; ?>

    </main>
  </div>
</div>
</body>
</html>
