<?php
require_once '../includes/db.php';
require_once '../includes/user_auth.php';
require_once 'layout.php';

$user     = auth_check();
$db       = get_db();
$business = user_get_business($db, $user['id']);

if (!$business) {
    dash_head('Services', 'services');
    echo '<div class="dash-alert dash-alert-amber">You need an approved business before managing services. <a href="/dashboard/claim.php" style="font-weight:700;color:#92400e">Claim a listing</a>.</div>';
    dash_foot();
    exit;
}

$error   = '';
$success = '';

// Add service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name  = trim($_POST['service_name'] ?? '');
    $price = trim($_POST['service_price'] ?? '');
    $desc  = trim($_POST['service_desc'] ?? '');
    if (empty($name)) {
        $error = 'Service name is required.';
    } else {
        $db->prepare('INSERT INTO business_services (business_id, name, price, description) VALUES (?,?,?,?)')
           ->execute([$business['id'], $name, $price ?: null, $desc ?: null]);
        $success = 'Service added.';
    }
}

// Delete service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    $db->prepare('DELETE FROM business_services WHERE id=? AND business_id=?')
       ->execute([(int)$_POST['delete_service'], $business['id']]);
    $success = 'Service removed.';
}

$stmt = $db->prepare('SELECT * FROM business_services WHERE business_id=? ORDER BY id');
$stmt->execute([$business['id']]);
$services = $stmt->fetchAll();

dash_head('Services', 'services');
?>

<div class="dash-page-title">Services &amp; Pricing</div>
<p class="dash-page-sub">List what you offer so customers know exactly what to expect.</p>

<?php if ($success): ?>
<div class="dash-alert dash-alert-green"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="dash-alert dash-alert-red"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="dash-card">
    <div class="dash-card-title">Add a Service</div>
    <form method="POST">
        <div class="field-row">
            <div class="field-group">
                <label>Service Name *</label>
                <input type="text" name="service_name" placeholder="e.g. Roof Inspection" required>
            </div>
            <div class="field-group">
                <label>Price</label>
                <input type="text" name="service_price" placeholder="e.g. $150 flat, $75/hr, Free estimate">
            </div>
        </div>
        <div class="field-group">
            <label>Description</label>
            <textarea name="service_desc" rows="2" placeholder="Brief description of what's included..."></textarea>
        </div>
        <button type="submit" name="add_service" class="btn-save">Add Service</button>
    </form>
</div>

<?php if ($services): ?>
<div class="dash-card">
    <div class="dash-card-title">Your Services (<?= count($services) ?>)</div>
    <?php foreach ($services as $s): ?>
    <div class="service-item">
        <div class="service-item-info">
            <div class="service-name"><?= htmlspecialchars($s['name']) ?></div>
            <?php if ($s['price']): ?>
            <div class="service-price"><?= htmlspecialchars($s['price']) ?></div>
            <?php endif; ?>
            <?php if ($s['description']): ?>
            <div class="service-desc"><?= htmlspecialchars($s['description']) ?></div>
            <?php endif; ?>
        </div>
        <form method="POST" onsubmit="return confirm('Remove this service?')">
            <input type="hidden" name="delete_service" value="<?= $s['id'] ?>">
            <button type="submit" class="btn-danger">Remove</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="dash-empty">
    <div style="font-size:3rem">💼</div>
    <h3>No services yet</h3>
    <p>Add your services and pricing to help customers understand what you offer.</p>
</div>
<?php endif; ?>

<?php dash_foot(); ?>
