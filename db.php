<?php
$DB_HOST = 'localhost';
$DB_NAME = 'foundly_db';
$DB_USER = 'root';  
$DB_PASS = '';        
try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS
    );
 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die('Database connection failed. Make sure Apache & MySQL are running in XAMPP '
        . 'and that the "foundly_db" database has been imported. Error: ' . $e->getMessage());
}