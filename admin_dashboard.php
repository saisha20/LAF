<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$userCount = $pdo->query(
    'SELECT COUNT(*) FROM users'
)->fetchColumn();

$reportCount = $pdo->query(
    'SELECT COUNT(*) FROM reports'
)->fetchColumn();

$pendingCount = $pdo->query(
    'SELECT COUNT(*) FROM matches WHERE status = "pending"'
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Foundly</title>

<link rel="stylesheet" href="base.css?v=2">
<link rel="stylesheet" href="dashboard.css">

<style>
.dashboard-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.dash-card {
    cursor: pointer;
    transition: 0.2s ease;
}

.dash-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

.card-link {
    display: inline-block;
    margin-top: 15px;
    color: #111b3a;
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
    <a href="admin_dashboard.php">Admin Dashboard</a>
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


<div class="dash-wrap">

    <h1>Admin Dashboard</h1>

    <p>Overview of the Foundly system.</p>


    <div class="dash-cards">

        <!-- USERS -->

        <a href="admin_users.php" class="dashboard-link">

            <div class="dash-card">

                <h3>Registered Users</h3>

                <p>
                    <?php echo (int)$userCount; ?>
                    users registered.
                </p>

                <span class="card-link">
                    View Users →
                </span>

            </div>

        </a>


        <!-- REPORTS -->

        <a href="admin_reports.php" class="dashboard-link">

            <div class="dash-card">

                <h3>Total Reports</h3>

                <p>
                    <?php echo (int)$reportCount; ?>
                    lost/found reports submitted.
                </p>

                <span class="card-link">
                    View Reports →
                </span>

            </div>

        </a>


        <!-- VERIFICATIONS -->

        <a href="admin_verifications.php" class="dashboard-link">

            <div class="dash-card">

                <h3>Pending Verifications</h3>

                <p>
                    <?php echo (int)$pendingCount; ?>
                    matches waiting for review.
                </p>

                <span class="card-link">
                    Review Matches →
                </span>

            </div>

        </a>

    </div>

</div>

</body>
</html>