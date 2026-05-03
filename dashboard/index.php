<?php
require_once '../includes/db.php';
require_once '../includes/user_auth.php';
require_once 'layout.php';

$user    = auth_check();
$db      = get_db();
$business = user_get_business($db, $user['id']);

dash_head('Dashboard', 'index');
?>

<div class="dash-page-title">Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></div>
<p class="dash-page-sub">Manage your business profile and services from here.</p>

<?php if (!$business): ?>
<div class="dash-alert dash-alert-amber">
    You don't have a business profile yet.
    <a href="/dashboard/claim.php" style="font-weight:700;color:#92400e">Claim an existing listing</a>
    or <a href="/dashboard/profile.php" style="font-weight:700;color:#92400e">create a new one</a>.
</div>
<?php else: ?>

<div class="dash-stats">
    <div class="dash-stat">
        <div class="dash-stat-num"><?= number_format((float)($business['rating'] ?? 0), 1) ?></div>
        <div class="dash-stat-label">Google Rating</div>
    </div>
    <div class="dash-stat">
        <div class="dash-stat-num"><?= number_format((int)($business['review_count'] ?? 0)) ?></div>
        <div class="dash-stat-label">Reviews</div>
    </div>
    <div class="dash-stat">
        <div class="dash-stat-num"><?= $db->prepare('SELECT COUNT(*) FROM business_photos WHERE business_id=?') && ($s=$db->prepare('SELECT COUNT(*) FROM business_photos WHERE business_id=?')) && $s->execute([$business['id']]) ? $s->fetchColumn() : 0 ?></div>
        <div class="dash-stat-label">Photos</div>
    </div>
</div>

<div class="dash-card">
    <div class="dash-card-title">Your Business</div>
    <div style="display:flex;gap:1.25rem;align-items:flex-start;flex-wrap:wrap">
        <div style="width:56px;height:56px;background:var(--blue-light);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:800;color:var(--blue);flex-shrink:0">
            <?= strtoupper(substr($business['name'], 0, 2)) ?>
        </div>
        <div style="flex:1">
            <div style="font-size:1.1rem;font-weight:700;color:var(--gray-900)"><?= htmlspecialchars($business['name']) ?></div>
            <?php if ($business['address']): ?>
            <div style="font-size:.85rem;color:var(--gray-500);margin-top:.2rem">📍 <?= htmlspecialchars($business['address']) ?></div>
            <?php endif; ?>
            <?php if ($business['phone']): ?>
            <div style="font-size:.85rem;color:var(--gray-500)">📞 <?= htmlspecialchars($business['phone']) ?></div>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a href="/business/<?= htmlspecialchars($business['slug']) ?>/" target="_blank" class="btn btn-outline" style="font-size:.82rem">View Public Profile</a>
            <a href="/dashboard/profile.php" class="btn btn-primary" style="font-size:.82rem">Edit Profile</a>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
    <a href="/dashboard/photos.php" class="dash-card" style="text-decoration:none;color:inherit;transition:box-shadow .15s" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow=''">
        <div style="font-size:1.5rem;margin-bottom:.5rem">📷</div>
        <div style="font-weight:700;color:var(--gray-900)">Manage Photos</div>
        <div style="font-size:.83rem;color:var(--gray-500);margin-top:.25rem">Add photos of your work to attract more customers</div>
    </a>
    <a href="/dashboard/services.php" class="dash-card" style="text-decoration:none;color:inherit;transition:box-shadow .15s" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow=''">
        <div style="font-size:1.5rem;margin-bottom:.5rem">💼</div>
        <div style="font-weight:700;color:var(--gray-900)">Services &amp; Pricing</div>
        <div style="font-size:.83rem;color:var(--gray-500);margin-top:.25rem">List your services and pricing to win more jobs</div>
    </a>
</div>

<?php endif; ?>

<?php dash_foot(); ?>
