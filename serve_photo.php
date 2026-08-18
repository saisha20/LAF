<?php
require_once 'db.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT photo_data, photo_type FROM reports WHERE report_id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && $row['photo_data']) {
    header('Content-Type: ' . $row['photo_type']);
    header('Cache-Control: private, max-age=3600');
    echo $row['photo_data'];
} else {
    // 1x1 transparent PNG fallback - the card CSS shows a category icon behind it
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
}
