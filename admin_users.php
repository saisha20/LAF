<?php
$pageTitle = 'Registered Users';
$activeAdminPage = 'users';
require 'admin_header.php';

$stmt = $pdo->query("
    SELECT user_id, full_name, email, phone, role, created_at
    FROM users
    WHERE role = 'user'
    ORDER BY created_at DESC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
</style>

<h1>Registered Users</h1>

<p>
    List of users registered in the Foundly system.
</p>


<?php if (count($users) > 0): ?>

    <div class="form-card" style="padding:0;overflow:hidden;">

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

    </div>

<?php else: ?>

    <div class="form-card">
        No registered users found.
    </div>

<?php endif; ?>

    </main>
  </div>
</div>
</body>
</html>