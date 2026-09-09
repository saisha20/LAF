<?php
/**
 * seed_admin.php
 * Run this ONCE in your browser (http://localhost/LAF/seed_admin.php)
 * to create an admin account in the `admin` table.
 *
 * DELETE THIS FILE after you've run it once, so nobody else can
 * re-run it and create extra admin accounts.
 */

require_once 'db.php';

// ---- Change these before running ----
$adminName     = 'System Admin';
$adminEmail    = 'admin@kathford.edu.np';
$adminPhone    = '9800000000';
$adminPassword = 'Admin@123';
// --------------------------------------

$stmt = $pdo->prepare('SELECT admin_id FROM admin WHERE email = ? LIMIT 1');
$stmt->execute([$adminEmail]);

if ($stmt->fetch()) {
    echo 'An admin with this email already exists. Nothing was created.';
} else {
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO admin (full_name, email, phone, password_hash, role)
         VALUES (?, ?, ?, ?, "admin")'
    );
    $stmt->execute([$adminName, $adminEmail, $adminPhone, $hash]);

    echo 'Admin account created successfully.<br>';
    echo 'Email: ' . htmlspecialchars($adminEmail) . '<br>';
    echo 'Password: ' . htmlspecialchars($adminPassword) . '<br><br>';
    echo '<strong>Now delete this file (seed_admin.php) from the LAF folder.</strong>';
}
