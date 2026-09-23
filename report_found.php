<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'match_helper.php';
requireLogin();

if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit;
}

$categories = $pdo->query(
    'SELECT category_id, category_name FROM categories ORDER BY category_name'
)->fetchAll(PDO::FETCH_ASSOC);

$error = '';

$old = [
    'item_name' => '',
    'category_id' => '',
    'description' => '',
    'found_date' => '',
    'found_time' => '',
    'condition_status' => 'new',
    'location' => '',
    'color' => '',
    'brand' => '',
    'notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old['item_name']        = trim($_POST['item_name'] ?? '');
    $old['category_id']      = $_POST['category_id'] ?? '';
    $old['description']      = trim($_POST['description'] ?? '');
    $old['found_date']       = trim($_POST['found_date'] ?? '');
    $old['found_time']       = trim($_POST['found_time'] ?? '');
    $old['condition_status'] = $_POST['condition_status'] ?? 'new';
    $old['location']         = trim($_POST['location'] ?? '');
    $old['color']            = trim($_POST['color'] ?? '');
    $old['brand']            = trim($_POST['brand'] ?? '');
    $old['notes']            = trim($_POST['notes'] ?? '');

    $validConditions = ['new', 'good', 'fair', 'poor'];

    /* Validation */

    if ($old['item_name'] === '') {
        $error = 'Please enter the item name.';
    } elseif ($old['category_id'] === '') {
        $error = 'Please choose a category.';
    } elseif ($old['description'] === '') {
        $error = 'Please describe the item.';
    } elseif (
        $old['found_date'] === '' ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['found_date'])
    ) {
        $error = 'Please enter a valid found date.';
    } elseif ($old['found_date'] > date('Y-m-d')) {
        $error = 'Found date cannot be in the future.';
    } elseif (
        $old['found_time'] !== '' &&
        !preg_match('/^\d{2}:\d{2}$/', $old['found_time'])
    ) {
        $error = 'Please enter a valid time.';
    } elseif (!in_array($old['condition_status'], $validConditions, true)) {
        $error = 'Please choose a valid condition.';
    } elseif ($old['location'] === '') {
        $error = 'Please enter where the item was found.';
    }

    /* Optional photo upload */

    $photoData = null;
    $photoType = null;

    if (
        $error === '' &&
        isset($_FILES['photo']) &&
        $_FILES['photo']['error'] === UPLOAD_ERR_OK
    ) {

        $allowed = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        $type = mime_content_type($_FILES['photo']['tmp_name']);

        if (!in_array($type, $allowed, true)) {
            $error = 'Photo must be a JPG, PNG, or WEBP image.';
        } elseif ($_FILES['photo']['size'] > 10 * 1024 * 1024) {
            $error = 'Photo must be under 10MB.';
        } else {
            $photoData = file_get_contents($_FILES['photo']['tmp_name']);
            $photoType = $type;
        }
    }

    /* Save report */

    if ($error === '') {

        $stmt = $pdo->prepare(
            'INSERT INTO reports
                (
                    user_id,
                    type,
                    item_name,
                    category_id,
                    description,
                    condition_status,
                    location,
                    color,
                    brand,
                    notes,
                    date_reported,
                    time_reported,
                    status,
                    is_public,
                    photo_data,
                    photo_type
                )
             VALUES
                (
                    ?,
                    "found",
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    "open",
                    1,
                    ?,
                    ?
                )'
        );

        $stmt->execute([
            $_SESSION['user_id'],
            $old['item_name'],
            $old['category_id'],
            $old['description'],
            $old['condition_status'],
            $old['location'],
            $old['color'] !== '' ? $old['color'] : null,
            $old['brand'] !== '' ? $old['brand'] : null,
            $old['notes'] !== '' ? $old['notes'] : null,
            $old['found_date'],
            $old['found_time'] !== '' ? $old['found_time'] : null,
            $photoData,
            $photoType
        ]);

        $newReportId = $pdo->lastInsertId();

        /* Automatically check for a matching lost report */

        $bestMatch = find_best_match_for_report($pdo, $newReportId);

        if ($bestMatch) {
            create_match_with_notification(
                $pdo,
                $bestMatch['report_id'],
                $newReportId
            );
        }

        header('Location: user_dashboard.php?reported=found');
        exit;
    }
}

