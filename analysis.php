<?php
require_once 'auth.php';
requireLogin();
require 'db.php'; // provides $pdo

function scalar($pdo, $sql) {
    $stmt = $pdo->query($sql);
    return (int)$stmt->fetchColumn();
}

// --- Top-level counts ---
$total_reports   = scalar($pdo, "SELECT COUNT(*) FROM reports");
$total_lost      = scalar($pdo, "SELECT COUNT(*) FROM reports WHERE type = 'lost'");
$total_found     = scalar($pdo, "SELECT COUNT(*) FROM reports WHERE type = 'found'");
$total_matched   = scalar($pdo, "SELECT COUNT(*) FROM reports WHERE status = 'matched'");
$total_open      = scalar($pdo, "SELECT COUNT(*) FROM reports WHERE status = 'open'");
$match_rate      = $total_reports > 0 ? round(($total_matched / $total_reports) * 100, 1) : 0;

// --- Reports over time (by month) ---
$sql = "SELECT DATE_FORMAT(date_reported, '%Y-%m') AS month,
               SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) AS lost,
               SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) AS found
        FROM reports
        GROUP BY month
        ORDER BY month ASC";
$monthly_data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// --- Top locations ---
$sql = "SELECT location, COUNT(*) AS cnt
        FROM reports
        WHERE location IS NOT NULL AND location != '' AND location != 'Not specified'
        GROUP BY location
        ORDER BY cnt DESC
        LIMIT 5";
$location_data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// --- Full report list (client-side filtering handles All/Lost/Found/Matched/Left) ---
$sql = "SELECT report_id, item_name, type, status, location, date_reported
        FROM reports
        ORDER BY date_reported DESC";
$all_reports = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Group by month for display
$reports_by_month = [];
foreach ($all_reports as $r) {
    $monthKey = date('F Y', strtotime($r['date_reported']));
    $reports_by_month[$monthKey][] = $r;
}

$pageTitle = 'Analysis';
$activePage = 'analysis';
require_once 'sidebar.php';
?>

