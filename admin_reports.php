<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

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

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Reports - Foundly</title>

<link rel="stylesheet" href="base.css?v=2">

<style>

.admin-container {
    width: 92%;
    max-width: 1400px;
    margin: 50px auto;
}

.back-link {
    display: inline-block;
    margin-bottom: 25px;
    color: #111b3a;
    text-decoration: none;
    font-weight: 600;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    margin-top: 25px;
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

.badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 13px;
    font-weight: 600;
}

</style>

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

        <a href="admin_dashboard.php">
            Admin Dashboard
        </a>

    </nav>


    <div class="nav-actions">

        <span class="nav-login">
            Admin:
            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        </span>

        <a href="logout.php" class="btn btn-navy">
            Logout
        </a>

    </div>

</header>


<div class="admin-container">

    <a href="admin_dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

    <h1>Lost & Found Reports</h1>

    <p>
        View all reports submitted by registered users.
    </p>


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

</body>
</html>