<?php
require_once '../includes/db.php';
require_once '../includes/user_auth.php';
require_once 'layout.php';

$user = auth_check();
$db   = get_db();

// If already has an approved business, redirect to profile
$existing = user_get_business($db, $user['id']);
if ($existing) {
    header('Location: /dashboard/profile.php');
    exit;
}

$error   = '';
$success = '';
$results = [];
$q       = trim($_GET['q'] ?? '');

// Search businesses
if ($q) {
    $like = '%' . $q . '%';
    $stmt = $db->prepare("
        SELECT b.id, b.name, b.address, b.city_name, b.state_abbr, b.phone,
               cb.status AS claim_status
        FROM businesses b
        LEFT JOIN claimed_businesses cb ON cb.business_id = b.id
        WHERE (b.name LIKE ? OR b.address LIKE ?) AND b.is_active = 1
        LIMIT 20
    ");
    $stmt->execute([$like, $like]);
    $results = $stmt->fetchAll();
}

// Submit claim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_id'])) {
    $biz_id = (int)$_POST['claim_id'];

    // Check not already claimed/pending
    $stmt = $db->prepare('SELECT status FROM claimed_businesses WHERE business_id=? AND user_id=?');
    $stmt->execute([$biz_id, $user['id']]);
    $existing_claim = $stmt->fetchColumn();

    if ($existing_claim) {
        $error = 'You already have a ' . $existing_claim . ' claim for this business.';
    } else {
        // Check if another approved claim exists
        $stmt = $db->prepare('SELECT id FROM claimed_businesses WHERE business_id=? AND status="approved"');
        $stmt->execute([$biz_id]);
        if ($stmt->fetch()) {
            $error = 'This business has already been claimed by another user.';
        } else {
            $db->prepare('INSERT INTO claimed_businesses (business_id, user_id, status) VALUES (?,?,"pending")')
               ->execute([$biz_id, $user['id']]);
            $success = 'Claim submitted! Our team will review it within 1–2 business days.';
        }
    }
}

dash_head('Claim Listing', 'claim');
?>

<div class="dash-page-title">Claim Your Listing</div>
<p class="dash-page-sub">Search for your business and request ownership of the profile.</p>

<?php if ($success): ?>
<div class="dash-alert dash-alert-green"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="dash-alert dash-alert-red"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="dash-card">
    <div class="dash-card-title">Search for Your Business</div>
    <form method="GET">
        <div style="display:flex;gap:.75rem">
            <div class="field-group" style="flex:1;margin-bottom:0">
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Business name or address..." autofocus>
            </div>
            <button type="submit" class="btn-save" style="white-space:nowrap">Search</button>
        </div>
    </form>
</div>

<?php if ($q && empty($results)): ?>
<div class="dash-empty">
    <div style="font-size:3rem">🔍</div>
    <h3>No results for "<?= htmlspecialchars($q) ?>"</h3>
    <p>Your business may not be in our directory yet. <a href="/dashboard/profile.php" style="color:var(--blue);font-weight:600">Create a new profile</a> instead.</p>
</div>
<?php endif; ?>

<?php if ($results): ?>
<div class="dash-card">
    <div class="dash-card-title"><?= count($results) ?> Result<?= count($results) !== 1 ? 's' : '' ?></div>
    <?php foreach ($results as $biz): ?>
    <div style="display:flex;gap:1rem;align-items:center;padding:.9rem 0;border-bottom:1px solid var(--gray-100)">
        <div style="width:44px;height:44px;background:var(--blue-light);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:800;color:var(--blue);flex-shrink:0">
            <?= strtoupper(substr($biz['name'], 0, 2)) ?>
        </div>
        <div style="flex:1">
            <div style="font-weight:700;color:var(--gray-900)"><?= htmlspecialchars($biz['name']) ?></div>
            <?php if ($biz['address'] || $biz['city_name']): ?>
            <div style="font-size:.83rem;color:var(--gray-500)">
                <?= htmlspecialchars(trim($biz['address'] . ', ' . $biz['city_name'] . ' ' . $biz['state_abbr'], ', ')) ?>
            </div>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($biz['claim_status'] === 'approved'): ?>
            <span style="font-size:.8rem;color:var(--gray-400);font-weight:600">Already Claimed</span>
            <?php elseif ($biz['claim_status'] === 'pending'): ?>
            <span style="font-size:.8rem;color:#d97706;font-weight:600">Claim Pending</span>
            <?php else: ?>
            <form method="POST">
                <input type="hidden" name="claim_id" value="<?= $biz['id'] ?>">
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
                <button type="submit" class="btn-save" style="font-size:.82rem;padding:.45rem 1.1rem"
                        onclick="return confirm('Claim <?= htmlspecialchars(addslashes($biz['name'])) ?>?')">
                    Claim This Business
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="dash-card" style="background:var(--gray-50)">
    <div class="dash-card-title">Don't see your business?</div>
    <p style="font-size:.88rem;color:var(--gray-600);margin-bottom:1rem">
        If your business isn't in our directory yet, you can create a new profile from scratch.
    </p>
    <a href="/dashboard/profile.php" class="btn-save" style="display:inline-block;text-decoration:none">Create New Profile</a>
</div>

<?php dash_foot(); ?>
