<?php
require_once 'auth.php';
require_once 'db.php';
requireLogin();

$stmt = $pdo->prepare('SELECT report_id, type, item_name, status, created_at FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$_SESSION['user_id']]);
$myReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require 'sidebar.php';
?>

<h1>Welcome, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?></h1>
<p class="page-sub">Here's what's happening with your reports.</p>

<?php if (isset($_GET['reported'])): ?>
  <div class="form-alert success" style="max-width:700px;">
    Your <?php echo $_GET['reported'] === 'lost' ? 'lost' : 'found'; ?> item report was submitted successfully.
  </div>
<?php endif; ?>

<div class="dash-cards">
  <a href="report_lost.php" class="dash-card">
    <h3>Report a Lost Item</h3>
    <p>Submit details about something you lost.</p>
  </a>
  <a href="report_found.php" class="dash-card">
    <h3>Report a Found Item</h3>
    <p>Submit details about something you found.</p>
  </a>
  <a href="my_reports.php" class="dash-card">
    <h3>My Reports</h3>
    <p>View and manage everything you've reported.</p>
  </a>
</div>

<h2 style="font-size:16px;margin:30px 0 14px;">My Recent Reports</h2>

<?php if (empty($myReports)): ?>
  <p style="color:var(--text-muted);font-size:13px;">You haven't submitted any reports yet.</p>
<?php else: ?>
  <div class="form-card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
      <thead>
        <tr style="background:#f4f6fb;text-align:left;">
          <th style="padding:12px 18px;">Item</th>
          <th style="padding:12px 18px;">Type</th>
          <th style="padding:12px 18px;">Status</th>
          <th style="padding:12px 18px;">Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($myReports as $r): ?>
          <tr style="border-top:1px solid var(--border-gray);">
            <td style="padding:12px 18px;"><?php echo htmlspecialchars($r['item_name']); ?></td>
            <td style="padding:12px 18px;text-transform:capitalize;"><?php echo htmlspecialchars($r['type']); ?></td>
            <td style="padding:12px 18px;text-transform:capitalize;"><?php echo htmlspecialchars($r['status']); ?></td>
            <td style="padding:12px 18px;"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

