<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'category_icon.php';
requireLogin();

$categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll(PDO::FETCH_ASSOC);

$search     = trim($_GET['q'] ?? '');
$categoryId = $_GET['category_id'] ?? '';
$statusType = $_GET['status_type'] ?? 'all'; // all | lost | found
$dateRange  = $_GET['date_range'] ?? 'any';  // any | 7 | 30
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 8;

$where  = ['r.is_public = 1', "r.status != 'closed'"];
$params = [];

if ($search !== '') {
    $where[] = '(r.item_name LIKE ? OR r.description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($categoryId !== '') {
    $where[] = 'r.category_id = ?';
    $params[] = $categoryId;
}
if ($statusType === 'lost' || $statusType === 'found') {
    $where[] = 'r.type = ?';
    $params[] = $statusType;
}
if ($dateRange === '7' || $dateRange === '30') {
    $where[] = 'r.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)';
    $params[] = (int)$dateRange;
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM reports r WHERE $whereSql");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT r.report_id, r.type, r.item_name, r.status, r.location, r.created_at,
               (r.photo_data IS NOT NULL) AS has_photo, c.category_name
        FROM reports r
        LEFT JOIN categories c ON c.category_id = r.category_id
        WHERE $whereSql
        ORDER BY r.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle  = 'Search Items';
$activePage = 'search';
require 'sidebar.php';
?>

<h1>Find Your Belongings</h1>
<p class="page-sub">
  Browse all lost and found items submitted by the university community.
  <span class="items-count"><?php echo $totalItems; ?> Items Found</span>
</p>

<form method="GET" action="search_items.php" class="filter-bar">
  <div class="filter-group">
    <label>Category</label>
    <select name="category_id" onchange="this.form.submit()">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?php echo $cat['category_id']; ?>" <?php echo ($categoryId == $cat['category_id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($cat['category_name']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="filter-group">
    <label>Date Range</label>
    <select name="date_range" onchange="this.form.submit()">
      <option value="any" <?php echo $dateRange === 'any' ? 'selected' : ''; ?>>Any Time</option>
      <option value="7" <?php echo $dateRange === '7' ? 'selected' : ''; ?>>Last 7 Days</option>
      <option value="30" <?php echo $dateRange === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
    </select>
  </div>

  <div class="filter-group">
    <label>Status</label>
    <select name="status_type" onchange="this.form.submit()">
      <option value="all" <?php echo $statusType === 'all' ? 'selected' : ''; ?>>Lost &amp; Found</option>
      <option value="lost" <?php echo $statusType === 'lost' ? 'selected' : ''; ?>>Lost Only</option>
      <option value="found" <?php echo $statusType === 'found' ? 'selected' : ''; ?>>Found Only</option>
    </select>
  </div>

  <div class="filter-group filter-search">
    <label>Search</label>
    <input type="text" name="q" placeholder="Search by item name or category..." value="<?php echo htmlspecialchars($search); ?>">
  </div>
</form>

<?php if (empty($items)): ?>
  <p style="color:var(--text-muted);font-size:13px;margin-top:20px;">No items match your filters.</p>
<?php else: ?>
  <div class="item-grid">
    <?php foreach ($items as $item):
        if ($item['status'] === 'matched') {
            $badgeClass = 'badge-pending';
            $badgeLabel = 'Pending';
        } elseif ($item['type'] === 'lost') {
            $badgeClass = 'badge-lost';
            $badgeLabel = 'Lost';
        } else {
            $badgeClass = 'badge-found';
            $badgeLabel = 'Found';
        }
    ?>
      <div class="item-card">
        <div class="item-thumb">
          <?php if ($item['has_photo']): ?>
            <img src="serve_photo.php?id=<?php echo $item['report_id']; ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
          <?php else: ?>
            <span class="item-thumb-icon"><?php echo category_icon($item['category_name'] ?? ''); ?></span>
          <?php endif; ?>
          <span class="item-badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
        </div>
        <div class="item-body">
          <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
          <div class="item-meta">
            <span>&#128197; <?php echo date('M j, Y', strtotime($item['created_at'])); ?></span>
            <span>&#128205; <?php echo htmlspecialchars($item['location'] === 'Not specified' ? ($item['category_name'] ?? 'Uncategorized') : $item['location']); ?></span>
          </div>
          <a href="report_details.php?id=<?php echo $item['report_id']; ?>" class="btn btn-navy btn-block">View Details</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1):
      $qs = $_GET;
  ?>
    <div class="pagination">
      <?php $qs['page'] = max(1, $page - 1); ?>
      <a href="?<?php echo http_build_query($qs); ?>" class="page-btn <?php echo $page === 1 ? 'disabled' : ''; ?>">&laquo;</a>

      <?php for ($p = 1; $p <= $totalPages; $p++):
          if ($p > 3 && $p < $totalPages - 1 && abs($p - $page) > 1) {
              if ($p == 4) echo '<span class="page-dots">...</span>';
              continue;
          }
          $qs['page'] = $p;
      ?>
        <a href="?<?php echo http_build_query($qs); ?>" class="page-btn <?php echo $p === $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
      <?php endfor; ?>

      <?php $qs['page'] = min($totalPages, $page + 1); ?>
      <a href="?<?php echo http_build_query($qs); ?>" class="page-btn <?php echo $page === $totalPages ? 'disabled' : ''; ?>">&raquo;</a>
    </div>
  <?php endif; ?>
<?php endif; ?>


