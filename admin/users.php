<?php
require_once '../includes/db.php';
require_once 'auth.php';
require_once 'layout.php';
admin_check();

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        $db->prepare('DELETE FROM users WHERE id = ? AND role != "admin"')->execute([$id]);
    }
    header('Location: /admin/users.php');
    exit;
}

$q    = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 25;
$off  = ($page - 1) * $per;

$where  = $q ? 'WHERE email LIKE ? OR name LIKE ?' : '';
$params = $q ? ['%'.$q.'%', '%'.$q.'%'] : [];

$total = $db->prepare("SELECT COUNT(*) FROM users $where");
$total->execute($params);
$total = (int)$total->fetchColumn();

$stmt = $db->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $per OFFSET $off");
$stmt->execute($params);
$users = $stmt->fetchAll();
$pages = ceil($total / $per);

admin_layout_head('Users', 'users');
?>

<div class="admin-table-wrap">
    <div class="admin-table-header">
        <h2><?= number_format($total) ?> Users</h2>
        <form class="admin-search" method="GET">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name or email...">
            <button type="submit">Search</button>
        </form>
    </div>
    <table class="admin-table">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
            <tr><td colspan="5" style="text-align:center;padding:3rem;color:var(--gray-400)">No users yet</td></tr>
            <?php else: ?>
            <?php foreach ($users as $u): ?>
            <tr>
                <td style="font-weight:600"><?= htmlspecialchars($u['name'] ?: '—') ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <?php if ($u['role'] === 'admin'): ?>
                    <span class="badge badge-blue">Admin</span>
                    <?php elseif ($u['role'] === 'business'): ?>
                    <span class="badge badge-green">Business</span>
                    <?php else: ?>
                    <span class="badge badge-gray">User</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--gray-400);font-size:.8rem"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <?php if ($u['role'] !== 'admin'): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user?')">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn-sm btn-sm-red">Delete</button>
                    </form>
                    <?php else: ?>
                    <span style="font-size:.78rem;color:var(--gray-400)">Protected</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <div class="admin-pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <?php if ($i == $page): ?><span class="cur"><?= $i ?></span>
        <?php else: ?><a href="?<?= http_build_query(array_filter(['q'=>$q,'page'=>$i])) ?>"><?= $i ?></a><?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php admin_layout_foot(); ?>
