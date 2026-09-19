<?php
require_once 'db.php';
require_once 'match_helper.php';

echo '<pre style="font-size:14px;background:#fff;padding:20px;">';

// List every open report so you can pick real IDs to test
$reports = $pdo->query("SELECT report_id, type, item_name, category_id, description, status FROM reports ORDER BY report_id")->fetchAll(PDO::FETCH_ASSOC);
echo "ALL REPORTS:\n";
foreach ($reports as $r) {
    echo "  #{$r['report_id']} [{$r['type']}] [{$r['status']}] {$r['item_name']} (category_id={$r['category_id']})\n";
    echo "      desc: {$r['description']}\n";
}

$testId = (int)($_GET['id'] ?? 0);
if ($testId) {
    echo "\n\nTESTING find_best_match_for_report() for report #$testId:\n";
    $match = find_best_match_for_report($pdo, $testId);
    if ($match) {
        echo "MATCH FOUND: #{$match['report_id']} - {$match['item_name']}\n";
    } else {
        echo "NO MATCH FOUND (either nothing scored >= 50, or no open opposite-type reports exist)\n";
    }
} else {
    echo "\n\nAdd &id=X to the URL to test matching for a specific report_id, e.g.:\n";
    echo "test_matching.php?id=6\n";
}

echo '</pre>';