$pageTitle  = 'Report Found Item';
$activePage = 'post';

require 'sidebar.php';
?>

<h1>Report Found Item</h1>

<p class="page-sub">
    Help reunite a student with their lost property by providing accurate details.
</p>

<?php if ($error): ?>
    <div class="form-alert error" style="max-width:700px;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<form method="POST" action="report_found.php" enctype="multipart/form-data">

    <div class="form-grid">

        <!-- LEFT SIDE -->

        <div class="form-card">

            <div class="field-row">

                <div class="field">
                    <label for="item_name">Item Name</label>

                    <input
                        type="text"
                        id="item_name"
                        name="item_name"
                        placeholder="e.g. Silver MacBook Air"
                        value="<?php echo htmlspecialchars($old['item_name']); ?>"
                    >
                </div>

                <div class="field">
                    <label for="category_id">Category</label>

                    <select id="category_id" name="category_id">

                        <option value="">Select category</option>

                        <?php foreach ($categories as $cat): ?>

                            <option
                                value="<?php echo $cat['category_id']; ?>"
                                <?php echo ($old['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

            </div>


            <!-- DESCRIPTION -->

            <div class="field">

                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    placeholder="Describe the item, including any distinguishing features (stickers, scratches, colors)..."
                ><?php echo htmlspecialchars($old['description']); ?></textarea>

            </div>


            <!-- DATE + TIME + CONDITION -->

            <div class="field-row three">

                <div class="field">

                    <label for="found_date">Found Date</label>

                    <input
                        type="date"
                        id="found_date"
                        name="found_date"
                        value="<?php echo htmlspecialchars($old['found_date']); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                    >

                </div>


                <div class="field">

                    <label for="found_time">Time Found (Optional)</label>

                    <input
                        type="time"
                        id="found_time"
                        name="found_time"
                        value="<?php echo htmlspecialchars($old['found_time']); ?>"
                    >

                </div>


                <div class="field">

                    <label for="condition_status">Condition</label>

                    <select id="condition_status" name="condition_status">

                        <?php foreach (
                            [
                                'new' => 'New',
                                'good' => 'Good',
                                'fair' => 'Fair',
                                'poor' => 'Poor'
                            ] as $val => $label
                        ): ?>

                            <option
                                value="<?php echo $val; ?>"
                                <?php echo $old['condition_status'] === $val ? 'selected' : ''; ?>
                            >
                                <?php echo $label; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- LOCATION -->

            <div class="field">

                <label for="location">
                    Found Location (Building/Room)
                </label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    placeholder="e.g. Science Building, Room 402"
                    value="<?php echo htmlspecialchars($old['location']); ?>"
                >

            </div>


            <!-- COLOR + BRAND + NOTES -->

            <div class="field-row three">

                <div class="field">

                    <label for="color">Color</label>

                    <input
                        type="text"
                        id="color"
                        name="color"
                        placeholder="e.g. Space Gray"
                        value="<?php echo htmlspecialchars($old['color']); ?>"
                    >

                </div>


                <div class="field">

                    <label for="brand">Brand</label>

                    <input
                        type="text"
                        id="brand"
                        name="brand"
                        placeholder="e.g. Apple"
                        value="<?php echo htmlspecialchars($old['brand']); ?>"
                    >

                </div>


                <div class="field">

                    <label for="notes">
                        Additional Notes (Optional)
                    </label>

                    <input
                        type="text"
                        id="notes"
                        name="notes"
                        placeholder="Any extra information for the office staff..."
                        value="<?php echo htmlspecialchars($old['notes']); ?>"
                    >

                </div>

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="btn btn-navy btn-block"
            >
                Submit Found Report
            </button>

        </div>


        <!-- RIGHT SIDE -->

        <div>

            <!-- PHOTO UPLOAD -->
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


            <!-- PRIVACY -->

            <div class="notice-card green">

                <strong>
                    &#9989; Privacy &amp; Security
                </strong>

                Found items are kept securely at the central office.
                Your contact information is only shared with university
                administrators for verification.

            </div>


            <!-- TIPS -->

            <div class="form-card tips-card">

                <h3>Quick Tips</h3>

                <ul>

                    <li>
                        Be specific about the location
                    </li>

                    <li>
                        Mention unique stickers or marks
                    </li>

                    <li>
                        Note the exact time found
                    </li>

                </ul>

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