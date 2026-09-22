<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'match_helper.php'; 
require 'admin_header.php';  // <-- add this

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $matchId = isset($_POST['match_id']) ? (int) $_POST['match_id'] : 0;
    $action  = $_POST['action'] ?? '';
    $reason  = trim($_POST['rejection_reason'] ?? '');

    if ($matchId > 0 && in_array($action, ['verify', 'reject'], true)) {

        // fetch the report ids first so we can notify + update reports afterward
        $stmt = $pdo->prepare('SELECT lost_report_id, found_report_id FROM matches WHERE match_id = ? AND status = "pending"');
        $stmt->execute([$matchId]);
        $m = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($m) {
            if ($action === 'verify') {

                $pdo->prepare("
                    UPDATE matches
                    SET status = 'verified', verified_by = ?, verified_at = NOW()
                    WHERE match_id = ? AND status = 'pending'
                ")->execute([$_SESSION['user_id'], $matchId]);

                $pdo->prepare('UPDATE reports SET status = "matched" WHERE report_id IN (?, ?)')
                    ->execute([$m['lost_report_id'], $m['found_report_id']]);

                notify_match_verified($pdo, $m['lost_report_id'], $m['found_report_id']);

            } else {

                $pdo->prepare("
                    UPDATE matches
                    SET status = 'rejected', verified_by = ?, verified_at = NOW(), rejection_reason = ?
                    WHERE match_id = ? AND status = 'pending'
                ")->execute([$_SESSION['user_id'], $reason !== '' ? $reason : null, $matchId]);

                notify_match_rejected($pdo, $m['lost_report_id'], $m['found_report_id'], $reason);
            }
        }
    }

    header('Location: admin_verifications.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Get pending matches
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query("
    SELECT
        m.match_id,
        m.lost_report_id,
        m.found_report_id,
        m.status,
        m.created_at,

       lost.item_name AS lost_item,
lost.description AS lost_description,
lost.color AS lost_color,
lost.brand AS lost_brand,
lost.location AS lost_location,
lost.date_reported AS lost_date,
lost.photo_data AS lost_photo_data,
lost.photo_type AS lost_photo_type,

found.item_name AS found_item,
found.description AS found_description,
found.color AS found_color,
found.brand AS found_brand,
found.location AS found_location,
found.date_reported AS found_date,
found.photo_data AS found_photo_data,
found.photo_type AS found_photo_type,

        lost_user.full_name AS lost_user_name,
        found_user.full_name AS found_user_name

    FROM matches m

    INNER JOIN reports lost
        ON m.lost_report_id = lost.report_id

    INNER JOIN reports found
        ON m.found_report_id = found.report_id

    LEFT JOIN users lost_user
        ON lost.user_id = lost_user.user_id

    LEFT JOIN users found_user
        ON found.user_id = found_user.user_id

    WHERE m.status = 'pending'
      AND m.claim_statement IS NOT NULL
      AND m.claim_statement != ''

    ORDER BY m.created_at DESC
");

$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Pending Verifications - Foundly</title>

<link rel="stylesheet" href="base.css?v=2">

<style>

.admin-container {
    width: 92%;
    max-width: 1000px;
    margin: 0px auto;
}

.back-link {
    display: inline-block;
    margin-bottom: 10px;
    color: #111b3a;
    text-decoration: none;
    font-weight: 600;
}

.page-description {
    color: #667085;
    margin-bottom:0px;
}


/* Match card */

.match-card {
    border: 1px solid #d9dee8;
    border-radius: 12px;
    padding: 10px;
    margin-bottom: 25px;
    background: #fff;
}


/* Reports */

.report-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.report-box {
    border: 1px solid #e1e5eb;
    border-radius: 10px;
    padding: 20px;
}

.report-box h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #111b3a;
}
.item-photo {
    width: 100%;
    height: 220px;
    margin-bottom: 18px;
    border: 1px solid #e1e5eb;
    border-radius: 8px;
    background: #f5f7fb;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.item-photo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.no-photo {
    width: 100%;
    height: 220px;
    margin-bottom: 18px;
    border: 1px solid #e1e5eb;
    border-radius: 8px;
    background: #f5f7fb;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #667085;
    font-size: 14px;
}
.report-label {
    font-size: 13px;
    color: #667085;
    margin-top: 12px;
    margin-bottom: 3px;
}

.report-value {
    color: #222;
}


/* Actions */

.match-actions {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e1e5eb;

    display: flex;
    gap: 12px;
}

.btn-verify {
    background: #1f7a4d;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-verify:hover {
    background: #17633e;
}

.btn-reject {
    background: #b42318;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-reject:hover {
    background: #8f1c13;
}


/* Empty state */

.empty-state {
    border: 1px solid #d9dee8;
    border-radius: 10px;
    padding: 40px;
    text-align: center;
    color: #667085;
}

.empty-state h2 {
    color: #111b3a;
}

@media (max-width: 800px) {

    .report-container {
        grid-template-columns: 1fr;
    }

    .match-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

}

</style>

<link rel="icon" type="image/png" href="image/foundly.png">
</head>
<body>
<!-- MAIN -->

<div class="admin-container">
    <h1>
        Pending Verifications
    </h1>


    <p class="page-description">
        Review possible lost and found item matches before approving or rejecting them.
    </p>



    <?php if (count($matches) === 0): ?>

        <div class="empty-state">

            <h2>
                No Pending Verifications
            </h2>

            <p>
                There are currently no matches waiting for admin review.
            </p>

        </div>


    <?php else: ?>


        <?php foreach ($matches as $match): ?>


            <div class="match-card">


                <!-- MATCH HEADER -->

                <div class="match-header">

                    <h2>
                        Match #<?php echo (int)$match['match_id']; ?>
                    </h2>

                    <span class="pending-badge">
                        Pending Review
                    </span>

                </div>



                <!-- REPORTS -->

                <div class="report-container">


                    <!-- LOST REPORT -->

                    <div class="report-box">

                        <h3>
                            Lost Item
                        </h3>
                        <?php if (!empty($match['lost_photo_data'])): ?>

    <div class="item-photo">
        <img
            src="data:<?= htmlspecialchars($match['lost_photo_type'] ?: 'image/jpeg') ?>;base64,<?= base64_encode($match['lost_photo_data']) ?>"
            alt="Lost item photo"
        >
    </div>

<?php else: ?>

    <div class="no-photo">
        No photo available
    </div>

<?php endif; ?>

                        <div class="report-label">
                            Item Name
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['lost_item']
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Reported By
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['lost_user_name'] ?? 'Unknown'
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Description
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['lost_description']
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Color
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['lost_color'] ?? 'Not specified'
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Brand
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['lost_brand'] ?? 'Not specified'
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Location
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['lost_location']
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Date Reported
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['lost_date']
                            );
                            ?>
                        </div>

                    </div>



                    <!-- FOUND REPORT -->

                    <div class="report-box">

                        <h3>
                            Found Item
                        </h3>
<?php if (!empty($match['found_photo_data'])): ?>

    <div class="item-photo">
        <img
            src="data:<?= htmlspecialchars($match['found_photo_type'] ?: 'image/jpeg') ?>;base64,<?= base64_encode($match['found_photo_data']) ?>"
            alt="Found item photo"
        >
    </div>

<?php else: ?>

    <div class="no-photo">
        No photo available
    </div>

<?php endif; ?>

                        <div class="report-label">
                            Item Name
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['found_item']
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Reported By
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['found_user_name'] ?? 'Unknown'
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Description
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['found_description']
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Color
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['found_color'] ?? 'Not specified'
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Brand
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['found_brand'] ?? 'Not specified'
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Location
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['found_location']
                            );
                            ?>
                        </div>


                        <div class="report-label">
                            Date Reported
                        </div>

                        <div class="report-value">
                            <?php
                            echo htmlspecialchars(
                                $match['found_date']
                            );
                            ?>
                        </div>

                    </div>


                </div>



                <!-- ACTIONS -->

                <div class="match-actions">


                    <!-- VERIFY -->

                    <form method="POST">

                        <input
                            type="hidden"
                            name="match_id"
                            value="<?php echo (int)$match['match_id']; ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="verify"
                        >

                        <button
                            type="submit"
                            class="btn-verify"
                            onclick="return confirm('Are you sure you want to verify this match?');"
                        >
                            ✓ Verify Match
                        </button>

                    </form>



                    <!-- REJECT -->

                    <form method="POST" class="reject-form" id="reject-form-<?php echo (int)$match['match_id']; ?>">

                        <input
                            type="hidden"
                            name="match_id"
                            value="<?php echo (int)$match['match_id']; ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="reject"
                        >

                        <input
                            type="hidden"
                            name="rejection_reason"
                            class="reject-reason-field"
                        >

                        <button
                            type="button"
                            class="btn-reject"
                            onclick="openRejectModal(<?php echo (int)$match['match_id']; ?>)"
                        >
                            ✕ Reject Match
                        </button>

                    </form>


                </div>


            </div>


        <?php endforeach; ?>


    <?php endif; ?>


</div>


<!-- Reject reason modal -->
<div id="reject-modal-overlay" class="reject-modal-overlay">
  <div class="reject-modal">
    <h3>Reject this match?</h3>
    <p>Let the user know why, so they understand what to do next.</p>
    <textarea id="reject-modal-textarea" rows="4" placeholder="e.g. Photos don't match, item description doesn't match, wrong location..."></textarea>
    <div class="reject-modal-actions">
      <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Cancel</button>
      <button type="button" class="btn-reject" onclick="confirmRejectModal()">Reject Match</button>
    </div>
  </div>
</div>

<style>
.reject-modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(16, 24, 40, 0.55);
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.reject-modal-overlay.open {
  display: flex;
}
.reject-modal {
  background: #fff;
  border-radius: 10px;
  padding: 24px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 10px 30px rgba(16, 24, 40, 0.25);
}
.reject-modal h3 {
  margin: 0 0 6px;
  font-size: 17px;
}
.reject-modal p {
  margin: 0 0 14px;
  font-size: 13px;
  color: #667085;
}
.reject-modal textarea {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #d0d5dd;
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 13.5px;
  font-family: inherit;
  resize: vertical;
}
.reject-modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 16px;
}
</style>

<script>
var rejectModalMatchId = null;

function openRejectModal(matchId) {
  rejectModalMatchId = matchId;
  document.getElementById('reject-modal-textarea').value = '';
  document.getElementById('reject-modal-overlay').classList.add('open');
  document.getElementById('reject-modal-textarea').focus();
}

function closeRejectModal() {
  document.getElementById('reject-modal-overlay').classList.remove('open');
  rejectModalMatchId = null;
}

function confirmRejectModal() {
  if (rejectModalMatchId === null) return;
  var form = document.getElementById('reject-form-' + rejectModalMatchId);
  var reason = document.getElementById('reject-modal-textarea').value.trim();
  form.querySelector('.reject-reason-field').value = reason;
  form.submit();
}

document.getElementById('reject-modal-overlay').addEventListener('click', function (e) {
  if (e.target === this) closeRejectModal();
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeRejectModal();
});
</script>
</body>

</html>