<link rel="stylesheet" href="dashboard.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
  .stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 32px; }
  @media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 560px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
  .stat-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
  .stat-card .value { font-size: 32px; font-weight: bold; }
  .stat-card .label { color: #666; font-size: 13px; margin-top: 4px; }
  .lost .value { color: #c0392b; }
  .found .value { color: #27ae60; }
  .matched .value { color: #2980b9; }
  .open .value { color: #e67e22; }
  .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
  .chart-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .chart-card h3 { margin-top: 0; }
  .an-table { width: 100%; border-collapse: collapse; }
  .an-table th, .an-table td { text-align: left; padding: 8px; border-bottom: 1px solid #eee; }
  @media (max-width: 800px) { .charts-grid { grid-template-columns: 1fr; } }
  .filter-tabs { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
  .filter-tabs a {
    padding: 8px 16px; border-radius: 20px; text-decoration: none; cursor: pointer;
    font-size: 13px; color: #444; background: #eee; border: 1px solid #ddd;
  }
  .filter-tabs a.active { background: #2c3e50; color: #fff; border-color: #2c3e50; }
  .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; }
  .badge-lost { background: #fdecea; color: #c0392b; }
  .badge-found { background: #e9f9ee; color: #27ae60; }
  .badge-matched { background: #eaf2fb; color: #2980b9; }
  .badge-open { background: #fdf3e3; color: #e67e22; }
  .month-heading { margin: 20px 0 8px; font-size: 15px; color: #333; }
  .month-count { color: #999; font-weight: normal; font-size: 13px; }
  .chart-card-small { max-width: 480px; }
  .chart-wrap { position: relative; height: 220px; }
</style>

<h1>Reports analysis</h1>
<p>Overview of lost and found activity across Foundly</p>

<div class="stats-grid">
  <div class="stat-card">
    <div class="value"><?= $total_reports ?></div>
    <div class="label">Total reports</div>
  </div>
  <div class="stat-card lost">
    <div class="value"><?= $total_lost ?></div>
    <div class="label">Lost items</div>
  </div>
  <div class="stat-card found">
    <div class="value"><?= $total_found ?></div>
    <div class="label">Found items</div>
  </div>
  <div class="stat-card matched">
    <div class="value"><?= $total_matched ?></div>
    <div class="label">Matched</div>
  </div>
  <div class="stat-card open">
    <div class="value"><?= $total_open ?></div>
    <div class="label">Still open</div>
  </div>
  <div class="stat-card">
    <div class="value"><?= $match_rate ?>%</div>
    <div class="label">Match rate</div>
  </div>
</div>

<div class="chart-card chart-card-small">
  <h3>Lost vs found by month</h3>
  <div class="chart-wrap">
    <canvas id="monthlyChart"></canvas>
  </div>
</div>

<div class="chart-card">
  <h3>Reports</h3>

  <div class="filter-tabs">
    <a href="#" data-filter="all"     class="active">All (<?= $total_reports ?>)</a>
    <a href="#" data-filter="lost"    >Lost (<?= $total_lost ?>)</a>
    <a href="#" data-filter="found"   >Found (<?= $total_found ?>)</a>
    <a href="#" data-filter="matched" >Matched (<?= $total_matched ?>)</a>
    <a href="#" data-filter="left"    >Left (<?= $total_open ?>)</a>
  </div>

  <?php if (empty($reports_by_month)): ?>
  <table class="an-table">
    <tr><td>No reports yet.</td></tr>
  </table>
  <?php endif; ?>

  <?php foreach ($reports_by_month as $month => $rows): ?>
  <div class="month-block">
    <h4 class="month-heading"><?= htmlspecialchars($month) ?> <span class="month-count">(<?= count($rows) ?>)</span></h4>
    <table class="an-table">
      <tr><th>Item</th><th>Type</th><th>Status</th><th>Location</th><th>Date</th></tr>
      <?php foreach ($rows as $r):
        $rowStatus = $r['status'] === 'matched' ? 'matched' : 'left';
      ?>
      <tr data-type="<?= htmlspecialchars($r['type']) ?>" data-status="<?= $rowStatus ?>">
        <td><?= htmlspecialchars($r['item_name']) ?></td>
        <td><span class="badge badge-<?= htmlspecialchars($r['type']) ?>"><?= ucfirst(htmlspecialchars($r['type'])) ?></span></td>
        <td><span class="badge badge-<?= $r['status'] === 'matched' ? 'matched' : 'open' ?>"><?= ucfirst(htmlspecialchars($r['status'])) ?></span></td>
        <td><?= htmlspecialchars($r['location']) ?></td>
        <td><?= htmlspecialchars($r['date_reported']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endforeach; ?>
</div>

<div class="chart-card">
  <h3>Top reported locations</h3>
  <table class="an-table">
    <tr><th>Location</th><th>Reports</th></tr>
    <?php foreach ($location_data as $loc): ?>
    <tr>
      <td><?= htmlspecialchars($loc['location']) ?></td>
      <td><?= $loc['cnt'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<script>
const monthlyData = <?= json_encode($monthly_data) ?>;

new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels: monthlyData.map(d => d.month),
    datasets: [
      { label: 'Lost', data: monthlyData.map(d => d.lost), backgroundColor: '#5b58d6', borderRadius: 4 },
      { label: 'Found', data: monthlyData.map(d => d.found), backgroundColor: '#a5a3ea', borderRadius: 4 }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { labels: { color: '#333' } }
    },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1, color: '#666' }, grid: { color: '#eee' } },
      x: { ticks: { color: '#666' }, grid: { display: false } }
    }
  }
});

// Client-side filtering for the Reports section — no page reload,
// so the chart above never re-renders when switching tabs.
document.querySelectorAll('.filter-tabs a').forEach(tab => {
  tab.addEventListener('click', function (e) {
    e.preventDefault();
    document.querySelectorAll('.filter-tabs a').forEach(t => t.classList.remove('active'));
    this.classList.add('active');

    const filter = this.dataset.filter;
    document.querySelectorAll('.month-block').forEach(block => {
      let visibleInBlock = 0;
      block.querySelectorAll('tr[data-type]').forEach(row => {
        const matches =
          filter === 'all' ||
          filter === row.dataset.type ||
          filter === row.dataset.status;
        row.style.display = matches ? '' : 'none';
        if (matches) visibleInBlock++;
      });
      block.style.display = visibleInBlock > 0 ? '' : 'none';
    });
  });
});
</script>

  </main>
</div>
</body>
</html>