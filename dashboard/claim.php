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

// Submit claim with verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_id'])) {
    $biz_id         = (int)$_POST['claim_id'];
    $license_number = trim($_POST['license_number'] ?? '');
    $license_state  = trim($_POST['license_state'] ?? '');
    $business_email = trim($_POST['business_email'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');

    if (!$license_number) {
        $error = 'License number is required.';
    } elseif (!$business_email || !filter_var($business_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'A valid business email is required.';
    } else {
        // Check not already claimed/pending by this user
        $stmt = $db->prepare('SELECT status FROM claimed_businesses WHERE business_id=? AND user_id=?');
        $stmt->execute([$biz_id, $user['id']]);
        $existing_claim = $stmt->fetchColumn();

        if ($existing_claim) {
            $error = 'You already have a ' . $existing_claim . ' claim for this business.';
        } else {
            $stmt = $db->prepare('SELECT id FROM claimed_businesses WHERE business_id=? AND status="approved"');
            $stmt->execute([$biz_id]);
            if ($stmt->fetch()) {
                $error = 'This business has already been claimed by another user.';
            } else {
                // Handle license doc upload
                $license_doc = null;
                if (isset($_FILES['license_doc']) && $_FILES['license_doc']['error'] === UPLOAD_ERR_OK) {
                    $file    = $_FILES['license_doc'];
                    $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
                    $mime    = mime_content_type($file['tmp_name']);
                    if (!in_array($mime, $allowed)) {
                        $error = 'License document must be a JPEG, PNG, or PDF.';
                    } elseif ($file['size'] > 10 * 1024 * 1024) {
                        $error = 'File must be under 10 MB.';
                    } else {
                        $ext  = ['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'][$mime];
                        $name = 'license_' . $user['id'] . '_' . $biz_id . '_' . uniqid() . '.' . $ext;
                        $dir  = '/var/www/serviceordered.com/public/uploads/licenses/';
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        if (move_uploaded_file($file['tmp_name'], $dir . $name)) {
                            $license_doc = '/uploads/licenses/' . $name;
                        }
                    }
                }

                if (!$error) {
                    $db->prepare('
                        INSERT INTO claimed_businesses (business_id, user_id, status, license_number, license_state, license_doc, business_email, notes)
                        VALUES (?,?,"pending",?,?,?,?,?)
                    ')->execute([$biz_id, $user['id'], $license_number, $license_state, $license_doc, $business_email, $notes]);
                    $success = 'Claim submitted! Our team will verify your license and respond within 1–2 business days.';
                    $results = [];
                    $q = '';
                }
            }
        }
    }
}

dash_head('Claim Listing', 'claim');
?>

<div class="dash-page-title">Claim Your Listing</div>
<p class="dash-page-sub">Search for your business and verify ownership with your contractor license.</p>

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
    <div style="padding:1rem 0;border-bottom:1px solid var(--gray-100)">
        <div style="display:flex;gap:1rem;align-items:center">
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
                <button type="button" class="btn-save" style="font-size:.82rem;padding:.45rem 1.1rem"
                        onclick="showClaimForm(<?= $biz['id'] ?>, '<?= htmlspecialchars(addslashes($biz['name'])) ?>')">
                    Claim This Business
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Verification form (hidden until button clicked) -->
        <div id="claim-form-<?= $biz['id'] ?>" style="display:none;margin-top:1.25rem;padding:1.25rem;background:var(--gray-50);border-radius:10px;border:1.5px solid var(--gray-200)">
            <div style="font-size:.85rem;font-weight:700;color:var(--gray-700);margin-bottom:1rem">
                Verify ownership of <strong><?= htmlspecialchars($biz['name']) ?></strong>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="claim_id" value="<?= $biz['id'] ?>">
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">

                <div class="field-row">
                    <div class="field-group">
                        <label>Contractor License Number *</label>
                        <input type="text" name="license_number" placeholder="e.g. LIC-123456" required>
                    </div>
                    <div class="field-group">
                        <label>License State</label>
                        <input type="text" name="license_state" placeholder="e.g. Texas" maxlength="50">
                    </div>
                </div>

                <div class="field-group">
                    <label>Business Email Address *</label>
                    <input type="email" name="business_email" placeholder="you@yourbusiness.com" required>
                    <div class="field-hint">Use your business domain email if possible (not Gmail/Yahoo).</div>
                </div>

                <div class="field-group">
                    <label>Upload License or Insurance Document</label>
                    <input type="file" name="license_doc" accept="image/jpeg,image/png,application/pdf" style="padding:.5rem;background:#fff">
                    <div class="field-hint">JPEG, PNG, or PDF — max 10 MB. Optional but speeds up approval.</div>
                </div>

                <div class="field-group">
                    <label>Additional Notes</label>
                    <textarea name="notes" rows="2" placeholder="Anything else that helps verify your ownership..."></textarea>
                </div>

                <div style="display:flex;gap:.75rem;align-items:center">
                    <button type="submit" class="btn-save">Submit Claim</button>
                    <button type="button" onclick="hideClaimForm(<?= $biz['id'] ?>)"
                            style="background:none;border:none;color:var(--gray-400);cursor:pointer;font-size:.85rem">Cancel</button>
                </div>
            </form>
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

<script>
function showClaimForm(id, name) {
    document.querySelectorAll('[id^="claim-form-"]').forEach(f => f.style.display = 'none');
    document.getElementById('claim-form-' + id).style.display = 'block';
    document.getElementById('claim-form-' + id).scrollIntoView({behavior:'smooth', block:'nearest'});
}
function hideClaimForm(id) {
    document.getElementById('claim-form-' + id).style.display = 'none';
}
</script>

<?php dash_foot(); ?>
