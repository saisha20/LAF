<?php
require_once 'auth.php';
require_once 'db.php';

$categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll(PDO::FETCH_ASSOC);

$search     = trim($_GET['q'] ?? '');
$categoryId = $_GET['category_id'] ?? '';

$sql = "SELECT r.report_id, r.item_name, r.description, r.color, r.brand, r.created_at, c.category_name
        FROM reports r
        LEFT JOIN categories c ON c.category_id = r.category_id
        WHERE r.type = 'lost' AND r.is_public = 1";
$params = [];

if ($search !== '') {
    $sql .= ' AND (r.item_name LIKE ? OR r.description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($categoryId !== '') {
    $sql .= ' AND r.category_id = ?';
    $params[] = $categoryId;
}
$sql .= ' ORDER BY r.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Lost Items - Foundly</title>
<link rel="stylesheet" href="base.css?v=2">
<link rel="stylesheet" href="browse.css">
<link rel="icon" type="image/png" href="image/foundly.png">
</head>
<body>

<header class="site-header">
  <div class="brand">
    <div class="brand-icon"><img src="image/foundly.png" alt="Foundly logo"></div>
    <div class="brand-text">
      <span class="brand-name">Foundly</span>
      <span class="brand-tagline">Reliable Recovery</span>
    </div>
  </div>
  <nav class="site-nav">
    <a href="index.php">Home</a>
    <a href="lost_items.php">Lost Items</a>
    <a href="found_items.php">Found Items</a>
    <a href="<?php echo isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>">Dashboard</a>
  </nav>
  <div class="nav-actions">
    <?php if (isLoggedIn()): ?>
      <a href="logout.php" class="btn btn-navy">Logout</a>
    <?php else: ?>
      <a href="login.php" class="nav-login">LOGIN</a>
      <a href="register.php" class="btn btn-navy">REGISTER</a>
    <?php endif; ?>
  </div>
</header>

<div class="browse-wrap">
  <h1>Lost Items</h1>
  <p class="page-sub">Items reported lost by the university community. Contact details are never shown here — they're only released to the relevant parties after admin verification.</p>

  <form method="GET" action="lost_items.php" class="browse-filters">
    <input type="text" name="q" placeholder="Search by item name or description..." value="<?php echo htmlspecialchars($search); ?>">
    <select name="category_id">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?php echo $cat['category_id']; ?>" <?php echo ($categoryId == $cat['category_id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($cat['category_name']); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-navy">Filter</button>
  </form>

  <?php if (empty($items)): ?>
    <p class="empty-msg">No lost items match your search.</p>
  <?php else: ?>
    <div class="browse-grid">
      <?php foreach ($items as $item): ?>
        <div class="browse-card">
          <span class="badge badge-lost">Lost</span>
          <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
          <p><?php echo htmlspecialchars(mb_strimwidth($item['description'], 0, 110, '...')); ?></p>
          <div class="browse-meta">
            <span><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></span>
            <span><?php echo date('M j, Y', strtotime($item['created_at'])); ?></span>
          </div>
          <?php if ($item['color'] || $item['brand']): ?>
            <div class="browse-tags">
              <?php if ($item['brand']): ?><span class="tag"><?php echo htmlspecialchars($item['brand']); ?></span><?php endif; ?>
              <?php if ($item['color']): ?><span class="tag"><?php echo htmlspecialchars($item['color']); ?></span><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>



</body>
</html>
