<?php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'foundly_db';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port=3306;dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    die(
        'Database connection failed: ' . $e->getMessage()
    );
}