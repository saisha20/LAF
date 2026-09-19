<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'category_icon.php';
requireLogin();

$uid = $_SESSION['user_id'];
$search = trim($_GET['q'] ?? '');

$where = ['rl.user_id = ?'];
$params = [$uid];
if ($search !== '') {
    $where[] = 'rf.item_name LIKE ?';
    $params[] = '%' . $search . '%';
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT m.match_id, m.status, m.claim_statement, m.created_at,
            rf.report_id AS found_report_id, rf.item_name AS found_item, rf.description AS found_desc,
            rf.location AS found_location, (rf.photo_data IS NOT NULL) AS found_has_photo, c.category_name
     FROM matches m
     JOIN reports rl ON rl.report_id = m.lost_report_id
     JOIN reports rf ON rf.report_id = m.found_report_id
     LEFT JOIN categories c ON c.category_id = rf.category_id
     WHERE $whereSql
     ORDER BY m.created_at DESC"
);
$stmt->execute($params);
$claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activeCount = 0;
$resolvedCount = 0;
foreach ($claims as $c) {
    if ($c['status'] === 'pending') $activeCount++;
    else $resolvedCount++;
}

function time_ago_short($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 86400) return 'today';
    $days = floor($diff / 86400);
    return $days == 1 ? '1 day ago' : "$days days ago";
}

$pageTitle  = 'My Claims';
$activePage = 'claims';
require 'sidebar.php';
?>

<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;">
  <div>
    <h1>Ownership Claims</h1>
    <p class="page-sub">Track and manage your submitted recovery requests.</p>
  </div>
  <div style="display:flex;gap:10px;">
    <span class="status-pill status-pending"><?php echo $activeCount; ?> Active</span>
    <span class="status-pill status-closed"><?php echo $resolvedCount; ?> Resolved</span>
  </div>
</div>

<form method="GET" class="reports-search" style="margin:18px 0 22px;">
  <input type="text" name="q" placeholder="Search your claims..." value="<?php echo htmlspecialchars($search); ?>" style="max-width:320px;">
</form>

<?php if (empty($claims)): ?>
  <p style="color:var(--text-muted);font-size:13px;">You don't have any claims yet. When an admin proposes a match on one of your lost reports, it'll show up here.</p>
<?php else: ?>
  <div class="item-grid">
    <?php foreach ($claims as $c):
        $hasEvidence = !empty($c['claim_statement']);
        if ($c['status'] === 'verified') {
            $badgeClass = 'badge-found'; $badgeLabel = 'Approved';
        } elseif ($c['status'] === 'rejected') {
            $badgeClass = 'badge-lost'; $badgeLabel = 'Rejected';
        } elseif (!$hasEvidence) {
            $badgeClass = 'badge-pending'; $badgeLabel = 'Verification Req.';
        } else {
            $badgeClass = 'badge-pending'; $badgeLabel = 'Pending';
        }
    ?>
      <div class="item-card">
        <div class="item-thumb">
          <?php if ($c['found_has_photo']): ?>
            <img src="serve_photo.php?id=<?php echo $c['found_report_id']; ?>" alt="<?php echo htmlspecialchars($c['found_item']); ?>">
          <?php else: ?>
            <span class="item-thumb-icon"><?php echo category_icon($c['category_name'] ?? ''); ?></span>
          <?php endif; ?>
          <span class="item-badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
        </div>
        <div class="item-body">
          <h3><?php echo htmlspecialchars($c['found_item']); ?> <small style="color:var(--text-muted);font-weight:400;">#CLM-<?php echo str_pad($c['match_id'], 4, '0', STR_PAD_LEFT); ?></small></h3>
          <div class="item-meta">
            <span>&#128205; <?php echo htmlspecialchars($c['found_location']); ?></span>
            <span>
              <?php if ($c['status'] === 'verified'): ?>
                Ownership verified. Please contact the finder.
              <?php elseif ($c['status'] === 'rejected'): ?>
                Descriptions provided did not sufficiently match.
              <?php elseif ($hasEvidence): ?>
                Claimed <?php echo time_ago_short($c['created_at']); ?>. Awaiting admin review.
              <?php else: ?>
                Submit your evidence to begin verification.
              <?php endif; ?>
            </span>
          </div>

          <?php if ($c['status'] === 'verified'): ?>
            <a href="contact_info.php?report_id=<?php echo $c['found_report_id']; ?>" class="btn btn-navy btn-block">Get Contact Detail of the Finder</a>
          <?php elseif ($c['status'] === 'rejected'): ?>
            <a href="report_details.php?id=<?php echo $c['found_report_id']; ?>" class="btn btn-outline btn-block">View Details</a>
          <?php elseif (!$hasEvidence): ?>
            <a href="claim_submit.php?match_id=<?php echo $c['match_id']; ?>" class="btn btn-navy btn-block">Provide Evidence</a>
          <?php else: ?>
            <a href="claim_submit.php?match_id=<?php echo $c['match_id']; ?>" class="btn btn-outline btn-block">View Details</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

