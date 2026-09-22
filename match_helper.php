<?php
/**
 * match_helper.php
 * Shared functions for auto-matching lost/found reports and sending
 * the notifications required at each stage: match created, match
 * verified, match rejected.
 *
 * Include this file (require_once 'match_helper.php';) anywhere a
 * match might be created or its status changed, so every path uses
 * identical logic and identical notification wording.
 */

/**
 * Very simple, explainable similarity score between two pieces of text.
 * Not real AI - just shared-keyword overlap after stripping common
 * filler words. Returns a value from 0 to 1.
 */
function word_overlap_score($a, $b) {
    $stopwords = ['a','an','the','and','or','in','on','at','with','for','of','is','was','to','it',
                  'this','that','my','i','has','have','near','found','lost','item','the'];

    $split = function ($text) use ($stopwords) {
        $words = preg_split('/\W+/', strtolower((string)$text));
        $words = array_filter($words, function ($w) use ($stopwords) {
            return $w !== '' && !in_array($w, $stopwords, true);
        });
        return array_unique($words);
    };

    $wordsA = $split($a);
    $wordsB = $split($b);

    if (empty($wordsA) || empty($wordsB)) {
        return 0;
    }

    $intersection = count(array_intersect($wordsA, $wordsB));
    $union        = count(array_unique(array_merge($wordsA, $wordsB)));

    return $union > 0 ? $intersection / $union : 0;
}

/**
 * Looks at a newly-submitted report and searches OPEN reports of the
 * opposite type for the single best-scoring candidate. Returns that
 * candidate's full row, or null if nothing scores high enough to be
 * confident about.
 */
function find_best_match_for_report($pdo, $newReportId) {
    $stmt = $pdo->prepare('SELECT * FROM reports WHERE report_id = ?');
    $stmt->execute([$newReportId]);
    $newReport = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$newReport) {
        return null;
    }

    $oppositeType = $newReport['type'] === 'lost' ? 'found' : 'lost';

    $stmt = $pdo->prepare("SELECT * FROM reports WHERE type = ? AND status = 'open' AND report_id != ? AND user_id != ?");
    $stmt->execute([$oppositeType, $newReportId, $newReport['user_id']]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $bestScore     = 0;
    $bestCandidate = null;

    foreach ($candidates as $c) {
        $score = 0;

        // Same category is a strong signal
        if (!empty($c['category_id']) && !empty($newReport['category_id']) && $c['category_id'] == $newReport['category_id']) {
            $score += 40;
        }

        $score += word_overlap_score($newReport['item_name'], $c['item_name']) * 30;
        $score += word_overlap_score($newReport['description'], $c['description']) * 30;

        if ($score > $bestScore) {
            $bestScore     = $score;
            $bestCandidate = $c;
        }
    }

    // Require a reasonably confident score before auto-proposing -
    // avoids spamming users with weak, unlikely matches.
    return ($bestCandidate && $bestScore >= 50) ? $bestCandidate : null;
}

/**
 * Creates a pending match between a lost report and a found report,
 * unless a non-rejected match between that exact pair already exists
 * (prevents duplicate matches and duplicate notifications for the
 * same event). Sends the required "match created" notification to
 * both sides. Returns the new match_id, or null if one already
 * existed / nothing was created.
 */
function create_match_with_notification($pdo, $lostReportId, $foundReportId) {
    $stmt = $pdo->prepare('SELECT match_id FROM matches WHERE lost_report_id = ? AND found_report_id = ? AND status != "rejected"');
    $stmt->execute([$lostReportId, $foundReportId]);
    if ($stmt->fetch()) {
        return null; // already exists - do not create a duplicate
    }

    $stmt = $pdo->prepare('INSERT INTO matches (lost_report_id, found_report_id, status) VALUES (?, ?, "pending")');
    $stmt->execute([$lostReportId, $foundReportId]);
    $matchId = $pdo->lastInsertId();

    notify_match_created($pdo, $lostReportId, $foundReportId);

    return $matchId;
}

/**
 * Sends the required "your item has been matched" notification to
 * both the lost report's owner and the found report's finder.
 */
function notify_match_created($pdo, $lostReportId, $foundReportId) {
    $stmt = $pdo->prepare('SELECT report_id, user_id, item_name FROM reports WHERE report_id IN (?, ?)');
    $stmt->execute([$lostReportId, $foundReportId]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reports as $r) {
        $otherReportId = ($r['report_id'] == $lostReportId) ? $foundReportId : $lostReportId;

        if ($r['report_id'] == $lostReportId) {
            $message = "Your lost item has been matched with a found item. (\"{$r['item_name']}\")";
        } else {
            $message = "Your found item has been matched with a lost item. (\"{$r['item_name']}\")";
        }

        $pdo->prepare(
            'INSERT INTO notifications (user_id, type, title, message, link_report_id)
             VALUES (?, "potential_match", "Match Found", ?, ?)'
        )->execute([$r['user_id'], $message, $otherReportId]);
    }
}

/**
 * Sends the required "verified by admin" notification to both sides
 * once a match has been confirmed.
 */
function notify_match_verified($pdo, $lostReportId, $foundReportId) {
    $stmt = $pdo->prepare('SELECT report_id, user_id, item_name FROM reports WHERE report_id IN (?, ?)');
    $stmt->execute([$lostReportId, $foundReportId]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reports as $r) {
        $otherReportId = ($r['report_id'] == $lostReportId) ? $foundReportId : $lostReportId;

        if ($r['report_id'] == $lostReportId) {
            $message = 'The match for your lost item has been verified by the admin.';
        } else {
            $message = 'The match for your found item has been verified by the admin.';
        }

        $pdo->prepare(
            'INSERT INTO notifications (user_id, type, title, message, link_report_id)
             VALUES (?, "match_approved", "Match Verified", ?, ?)'
        )->execute([$r['user_id'], $message, $otherReportId]);
    }
}

/**
 * Sends a notification to both sides when admin rejects a proposed
 * match - lets them know it wasn't confirmed and their report is
 * still open.
 */
function notify_match_rejected($pdo, $lostReportId, $foundReportId, $reason = '') {
    $stmt = $pdo->prepare('SELECT report_id, user_id, item_name FROM reports WHERE report_id IN (?, ?)');
    $stmt->execute([$lostReportId, $foundReportId]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reports as $r) {
        if ($r['report_id'] == $lostReportId) {
            $message = 'Your item match has been rejected by the admin.';
        } else {
            $message = 'The match for your found item has been rejected by the admin.';
        }

        if ($reason !== '') {
            $message .= ' Reason: ' . $reason;
        }

        $pdo->prepare(
            'INSERT INTO notifications (user_id, type, title, message, link_report_id)
             VALUES (?, "system", "Match Rejected", ?, ?)'
        )->execute([$r['user_id'], $message, $r['report_id']]);
    }
}
