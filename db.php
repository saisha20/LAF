<?php
/**
 * db.php
 * Single shared database connection (PDO) for the whole project.
 * Every other PHP file should do: require_once 'db.php';
 * and then use the $pdo variable.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'foundly_db';
$DB_USER = 'root';   // default XAMPP username
$DB_PASS = '';        // default XAMPP password is empty

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS
    );
    // Throw exceptions on error instead of failing silently
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Return real PHP types (not everything as string)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die('Database connection failed. Make sure Apache & MySQL are running in XAMPP '
        . 'and that the "foundly_db" database has been imported. Error: ' . $e->getMessage());
}