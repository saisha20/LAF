<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$stmt = $pdo->query("
    SELECT user_id, full_name, email, phone, role, created_at
    FROM users
    WHERE role = 'user'
    ORDER BY created_at DESC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Registered Users - Foundly</title>

<link rel="stylesheet" href="base.css">

<style>

.admin-container {
    width: 90%;
    max-width: 1200px;
    margin: 50px auto;
}

.admin-container h1 {
    color: #111b3a;
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
    margin-top: 20px;
}

.admin-table th,
.admin-table td {
    border: 1px solid #ddd;
    padding: 14px;
    text-align: left;
}

.admin-table th {
    background: #111b3a;
    color: white;
}

.admin-table tr:hover {
    background: #f7f7f7;
}

.empty {
    padding: 25px;
    border: 1px solid #ddd;
    margin-top: 20px;
}

</style>

</head>

<body>

<header class="site-header">

    <div class="brand">

        <div class="brand-icon">&#9737;</div>

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

    <h1>Registered Users</h1>

    <p>
        List of users registered in the Foundly system.
    </p>


    <?php if (count($users) > 0): ?>

        <table class="admin-table">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Registered Date</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($users as $user): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($user['user_id']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($user['phone']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($user['role']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($user['created_at']); ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php else: ?>

        <div class="empty">
            No registered users found.
        </div>

    <?php endif; ?>

</div>

</body>
</html>