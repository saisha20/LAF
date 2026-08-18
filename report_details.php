<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'category_icon.php';
requireLogin();

$reportId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT r.*, c.category_name, u.full_name AS reporter_name
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

$badgeLabel = $item['status'] === 'matched' ? 'Pending Verification' : ucfirst($item['type']);
?>

<a href="search_items.php" style="font-size:13px;color:var(--text-muted);display:inline-block;margin-bottom:14px;">&larr; Back to Search</a>

<div class="form-grid">
  <div class="form-card">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
      <span class="badge <?php echo $item['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>"><?php echo htmlspecialchars($badgeLabel); ?></span>
      <h1 style="font-size:20px;margin:0;"><?php echo htmlspecialchars($item['item_name']); ?></h1>
    </div>

    <?php if ($item['photo_data']): ?>
      <img src="serve_photo.php?id=<?php echo $item['report_id']; ?>" alt="" style="width:100%;max-height:280px;object-fit:cover;border-radius:10px;margin-bottom:18px;">
    <?php else: ?>
      <div style="height:180px;background:#eef0f6;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:40px;margin-bottom:18px;">
        <?php echo category_icon($item['category_name'] ?? ''); ?>
      </div>
    <?php endif; ?>

    <h2 style="font-size:14px;">Description</h2>
    <p style="font-size:13.5px;color:var(--text-dark);margin-bottom:18px;"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>

    <div class="field-row">
      <div>
        <label style="font-size:12px;color:var(--text-muted);">Category</label>
        <p style="font-size:13.5px;"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></p>
      </div>
      <div>
        <label style="font-size:12px;color:var(--text-muted);">Date</label>
        <p style="font-size:13.5px;"><?php echo date('M j, Y', strtotime($item['date_reported'])); ?></p>
      </div>
    </div>

    <?php if ($item['type'] === 'lost'): ?>
      <div class="field-row" style="margin-top:14px;">
        <?php if ($item['color']): ?><div><label style="font-size:12px;color:var(--text-muted);">Color</label><p style="font-size:13.5px;"><?php echo htmlspecialchars($item['color']); ?></p></div><?php endif; ?>
        <?php if ($item['brand']): ?><div><label style="font-size:12px;color:var(--text-muted);">Brand</label><p style="font-size:13.5px;"><?php echo htmlspecialchars($item['brand']); ?></p></div><?php endif; ?>
      </div>
      <?php if ($item['reward_amount']): ?>
        <p style="font-size:13px;margin-top:10px;"><strong>Reward:</strong> $<?php echo number_format($item['reward_amount'], 2); ?></p>
      <?php endif; ?>
    <?php else: ?>
      <div class="field-row" style="margin-top:14px;">
        <div><label style="font-size:12px;color:var(--text-muted);">Found Location</label><p style="font-size:13.5px;"><?php echo htmlspecialchars($item['location']); ?></p></div>
        <?php if ($item['condition_status']): ?><div><label style="font-size:12px;color:var(--text-muted);">Condition</label><p style="font-size:13.5px;"><?php echo htmlspecialchars(ucfirst($item['condition_status'])); ?></p></div><?php endif; ?>
      </div>
      <?php if ($item['notes']): ?>
        <p style="font-size:13px;margin-top:10px;"><strong>Notes:</strong> <?php echo htmlspecialchars($item['notes']); ?></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div>
    <div class="notice-card green">
      <strong>&#128274; Contact Protected</strong>
      This item was reported by <?php echo htmlspecialchars(explode(' ', $item['reporter_name'])[0]); ?>. Contact details are only shared with the verified counterpart after an admin confirms a match between a lost and found report.
    </div>
    <?php if ($item['user_id'] == $_SESSION['user_id']): ?>
      <div class="form-card" style="margin-top:16px;">
        <p style="font-size:13px;color:var(--text-muted);">This is your own report. Manage it from <a href="my_reports.php">My Reports</a>.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

