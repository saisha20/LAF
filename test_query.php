<?php
require_once 'db.php';

$reportId = (int)($_GET['id'] ?? 7);

$stmt = $pdo->prepare(
    'SELECT r.report_id, r.item_name, r.type, r.status, c.category_name, u.full_name AS reporter_name
     FROM reports r
     LEFT JOIN categories c ON c.category_id = r.category_id
     LEFT JOIN users u ON u.user_id = r.user_id
     WHERE r.report_id = ?'
);
$stmt->execute([$reportId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

echo '<pre style="font-size:14px;background:#fff;padding:20px;">';
echo "Testing report_id = $reportId\n\n";
echo "Raw result from PDO:\n";
var_dump($item);
echo '</pre>';