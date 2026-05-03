<?php
require_once '../includes/db.php';
require_once '../includes/user_auth.php';
require_once 'layout.php';

$user     = auth_check();
$db       = get_db();
$business = user_get_business($db, $user['id']);

if (!$business) {
    dash_head('Photos', 'photos');
    echo '<div class="dash-alert dash-alert-amber">You need an approved business before managing photos. <a href="/dashboard/claim.php" style="font-weight:700;color:#92400e">Claim a listing</a>.</div>';
    dash_foot();
    exit;
}

$error   = '';
$success = '';

// Delete photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_photo'])) {
    $photo_id = (int)$_POST['delete_photo'];
    $stmt = $db->prepare('SELECT path FROM business_photos WHERE id=? AND business_id=?');
    $stmt->execute([$photo_id, $business['id']]);
    $photo = $stmt->fetch();
    if ($photo) {
        $full = '/var/www/serviceordered.com/public' . $photo['path'];
        if (file_exists($full)) unlink($full);
        $db->prepare('DELETE FROM business_photos WHERE id=?')->execute([$photo_id]);
        $success = 'Photo deleted.';
    }
}

// Upload photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM business_photos WHERE business_id=?');
    $stmt->execute([$business['id']]);
    if ($stmt->fetchColumn() >= 20) {
        $error = 'Maximum 20 photos allowed.';
    } else {
        $file     = $_FILES['photo'];
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
        $mime     = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed)) {
            $error = 'Only JPEG, PNG, and WebP images are allowed.';
        } elseif ($file['size'] > 8 * 1024 * 1024) {
            $error = 'File must be under 8 MB.';
        } else {
            $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
            $name = 'biz_' . $business['id'] . '_' . uniqid() . '.' . $ext;
            $dir  = '/var/www/serviceordered.com/public/uploads/businesses/';
            if (move_uploaded_file($file['tmp_name'], $dir . $name)) {
                $path = '/uploads/businesses/' . $name;
                $db->prepare('INSERT INTO business_photos (business_id, path) VALUES (?,?)')->execute([$business['id'], $path]);
                $success = 'Photo uploaded.';
            } else {
                $error = 'Upload failed — check server permissions.';
            }
        }
    }
}

$stmt = $db->prepare('SELECT * FROM business_photos WHERE business_id=? ORDER BY id');
$stmt->execute([$business['id']]);
$photos = $stmt->fetchAll();

dash_head('Photos', 'photos');
?>

<div class="dash-page-title">Photos</div>
<p class="dash-page-sub">Add photos of your work to attract more customers.</p>

<?php if ($success): ?>
<div class="dash-alert dash-alert-green"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="dash-alert dash-alert-red"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="dash-card">
    <div class="dash-card-title">Upload Photo</div>
    <form method="POST" enctype="multipart/form-data" id="upload-form">
        <label class="upload-zone" for="photo-input" id="upload-zone">
            <input type="file" name="photo" id="photo-input" accept="image/jpeg,image/png,image/webp">
            <div class="upload-zone-text">
                <div style="font-size:2rem;margin-bottom:.5rem">📷</div>
                <div><strong>Click to upload</strong> or drag and drop</div>
                <div style="margin-top:.35rem;font-size:.8rem">JPEG, PNG, WebP — max 8 MB</div>
            </div>
        </label>
    </form>
</div>

<?php if ($photos): ?>
<div class="dash-card">
    <div class="dash-card-title">Your Photos (<?= count($photos) ?>/20)</div>
    <div class="photo-grid">
        <?php foreach ($photos as $p): ?>
        <div class="photo-item">
            <img src="<?= htmlspecialchars($p['path']) ?>" alt="Business photo" loading="lazy">
            <form method="POST" onsubmit="return confirm('Delete this photo?')">
                <input type="hidden" name="delete_photo" value="<?= $p['id'] ?>">
                <button type="submit" class="photo-delete" title="Delete">✕</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="dash-empty">
    <div style="font-size:3rem">📷</div>
    <h3>No photos yet</h3>
    <p>Upload photos of your completed work to build trust with customers.</p>
</div>
<?php endif; ?>

<script>
const input = document.getElementById('photo-input');
const zone  = document.getElementById('upload-zone');

input.addEventListener('change', () => {
    if (input.files.length) document.getElementById('upload-form').submit();
});

zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.borderColor = 'var(--blue)'; });
zone.addEventListener('dragleave', () => { zone.style.borderColor = ''; });
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.style.borderColor = '';
    if (e.dataTransfer.files.length) {
        const dt = new DataTransfer();
        dt.items.add(e.dataTransfer.files[0]);
        input.files = dt.files;
        document.getElementById('upload-form').submit();
    }
});
</script>

<?php dash_foot(); ?>
