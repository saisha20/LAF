<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'match_helper.php';
requireLogin();


if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit;
}

$categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$old = ['item_name' => '', 'category_id' => '', 'description' => '', 'color' => '', 'brand' => '', 'reward_amount' => '', 'date_lost' => '', 'time_lost' => '', 'condition_status' => 'new', 'location' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['item_name']     = trim($_POST['item_name'] ?? '');
    $old['category_id']   = $_POST['category_id'] ?? '';
    $old['description']   = trim($_POST['description'] ?? '');
    $old['color']         = trim($_POST['color'] ?? '');
    $old['brand']         = trim($_POST['brand'] ?? '');
    $old['reward_amount'] = trim($_POST['reward_amount'] ?? '');
    $old['date_lost']        = trim($_POST['date_lost'] ?? '');
    $old['time_lost']        = trim($_POST['time_lost'] ?? '');
    $old['condition_status'] = $_POST['condition_status'] ?? 'new';
    $old['location']         = trim($_POST['location'] ?? '');
    $isPublic             = isset($_POST['is_public']) ? 1 : 0;

    if ($old['item_name'] === '') {
        $error = 'Please enter the item name.';
    } elseif ($old['category_id'] === '') {
        $error = 'Please choose a category.';
    } elseif ($old['description'] === '') {
        $error = 'Please describe the item.';
    } elseif ($old['date_lost'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['date_lost'])) {
        $error = 'Please enter a valid date lost.';
    } elseif ($old['date_lost'] > date('Y-m-d')) {
        $error = 'Date lost cannot be in the future.';
    } elseif ($old['time_lost'] !== '' && !preg_match('/^\d{2}:\d{2}$/', $old['time_lost'])) {
        $error = 'Please enter a valid time.';
    } elseif (!in_array($old['condition_status'], ['new', 'good', 'fair', 'poor'], true)) {
        $error = 'Please choose a valid condition.';
    } elseif ($old['location'] === '') {
        $error = 'Please enter where you last saw the item.';
    } elseif ($old['reward_amount'] !== '' && !is_numeric($old['reward_amount'])) {
        $error = 'Reward must be a number.';
    }

    // Optional photo upload -> stored directly in the database
    $photoData = null;
    $photoType = null;
    if ($error === '' && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $type = mime_content_type($_FILES['photo']['tmp_name']);
        if (!in_array($type, $allowed, true)) {
            $error = 'Photo must be a JPG, PNG, or WEBP image.';
        } elseif ($_FILES['photo']['size'] > 10 * 1024 * 1024) {
    $error = 'Photo must be under 10MB.';
}
 else {
            $photoData = file_get_contents($_FILES['photo']['tmp_name']);
            $photoType = $type;
        }
    }

    if ($error === '') {
        $stmt = $pdo->prepare(
            'INSERT INTO reports
                (user_id, type, item_name, category_id, description, color, brand, reward_amount, condition_status,
                 location, date_reported, time_reported, status, is_public, photo_data, photo_type)
             VALUES
                (?, "lost", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "open", ?, ?, ?)'
        );
        $stmt->execute([
            $_SESSION['user_id'],
            $old['item_name'],
            $old['category_id'],
            $old['description'],
            $old['color'] !== '' ? $old['color'] : null,
            $old['brand'] !== '' ? $old['brand'] : null,
            $old['reward_amount'] !== '' ? $old['reward_amount'] : null,
            $old['condition_status'],
            $old['location'],
            $old['date_lost'],
            $old['time_lost'] !== '' ? $old['time_lost'] : null,
            $isPublic,
            $photoData,
            $photoType,
        ]);

        $newReportId = $pdo->lastInsertId();

        // Automatically check if this lost report matches an existing
        // open found report - if so, create the match and notify both sides.
        $bestMatch = find_best_match_for_report($pdo, $newReportId);
        if ($bestMatch) {
            create_match_with_notification($pdo, $newReportId, $bestMatch['report_id']);
        }

        header('Location: user_dashboard.php?reported=lost');
        exit;
    }
}

$pageTitle  = 'Report Lost Item';
$activePage = 'post';
require 'sidebar.php';
?>

<h1>Report Lost Item</h1>
<p class="page-sub">Help the community find your belongings. Provide as much detail as possible to increase the chances of a successful recovery.</p>

