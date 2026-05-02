<?php
require_once '../includes/db.php';
require_once '../includes/helpers.php';
require_once 'auth.php';
require_once 'layout.php';
admin_check();

$db = get_db();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    if ($action === 'feature' && $id) {
        $db->prepare('UPDATE businesses SET featured = 1 WHERE id = ?')->execute([$id]);
    } elseif ($action === 'unfeature' && $id) {
        $db->prepare('UPDATE businesses SET featured = 0 WHERE id = ?')->execute([$id]);
    } elseif ($action === 'delete' && $id) {
        $db->prepare('DELETE FROM businesses WHERE id = ?')->execute([$id]);
    }
    header('Location: /admin/businesses.php?' . http_build_query(array_filter(['q' => $_GET['q'] ?? '', 'page' => $_GET['page'] ?? ''])));
    exit;
}

$q    = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 25;
$off  = ($page - 1) * $per;

$where  = $q ? 'WHERE b.name LIKE ?' : '';
$params = $q ? ['%' . $q . '%'] : [];

$total = $db->prepare("SELECT COUNT(*) FROM businesses b $where");
$total->execute($params);
$total = (int)$total->fetchColumn();

$stmt = $db->prepare("
    SELECT b.id, b.name, b.slug, b.rating, b.review_count, b.featured, b.phone,
           ci.name AS city, s.abbreviation AS state, b.created_at
    FROM businesses b
    LEFT JOIN cities ci ON ci.id = b.city_id
    LEFT JOIN states s ON s.id = ci.state_id
    $where
    ORDER BY b.featured DESC, b.created_at DESC
    LIMIT $per OFFSET $off
");
$stmt->execute($params);
$businesses = $stmt->fetchAll();
$pages = ceil($total / $per);

admin_layout_head('Businesses', 'businesses');
?>

<div class="admin-table-wrap">
    <div class="admin-table-header">
        <h2><?= number_format($total) ?> Businesses</h2>
        <form class="admin-search" method="GET">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name...">
            <button type="submit">Search</button>
        </form>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Business</th>
                <th>Location</th>
                <th>Rating</th>
                <th>Status</th>
                <th>Added</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($businesses)): ?>
            <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--gray-400)">
                <?= $q ? 'No results for "' . htmlspecialchars($q) . '"' : 'No businesses yet' ?>
            </td></tr>
            <?php else: ?>
            <?php foreach ($businesses as $b): ?>
            <tr>
                <td>
                    <div style="font-weight:600;color:var(--gray-900)"><?= htmlspecialchars($b['name']) ?></div>
                    <?php if ($b['phone']): ?>
                    <div style="font-size:.77rem;color:var(--gray-400)"><?= htmlspecialchars($b['phone']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars(($b['city'] ?? '—') . ', ' . ($b['state'] ?? '')) ?></td>
                <td>
                    <?php if ($b['rating']): ?>
                    <span style="color:var(--amber);font-weight:700"><?= number_format((float)$b['rating'],1) ?></span>
                    <span style="color:var(--gray-400);font-size:.78rem">(<?= number_format($b['review_count']) ?>)</span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <?php if ($b['featured']): ?>
                    <span class="badge badge-amber">Featured</span>
                    <?php else: ?>
                    <span class="badge badge-gray">Standard</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--gray-400);font-size:.8rem"><?= date('M j, Y', strtotime($b['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                        <a href="/business/<?= htmlspecialchars($b['slug']) ?>/" target="_blank" class="btn-sm btn-sm-blue">View</a>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <?php if ($b['featured']): ?>
                            <input type="hidden" name="action" value="unfeature">
                            <button class="btn-sm btn-sm-green">Unfeature</button>
                            <?php else: ?>
                            <input type="hidden" name="action" value="feature">
                            <button class="btn-sm btn-sm-green">Feature</button>
                            <?php endif; ?>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this business?')">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn-sm btn-sm-red">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($pages > 1): ?>
    <div class="admin-pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php if (abs($i - $page) <= 2 || $i == 1 || $i == $pages): ?>
                <?php if ($i == $page): ?>
                <span class="cur"><?= $i ?></span>
                <?php else: ?>
                <a href="?<?= http_build_query(array_filter(['q' => $q, 'page' => $i])) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php elseif (abs($i - $page) == 3): ?>
                <span>…</span>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php admin_layout_foot(); ?>
