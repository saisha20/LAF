<?php
$pageTitle = 'Lost & Found Reports';
$activeAdminPage = 'reports';
require 'admin_header.php';

$stmt = $pdo->query("
    SELECT
        r.report_id,
        r.user_id,
        r.type,
        r.item_name,
        r.description,
        r.color,
        r.brand,
        r.condition_status,
        r.location,
        r.date_reported,
        r.status,
        u.full_name
    FROM reports r
    LEFT JOIN users u
        ON r.user_id = u.user_id
    ORDER BY r.created_at DESC
");

$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.admin-table th,
.admin-table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

.admin-table th {
    background: #111b3a;
    color: white;
}

.admin-table tr:hover {
    background: #f7f7f7;
}
</style>

<h1>Lost & Found Reports</h1>

<p>
    View all reports submitted by registered users.
</p>

<div class="form-card" style="padding:0;overflow:hidden;">

<table class="admin-table">

    <thead>

        <tr>

            <th>ID</th>
            <th>User</th>
            <th>Type</th>
            <th>Item</th>
            <th>Description</th>
            <th>Color</th>
            <th>Brand</th>
            <th>Condition</th>
            <th>Location</th>
            <th>Date</th>
            <th>Status</th>

        </tr>

    </thead>


    <tbody>

    <?php foreach ($reports as $report): ?>

        <tr>

            <td>
                <?php echo htmlspecialchars($report['report_id']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($report['full_name'] ?? 'Unknown'); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($report['type']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($report['item_name']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($report['description']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($report['color']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($report['brand']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($report['condition_status']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($report['location']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($report['date_reported']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($report['status']); ?>
            </td>

        </tr>

    <?php endforeach; ?>

    </tbody>

</table>

</div>

    </main>
  </div>
</div>
</body>
</html>