<?php if ($error): ?>
  <div class="form-alert error" style="max-width:700px;"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST" action="report_lost.php" enctype="multipart/form-data">
  <div class="form-grid">

    <div class="form-card">
      <h2>&#9432; Item Essentials</h2>

      <div class="field-row">
        <div class="field">
          <label for="item_name">Item Name</label>
          <input type="text" id="item_name" name="item_name" placeholder="e.g. MacBook Air, Blue Backpack" value="<?php echo htmlspecialchars($old['item_name']); ?>">
        </div>
        <div class="field">
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id">
            <option value="">Select category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['category_id']; ?>" <?php echo ($old['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['category_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4" placeholder="Describe unique markings, scratches, or contents inside..."><?php echo htmlspecialchars($old['description']); ?></textarea>
      </div>

      <div class="field-row three">
        <div class="field">
          <label for="date_lost">Date Lost</label>
          <input type="date" id="date_lost" name="date_lost" value="<?php echo htmlspecialchars($old['date_lost']); ?>" max="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="field">
          <label for="time_lost">Time Lost (Optional)</label>
          <input type="time" id="time_lost" name="time_lost" value="<?php echo htmlspecialchars($old['time_lost']); ?>">
        </div>
        <div class="field">
          <label for="condition_status">Condition</label>
          <select id="condition_status" name="condition_status">
            <?php foreach (['new' => 'New', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $val => $label): ?>
              <option value="<?php echo $val; ?>" <?php echo $old['condition_status'] === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field">
        <label for="location">Last Seen Location (Building/Room)</label>
        <input type="text" id="location" name="location" placeholder="e.g. Library Level 3, Study Room C" value="<?php echo htmlspecialchars($old['location']); ?>">
      </div>

      <div class="field-row three">
        <div class="field">
          <label for="color">Color</label>
          <input type="text" id="color" name="color" placeholder="e.g. Space Gray" value="<?php echo htmlspecialchars($old['color']); ?>">
        </div>
        <div class="field">
          <label for="brand">Brand</label>
          <input type="text" id="brand" name="brand" placeholder="e.g. Apple" value="<?php echo htmlspecialchars($old['brand']); ?>">
        </div>
        <div class="field">
          <label for="reward_amount">Reward (Optional)</label>
          <input type="text" id="reward_amount" name="reward_amount" placeholder="Rs 0.00" value="<?php echo htmlspecialchars($old['reward_amount']); ?>">
        </div>
      </div>

      <div class="form-actions" style="margin-top:16px;">
         <button
                type="submit"
                class="btn btn-navy btn-block"
            >
                Submit Lost Report
            </button>
      </div>
    </div>

    <div>
      <div class="form-card">
        <h2>&#128247; Visual Proof</h2>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">Upload a photo of the item or a similar stock image to help identification.</p>

        <label class="upload-zone" for="photo">
          <span class="upload-icon">&#9729;</span>
          <strong>Drag and drop images</strong>
          or click to browse files
        </label>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewPhoto(this)">

        <div class="photo-slots">
          <label for="photo" class="photo-slot"><img id="photoPreview" alt=""></label>
          <span class="photo-slot disabled">&#128247;</span>
          <span class="photo-slot disabled">&#128247;</span>
        </div>
      </div>

      <div class="form-card" style="margin-top:16px; background:#eafaf0; border:1px solid #cdeedb;">
        <h2 style="display:flex;align-items:center;gap:6px;">&#9989; Privacy &amp; Security</h2>
        <p style="font-size:13px;color:#2f6e4e;margin:0;">
          Your contact information is only shared with the person you approve as a match. Lost item details are kept secure until a claim is verified.
        </p>
      </div>

      <div class="form-card" style="margin-top:16px;">
        <h2>Quick Tips</h2>
        <ul style="list-style:none;padding:0;margin:0;font-size:13px;color:var(--text-muted);">
          <li style="margin-bottom:8px;">&#10003; Be specific about the location</li>
          <li style="margin-bottom:8px;">&#10003; Mention unique stickers or marks</li>
          <li>&#10003; Note the approximate time lost</li>
        </ul>
      </div>

      <div class="form-card" style="margin-top:16px;">
        <h2>&#128737; Visibility</h2>
        <div class="toggle-row">
          <div>
            <div class="toggle-label">Public Visibility</div>
            <div class="toggle-desc">Allow others to see details</div>
          </div>
          <label class="switch">
            <input type="checkbox" name="is_public" checked>
            <span class="switch-track"></span>
          </label>
        </div>
        <div class="lock-note">
          &#128274; Your personal contact details are hidden until you approve a claim request.
        </div>
      </div>
    </div>

  </div>
</form>

<script>
function previewPhoto(input) {
  const preview = document.getElementById('photoPreview');
  if (input.files && input.files[0]) {
    preview.src = URL.createObjectURL(input.files[0]);
    preview.style.display = 'block';
  }
}
</script>