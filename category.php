<?php
// /electrical/ → category page showing all states with listings
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/seo_head.php';

$slug = trim($_GET['slug'] ?? '');
$cat  = get_category_by_slug($slug);

if (!$cat) { http_response_code(404); include '404.php'; exit; }

$states = get_states_with_listings((int)$cat['id']);
$all_states = get_db()->query('SELECT * FROM states ORDER BY name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head([
    'title'       => $cat['name'] . ' Contractors by State | ServiceOrdered',
    'description' => 'Find ' . $cat['name'] . ' contractors in every US state and city. ' . $cat['description'] . ' Browse listings near you.',
    'canonical'   => 'https://serviceordered.com/' . $cat['slug'] . '/',
    'jsonld'      => [
        '@context' => 'https://schema.org',
        '@type'    => 'CollectionPage',
        'name'     => $cat['name'] . ' Contractors — All States',
        'description' => $cat['description'],
        'url'      => 'https://serviceordered.com/' . $cat['slug'] . '/',
    ],
]); ?>
</head>
<body>

<?php require_once 'includes/nav.php'; ?>

<div class="page-header">
    <div class="container">
        <ol class="breadcrumb" aria-label="Breadcrumb">
            <li><a href="/">Home</a></li>
            <li><?= htmlspecialchars($cat['name']) ?></li>
        </ol>
        <h1><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?> Contractors</h1>
        <p><?= htmlspecialchars($cat['description']) ?> — Browse by state to find contractors near you.</p>
    </div>
</div>

<div class="container section">

    <?php if (!empty($states)): ?>
        <h2 class="section-title">States with <span><?= htmlspecialchars($cat['name']) ?></span> Contractors</h2>
        <div class="states-grid">
            <?php foreach ($states as $state): ?>
            <a href="/<?= $cat['slug'] ?>/<?= $state['slug'] ?>/" class="state-card">
                <div class="state-name"><?= htmlspecialchars($state['name']) ?></div>
                <div class="state-count"><?= number_format($state['business_count']) ?> contractors</div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- States with no listings yet -->
        <?php
        $listed_ids = array_column($states, 'id');
        $unlisted   = array_filter($all_states, fn($s) => !in_array($s['id'], $listed_ids));
        if ($unlisted):
        ?>
        <h3 style="font-size:.88rem;font-weight:700;color:var(--gray-400);margin:2.5rem 0 .75rem;text-transform:uppercase;letter-spacing:.06em">Coming Soon</h3>
        <div class="states-grid">
            <?php foreach ($unlisted as $state): ?>
            <div class="state-card" style="opacity:.5;cursor:default">
                <div class="state-name"><?= htmlspecialchars($state['name']) ?></div>
                <div class="state-count">Listings coming soon</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state">
            <div style="font-size:3rem"><?= $cat['icon'] ?></div>
            <h2><?= htmlspecialchars($cat['name']) ?> Contractors</h2>
            <p>We're actively adding <?= htmlspecialchars(strtolower($cat['name'])) ?> contractors across all 50 states.<br>Check back soon or <a href="/search.php">search all contractors</a>.</p>
        </div>

        <h2 class="section-title" style="margin-top:2rem">Browse All States</h2>
        <div class="states-grid">
            <?php foreach ($all_states as $state): ?>
            <div class="state-card" style="opacity:.5;cursor:default">
                <div class="state-name"><?= htmlspecialchars($state['name']) ?></div>
                <div class="state-count">Coming soon</div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>

</body>
</html>
