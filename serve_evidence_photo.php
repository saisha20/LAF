<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$matchId = (int)($_GET['match_id'] ?? 0);

$stmt = $pdo->prepare('SELECT claim_evidence_photo, claim_evidence_type FROM matches WHERE match_id = ?');
$stmt->execute([$matchId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && $row['claim_evidence_photo']) {
    header('Content-Type: ' . $row['claim_evidence_type']);
    header('Cache-Control: private, max-age=3600');
    echo $row['claim_evidence_photo'];
} else {
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
}
