<?php
require_once '../includes/db.php';
require_once '../includes/user_auth.php';
require_once 'layout.php';

$user     = auth_check();
$db       = get_db();
$business = user_get_business($db, $user['id']);

if (!$business) {
    dash_head('Business Profile', 'profile');
    echo '<div class="dash-alert dash-alert-amber">You don\'t have an approved business yet. <a href="/dashboard/claim.php" style="font-weight:700;color:#92400e">Claim a listing</a> first.</div>';
    dash_foot();
    exit;
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'name'              => trim($_POST['name'] ?? ''),
        'description'       => trim($_POST['description'] ?? ''),
        'phone'             => trim($_POST['phone'] ?? ''),
        'website'           => trim($_POST['website'] ?? ''),
        'address'           => trim($_POST['address'] ?? ''),
        'years_in_business' => (int)($_POST['years_in_business'] ?? 0) ?: null,
        'license_number'    => trim($_POST['license_number'] ?? ''),
        'insured'           => isset($_POST['insured']) ? 1 : 0,
        'service_area'      => trim($_POST['service_area'] ?? ''),
    ];

    if (empty($fields['name'])) {
        $error = 'Business name is required.';
    } else {
        $stmt = $db->prepare('
            UPDATE businesses
            SET name=?, description=?, phone=?, website=?, address=?,
                years_in_business=?, license_number=?, insured=?, service_area=?
            WHERE id=?
        ');
        $stmt->execute([
            $fields['name'], $fields['description'], $fields['phone'],
            $fields['website'], $fields['address'], $fields['years_in_business'],
            $fields['license_number'], $fields['insured'], $fields['service_area'],
            $business['id'],
        ]);
        $success  = 'Profile updated successfully.';
        $business = array_merge($business, $fields);
    }
}

dash_head('Business Profile', 'profile');
?>

<div class="dash-page-title">Business Info</div>
<p class="dash-page-sub">Update your public business profile.</p>

<?php if ($success): ?>
<div class="dash-alert dash-alert-green"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="dash-alert dash-alert-red"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="dash-card">
    <div class="dash-card-title">Basic Information</div>
    <form method="POST">
        <div class="field-group">
            <label>Business Name *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($business['name']) ?>" required>
        </div>
        <div class="field-group">
            <label>Description</label>
            <textarea name="description" rows="4"><?= htmlspecialchars($business['description'] ?? '') ?></textarea>
            <div class="field-hint">Tell customers what makes your business great.</div>
        </div>
        <div class="field-row">
            <div class="field-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($business['phone'] ?? '') ?>">
            </div>
            <div class="field-group">
                <label>Website</label>
                <input type="url" name="website" value="<?= htmlspecialchars($business['website'] ?? '') ?>" placeholder="https://example.com">
            </div>
        </div>
        <div class="field-group">
            <label>Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($business['address'] ?? '') ?>">
        </div>
        <div class="field-group">
            <label>Service Area</label>
            <input type="text" name="service_area" value="<?= htmlspecialchars($business['service_area'] ?? '') ?>" placeholder="e.g. Greater Phoenix, AZ within 30 miles">
            <div class="field-hint">Describe the cities or radius you serve.</div>
        </div>

        <div class="dash-card-title" style="margin-top:2rem">Credentials</div>
        <div class="field-row">
            <div class="field-group">
                <label>Years in Business</label>
                <input type="number" name="years_in_business" min="0" max="200" value="<?= htmlspecialchars($business['years_in_business'] ?? '') ?>">
            </div>
            <div class="field-group">
                <label>License Number</label>
                <input type="text" name="license_number" value="<?= htmlspecialchars($business['license_number'] ?? '') ?>">
            </div>
        </div>
        <div class="field-group">
            <div class="toggle-wrap">
                <input type="checkbox" name="insured" id="insured" <?= !empty($business['insured']) ? 'checked' : '' ?>>
                <label for="insured" style="margin:0;font-size:.9rem;font-weight:500;color:var(--gray-700)">We carry liability insurance</label>
            </div>
        </div>

        <button type="submit" class="btn-save">Save Changes</button>
    </form>
</div>

<?php dash_foot(); ?>
