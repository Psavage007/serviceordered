<?php
require_once '../includes/db.php';
require_once 'auth.php';
require_once 'layout.php';
admin_check();

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    if ($action === 'approve' && $id) {
        $db->prepare('UPDATE claimed_businesses SET status="approved" WHERE id=?')->execute([$id]);
        // Upgrade user to business role
        $db->prepare('UPDATE users u JOIN claimed_businesses cb ON cb.user_id=u.id SET u.role="business" WHERE cb.id=?')->execute([$id]);
    } elseif ($action === 'reject' && $id) {
        $db->prepare('UPDATE claimed_businesses SET status="rejected" WHERE id=?')->execute([$id]);
    }
    header('Location: /admin/claims.php');
    exit;
}

$filter = $_GET['filter'] ?? 'pending';
$where  = in_array($filter, ['pending','approved','rejected']) ? "WHERE cb.status = '$filter'" : '';

$claims = $db->query("
    SELECT cb.*, b.name AS biz_name, b.slug AS biz_slug,
           u.name AS user_name, u.email AS user_email
    FROM claimed_businesses cb
    JOIN businesses b ON b.id = cb.business_id
    JOIN users u ON u.id = cb.user_id
    $where
    ORDER BY cb.created_at DESC
")->fetchAll();

admin_layout_head('Business Claims', 'claims');
?>

<div style="display:flex;gap:.5rem;margin-bottom:1.25rem">
    <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $k=>$v): ?>
    <a href="?filter=<?= $k ?>" class="btn-sm <?= $filter===$k ? 'btn-sm-blue' : '' ?>" style="<?= $filter!==$k ? 'background:var(--gray-100);color:var(--gray-600)' : '' ?>"><?= $v ?></a>
    <?php endforeach; ?>
</div>

<div class="admin-table-wrap">
    <div class="admin-table-header">
        <h2><?= count($claims) ?> <?= ucfirst($filter) ?> Claims</h2>
    </div>
    <table class="admin-table">
        <thead>
            <tr><th>Business</th><th>Claimed By</th><th>Verification</th><th>Submitted</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (empty($claims)): ?>
            <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--gray-400)">No <?= $filter ?> claims</td></tr>
            <?php else: ?>
            <?php foreach ($claims as $c): ?>
            <tr>
                <td>
                    <a href="/business/<?= htmlspecialchars($c['biz_slug']) ?>/" target="_blank" style="font-weight:600"><?= htmlspecialchars($c['biz_name']) ?></a>
                </td>
                <td>
                    <div style="font-weight:500"><?= htmlspecialchars($c['user_name'] ?: '—') ?></div>
                    <div style="font-size:.78rem;color:var(--gray-400)"><?= htmlspecialchars($c['user_email']) ?></div>
                </td>
                <td>
                    <?php if ($c['license_number']): ?>
                    <div style="font-size:.82rem"><strong>License:</strong> <?= htmlspecialchars($c['license_number']) ?><?= $c['license_state'] ? ' (' . htmlspecialchars($c['license_state']) . ')' : '' ?></div>
                    <?php endif; ?>
                    <?php if ($c['business_email']): ?>
                    <div style="font-size:.82rem"><strong>Email:</strong> <?= htmlspecialchars($c['business_email']) ?></div>
                    <?php endif; ?>
                    <?php if ($c['license_doc']): ?>
                    <a href="<?= htmlspecialchars($c['license_doc']) ?>" target="_blank" class="btn-sm btn-sm-blue" style="margin-top:.3rem;display:inline-block">View Doc</a>
                    <?php else: ?>
                    <span style="font-size:.78rem;color:var(--gray-400)">No document</span>
                    <?php endif; ?>
                    <?php if ($c['notes']): ?>
                    <div style="font-size:.78rem;color:var(--gray-500);margin-top:.25rem;font-style:italic"><?= htmlspecialchars($c['notes']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="color:var(--gray-400);font-size:.8rem"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                <td>
                    <?php if ($c['status']==='pending'): ?><span class="badge badge-amber">Pending</span>
                    <?php elseif ($c['status']==='approved'): ?><span class="badge badge-green">Approved</span>
                    <?php else: ?><span class="badge badge-red">Rejected</span><?php endif; ?>
                </td>
                <td>
                    <?php if ($c['status']==='pending'): ?>
                    <div style="display:flex;gap:.4rem">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button class="btn-sm btn-sm-green">Approve</button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button class="btn-sm btn-sm-red">Reject</button>
                        </form>
                    </div>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_layout_foot(); ?>
