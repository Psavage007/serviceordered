<?php
require_once '../includes/db.php';
require_once 'auth.php';
require_once 'layout.php';
admin_check();

$db = get_db();
$stats = [
    'businesses' => $db->query('SELECT COUNT(*) FROM businesses')->fetchColumn(),
    'cities'     => $db->query('SELECT COUNT(*) FROM cities')->fetchColumn(),
    'users'      => $db->query('SELECT COUNT(*) FROM users WHERE role != "admin"')->fetchColumn(),
    'claims'     => $db->query('SELECT COUNT(*) FROM claimed_businesses WHERE status = "pending"')->fetchColumn(),
    'featured'   => $db->query('SELECT COUNT(*) FROM businesses WHERE featured = 1')->fetchColumn(),
    'categories' => $db->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
];

$recent = $db->query('
    SELECT b.name, b.rating, b.review_count, ci.name AS city, s.abbreviation AS state, b.created_at
    FROM businesses b
    LEFT JOIN cities ci ON ci.id = b.city_id
    LEFT JOIN states s ON s.id = ci.state_id
    ORDER BY b.created_at DESC LIMIT 10
')->fetchAll();

$top_cities = $db->query('
    SELECT ci.name, s.abbreviation, COUNT(b.id) as cnt
    FROM businesses b
    JOIN cities ci ON ci.id = b.city_id
    JOIN states s ON s.id = ci.state_id
    GROUP BY ci.id ORDER BY cnt DESC LIMIT 8
')->fetchAll();

admin_layout_head('Dashboard', 'index');
?>

<div class="stat-grid">
    <div class="stat-card blue">
        <div class="stat-card-label">Total Businesses</div>
        <div class="stat-card-value"><?= number_format($stats['businesses']) ?></div>
        <div class="stat-card-sub">Across all cities</div>
    </div>
    <div class="stat-card teal">
        <div class="stat-card-label">Cities</div>
        <div class="stat-card-value"><?= number_format($stats['cities']) ?></div>
        <div class="stat-card-sub">With active listings</div>
    </div>
    <div class="stat-card green">
        <div class="stat-card-label">Registered Users</div>
        <div class="stat-card-value"><?= number_format($stats['users']) ?></div>
        <div class="stat-card-sub">Business owners &amp; users</div>
    </div>
    <div class="stat-card amber">
        <div class="stat-card-label">Pending Claims</div>
        <div class="stat-card-value"><?= number_format($stats['claims']) ?></div>
        <div class="stat-card-sub">Awaiting review</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-card-label">Featured</div>
        <div class="stat-card-value"><?= number_format($stats['featured']) ?></div>
        <div class="stat-card-sub">Featured listings</div>
    </div>
    <div class="stat-card teal">
        <div class="stat-card-label">Categories</div>
        <div class="stat-card-value"><?= number_format($stats['categories']) ?></div>
        <div class="stat-card-sub">Service categories</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 280px;gap:1.5rem">
    <div class="admin-table-wrap">
        <div class="admin-table-header">
            <h2>Recently Added Businesses</h2>
            <a href="/admin/businesses.php" class="btn-sm btn-sm-blue">View All</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr><th>Business</th><th>Location</th><th>Rating</th><th>Added</th></tr>
            </thead>
            <tbody>
                <?php if (empty($recent)): ?>
                <tr><td colspan="4" style="color:var(--gray-400);text-align:center;padding:2rem">No businesses yet — run Apify to populate</td></tr>
                <?php else: ?>
                <?php foreach ($recent as $r): ?>
                <tr>
                    <td style="font-weight:600;color:var(--gray-900)"><?= htmlspecialchars($r['name']) ?></td>
                    <td><?= htmlspecialchars($r['city'] . ', ' . $r['state']) ?></td>
                    <td>
                        <?php if ($r['rating']): ?>
                        <span style="color:var(--amber);font-weight:700"><?= number_format((float)$r['rating'],1) ?></span>
                        <span style="color:var(--gray-400);font-size:.78rem">(<?= number_format($r['review_count']) ?>)</span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="color:var(--gray-400)"><?= date('M j', strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-table-wrap">
        <div class="admin-table-header"><h2>Top Cities</h2></div>
        <table class="admin-table">
            <thead><tr><th>City</th><th>Listings</th></tr></thead>
            <tbody>
                <?php if (empty($top_cities)): ?>
                <tr><td colspan="2" style="color:var(--gray-400);text-align:center;padding:2rem">No data yet</td></tr>
                <?php else: ?>
                <?php foreach ($top_cities as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['name'] . ', ' . $c['abbreviation']) ?></td>
                    <td><span class="badge badge-blue"><?= number_format($c['cnt']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_layout_foot(); ?>
