<?php
require_once 'db.php';
$adminName     = 'System Admin';
$adminEmail    = 'admin@kathford.edu.np';
$adminPhone    = '9800000000';
$adminPassword = 'Admin@123';

$stmt = $pdo->prepare(
    'SELECT admin_id FROM admin WHERE email = ? LIMIT 1'
);
$stmt->execute([$adminEmail]);

if ($stmt->fetch()) {

    echo 'An admin account with this email already exists.';

} else {

    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO admin
        (full_name, email, phone, password_hash, role)
        VALUES (?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $adminName,
        $adminEmail,
        $adminPhone,
        $hash,
        'admin'
    ]);

    echo 'Admin account created successfully.<br>';
    echo 'Email: ' . htmlspecialchars($adminEmail) . '<br>';
    echo 'Password: ' . htmlspecialchars($adminPassword) . '<br><br>';
    echo '<strong>Delete seed_admin.php after creating the account.</strong>';
}
?>