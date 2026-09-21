<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'category_icon.php';
requireLogin();

$uid = $_SESSION['user_id'];

// Delete own report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report_id'])) {
    $stmt = $pdo->prepare('DELETE FROM reports WHERE report_id = ? AND user_id = ?');
    $stmt->execute([$_POST['delete_report_id'], $uid]);
    header('Location: my_reports.php?deleted=1');
    exit;
}

// ---- Stats (real data) ----
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'open'");
$stmt->execute([$uid]);
$activeCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT m.match_id)
     FROM matches m
     JOIN reports rl ON rl.report_id = m.lost_report_id
     JOIN reports rf ON rf.report_id = m.found_report_id
     WHERE (rl.user_id = ? OR rf.user_id = ?) AND m.status = 'verified'"
);
$stmt->execute([$uid, $uid]);
$matchedCount = (int)$stmt->fetchColumn();

// ---- "Found a match?" banner - a real pending match on one of the user's reports ----
$stmt = $pdo->prepare(
    "SELECT m.match_id, r.report_id, r.item_name
     FROM matches m
     JOIN reports r ON r.report_id = m.lost_report_id OR r.report_id = m.found_report_id
     WHERE r.user_id = ? AND m.status = 'pending'
     LIMIT 1"
);
$stmt->execute([$uid]);
$pendingMatch = $stmt->fetch(PDO::FETCH_ASSOC);

// ---- Filters ----
$typeFilter = $_GET['type'] ?? 'all'; // all | lost | found
$search     = trim($_GET['q'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 6;

$where  = ['r.user_id = ?'];
$params = [$uid];

if ($typeFilter === 'lost' || $typeFilter === 'found') {
    $where[] = 'r.type = ?';
    $params[] = $typeFilter;
}
if ($search !== '') {
    $where[] = 'r.item_name LIKE ?';
    $params[] = '%' . $search . '%';
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM reports r WHERE $whereSql");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT r.report_id, r.type, r.item_name, r.color, r.brand, r.status, r.date_reported, r.category_id,
               m.status AS match_status
        FROM reports r
        LEFT JOIN matches m ON (m.lost_report_id = r.report_id OR m.found_report_id = r.report_id) AND m.status != 'rejected'
        WHERE $whereSql
        ORDER BY r.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle  = 'My Reports';
$activePage = 'reports';
require 'sidebar.php';
?>

<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;">
  <div>
    <h1>My Reports</h1>
    <p class="page-sub">Track the status of items you've reported as lost or found within the university campus.</p>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="my_claims.php" class="btn btn-outline">My Claims</a>
    <a href="report_lost.php" class="btn btn-navy">+ New Report</a>
  </div>
</div>

<?php if (isset($_GET['deleted'])): ?>
  <div class="form-alert success" style="max-width:700px;">Report deleted.</div>
<?php endif; ?>

<div class="reports-top-row">
  <div class="stat-box">
    <span class="stat-box-label">Active Reports</span>
    <span class="stat-box-value"><?php echo $activeCount; ?></span>
  </div>
  <div class="stat-box">
    <span class="stat-box-label">Items Matched</span>
    <span class="stat-box-value"><?php echo $matchedCount; ?></span>
  </div>

  <?php if ($pendingMatch): ?>
    <div class="match-banner">
      <div>
        <strong>Found a match?</strong>
        <p>One of your reports for "<?php echo htmlspecialchars($pendingMatch['item_name']); ?>" has a potential match. Review it now.</p>
      </div>
      <a href="claim_submit.php?match_id=<?php echo $pendingMatch['match_id']; ?>" class="btn" style="background:#fff;color:var(--navy);">Review Now</a>
    </div>
  <?php else: ?>
    <div class="match-banner match-banner-empty">
      <div>
        <strong>No pending matches</strong>
        <p>We'll let you know as soon as an admin finds a potential match for one of your reports.</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="reports-toolbar">
  <div class="type-tabs">
    <a href="?type=all" class="<?php echo $typeFilter === 'all' ? 'active' : ''; ?>">All Reports</a>
    <a href="?type=lost" class="<?php echo $typeFilter === 'lost' ? 'active' : ''; ?>">Lost Items</a>
    <a href="?type=found" class="<?php echo $typeFilter === 'found' ? 'active' : ''; ?>">Found Items</a>
  </div>
  <form method="GET" class="reports-search">
    <input type="hidden" name="type" value="<?php echo htmlspecialchars($typeFilter); ?>">
    <input type="text" name="q" placeholder="Search your reports..." value="<?php echo htmlspecialchars($search); ?>">
  </form>
</div>

<?php if (empty($reports)): ?>
  <p style="color:var(--text-muted);font-size:13px;margin-top:16px;">No reports match this view.</p>
<?php else: ?>
  <div class="form-card" style="padding:0;overflow:hidden;">
    <table class="reports-table">
      <thead>
        <tr>
          <th>Item Name</th>
          <th>Type</th>
          <th>Date Reported</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $r):
            if ($r['status'] === 'closed') {
                $statusLabel = 'Closed'; $statusClass = 'status-closed';
            } elseif ($r['match_status'] === 'verified') {
                $statusLabel = 'Matched'; $statusClass = 'status-matched';
            } elseif ($r['match_status'] === 'pending') {
                $statusLabel = 'Pending'; $statusClass = 'status-pending';
            } else {
                $statusLabel = 'Open'; $statusClass = 'status-open';
            }
        ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <span class="listing-icon"><?php echo category_icon(''); ?></span>
                <div>
                  <strong style="font-size:13px;"><?php echo htmlspecialchars($r['item_name']); ?></strong><br>
                  <small style="color:var(--text-muted);font-size:11px;"><?php echo htmlspecialchars(trim(($r['brand'] ?? '') . ' ' . ($r['color'] ?? ''))); ?></small>
                </div>
              </div>
            </td>
            <td><span class="badge <?php echo $r['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>"><?php echo ucfirst($r['type']); ?></span></td>
            <td><?php echo date('M j, Y', strtotime($r['date_reported'])); ?></td>
            <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
            <td style="text-align:right;">
       <a href="report_details.php?id=<?php echo $r['report_id']; ?>" style="font-size:12px;color:var(--indigo);margin-right:12px;">View</a>
<?php if ($r['match_status'] !== 'verified'): ?>
  <a href="edit_report.php?id=<?php echo $r['report_id']; ?>" style="font-size:12px;color:var(--indigo);margin-right:12px;">Edit</a>
<?php endif; ?>
              <form method="POST" action="my_reports.php" style="display:inline;" onsubmit="return confirm('Delete this report?');">
                <input type="hidden" name="delete_report_id" value="<?php echo $r['report_id']; ?>">
                <button type="submit" style="background:none;border:none;color:var(--error);cursor:pointer;font-size:12px;">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): $qs = $_GET; ?>
    <div class="pagination">
      <?php for ($p = 1; $p <= $totalPages; $p++): $qs['page'] = $p; ?>
        <a href="?<?php echo http_build_query($qs); ?>" class="page-btn <?php echo $p === $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
