<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'category_icon.php';
requireLogin();
if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit;
}
$uid = $_SESSION['user_id'];
$reportId = (int)($_GET['id'] ?? $_POST['report_id'] ?? 0);

$categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll(PDO::FETCH_ASSOC);

// Load the report, confirming it belongs to this user
$stmt = $pdo->prepare('SELECT * FROM reports WHERE report_id = ? AND user_id = ?');
$stmt->execute([$reportId, $uid]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';

if (!$report) {
    $pageTitle = 'Edit Report';
    $activePage = 'reports';
    require 'sidebar.php';
    echo '<h1>Report not found</h1><p class="page-sub">This report does not exist or does not belong to you.</p>';
    echo '<a href="my_reports.php" class="btn btn-navy">Back to My Reports</a>';
    require 'sidebar_footer.php';
    exit;
}

if ($report['status'] === 'matched') {
    $pageTitle = 'Edit Report';
    $activePage = 'reports';
    require 'sidebar.php';
    echo '<h1>Cannot Edit</h1><p class="page-sub">This report has already been matched and can no longer be edited.</p>';
    echo '<a href="my_reports.php" class="btn btn-navy">Back to My Reports</a>';
    require 'sidebar_footer.php';
    exit;
}

$type = $report['type']; // 'lost' or 'found'

// Pre-fill from the existing report
$old = [
    'item_name'        => $report['item_name'],
    'category_id'      => $report['category_id'],
    'description'      => $report['description'],
    'color'             => $report['color'] ?? '',
    'brand'             => $report['brand'] ?? '',
    'reward_amount'     => $report['reward_amount'] ?? '',
    'condition_status'  => $report['condition_status'] ?? 'new',
    'location'          => $report['location'] ?? '',
    'notes'             => $report['notes'] ?? '',
    'date_field'        => $report['date_reported'] ?? '',
];
$isPublic = (int)($report['is_public'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old['item_name']       = trim($_POST['item_name'] ?? '');
    $old['category_id']     = $_POST['category_id'] ?? '';
    $old['description']     = trim($_POST['description'] ?? '');
    $old['color']           = trim($_POST['color'] ?? '');
    $old['brand']           = trim($_POST['brand'] ?? '');
    $old['condition_status'] = $_POST['condition_status'] ?? 'new';
    $old['location']        = trim($_POST['location'] ?? '');
    $old['date_field']      = trim($_POST['date_field'] ?? '');

    if ($type === 'lost') {
        $old['reward_amount'] = trim($_POST['reward_amount'] ?? '');
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
    } else {
        $old['notes'] = trim($_POST['notes'] ?? '');
    }

    $validConditions = ['new', 'good', 'fair', 'poor'];

    if ($old['item_name'] === '') {
        $error = 'Please enter the item name.';
    } elseif ($old['category_id'] === '') {
        $error = 'Please choose a category.';
    } elseif ($old['description'] === '') {
        $error = 'Please describe the item.';
    } elseif ($old['date_field'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['date_field'])) {
        $error = 'Please enter a valid date.';
    } elseif ($old['date_field'] > date('Y-m-d')) {
        $error = 'Date cannot be in the future.';
    } elseif (!in_array($old['condition_status'], $validConditions, true)) {
        $error = 'Please choose a valid condition.';
    } elseif ($old['location'] === '') {
        $error = 'Please enter a location.';
    } elseif ($type === 'lost' && $old['reward_amount'] !== '' && !is_numeric($old['reward_amount'])) {
        $error = 'Reward must be a number.';
    }

    // Optional new photo
    $photoData = $report['photo_data'];
    $photoType = $report['photo_type'];
    if ($error === '' && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $mtype = mime_content_type($_FILES['photo']['tmp_name']);
        if (!in_array($mtype, $allowed, true)) {
            $error = 'Photo must be a JPG, PNG, or WEBP image.';
        } elseif ($_FILES['photo']['size'] > 10 * 1024 * 1024) {
            $error = 'Photo must be under 10MB.';
        } else {
            $photoData = file_get_contents($_FILES['photo']['tmp_name']);
            $photoType = $mtype;
        }
    }

    if ($error === '') {
        if ($type === 'lost') {
            $stmt = $pdo->prepare(
                'UPDATE reports SET
                    item_name = ?, category_id = ?, description = ?, color = ?, brand = ?,
                    reward_amount = ?, condition_status = ?, location = ?, date_reported = ?,
                    is_public = ?, photo_data = ?, photo_type = ?
                 WHERE report_id = ? AND user_id = ?'
            );
            $stmt->execute([
                $old['item_name'],
                $old['category_id'],
                $old['description'],
                $old['color'] !== '' ? $old['color'] : null,
                $old['brand'] !== '' ? $old['brand'] : null,
                $old['reward_amount'] !== '' ? $old['reward_amount'] : null,
                $old['condition_status'],
                $old['location'],
                $old['date_field'],
                $isPublic,
                $photoData,
                $photoType,
                $reportId,
                $uid,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE reports SET
                    item_name = ?, category_id = ?, description = ?, condition_status = ?,
                    location = ?, color = ?, brand = ?, notes = ?, date_reported = ?,
                    photo_data = ?, photo_type = ?
                 WHERE report_id = ? AND user_id = ?'
            );
            $stmt->execute([
                $old['item_name'],
                $old['category_id'],
                $old['description'],
                $old['condition_status'],
                $old['location'],
                $old['color'] !== '' ? $old['color'] : null,
                $old['brand'] !== '' ? $old['brand'] : null,
                $old['notes'] !== '' ? $old['notes'] : null,
                $old['date_field'],
                $photoData,
                $photoType,
                $reportId,
                $uid,
            ]);
        }

        header('Location: my_reports.php?updated=1');
        exit;
    }
}

$pageTitle  = 'Edit Report';
$activePage = 'reports';
require 'sidebar.php';
?>

<h1>Edit <?php echo $type === 'lost' ? 'Lost' : 'Found'; ?> Report</h1>
<p class="page-sub">Update the details of your report below.</p>

<?php if ($error): ?>
  <div class="form-alert error" style="max-width:700px;"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST" action="edit_report.php?id=<?php echo $reportId; ?>" enctype="multipart/form-data">
  <input type="hidden" name="report_id" value="<?php echo $reportId; ?>">
  <div class="form-grid">

    <div class="form-card">
      <h2>&#9432; Item Essentials</h2>

      <div class="field-row">
        <div class="field">
          <label for="item_name">Item Name</label>
          <input type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($old['item_name']); ?>">
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
        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($old['description']); ?></textarea>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="date_field"><?php echo $type === 'lost' ? 'Date Lost' : 'Found Date'; ?></label>
          <input type="date" id="date_field" name="date_field" value="<?php echo htmlspecialchars($old['date_field']); ?>" max="<?php echo date('Y-m-d'); ?>">
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
        <label for="location"><?php echo $type === 'lost' ? 'Last Seen Location' : 'Found Location'; ?> (Building/Room)</label>
        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($old['location']); ?>">
      </div>

      <div class="field-row three">
        <div class="field">
          <label for="color">Color</label>
          <input type="text" id="color" name="color" value="<?php echo htmlspecialchars($old['color']); ?>">
        </div>
        <div class="field">
          <label for="brand">Brand</label>
          <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($old['brand']); ?>">
        </div>
        <?php if ($type === 'lost'): ?>
          <div class="field">
            <label for="reward_amount">Reward (Optional)</label>
            <input type="text" id="reward_amount" name="reward_amount" value="<?php echo htmlspecialchars($old['reward_amount']); ?>">
          </div>
        <?php else: ?>
          <div class="field">
            <label for="notes">Additional Notes (Optional)</label>
            <input type="text" id="notes" name="notes" value="<?php echo htmlspecialchars($old['notes']); ?>">
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <div class="form-card">
        <h2>&#128247; Visual Proof</h2>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">Upload a new photo to replace the current one (optional).</p>

        <label class="upload-zone" for="photo">
          <span class="upload-icon">&#9729;</span>
          <strong>Drag and drop images</strong>
          or click to browse files
        </label>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewPhoto(this)">

        <?php if ($report['photo_data']): ?>
          <img src="serve_photo.php?id=<?php echo $reportId; ?>" style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;margin-top:12px;">
        <?php endif; ?>

        <div class="photo-slots">
          <label for="photo" class="photo-slot"><img id="photoPreview" alt=""></label>
          <span class="photo-slot disabled">&#128247;</span>
          <span class="photo-slot disabled">&#128247;</span>
        </div>
      </div>

      <?php if ($type === 'lost'): ?>
        <div class="form-card" style="margin-top:16px;">
          <h2>&#128737; Visibility</h2>
          <div class="toggle-row">
            <div>
              <div class="toggle-label">Public Visibility</div>
              <div class="toggle-desc">Allow others to see details</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="is_public" <?php echo $isPublic ? 'checked' : ''; ?>>
              <span class="switch-track"></span>
            </label>
          </div>
        </div>
      <?php endif; ?>

      <div class="form-actions">
        <button type="submit" class="btn btn-navy btn-block">&#9654; Save Changes</button>
        <a href="my_reports.php" class="btn btn-outline btn-block">Cancel</a>
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