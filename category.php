<?php
// /electrical/ → category page showing all states with listings
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/seo_head.php';

$slug = trim($_GET['slug'] ?? '');
$cat  = get_category_by_slug($slug);

if (!$cat) { http_response_code(404); include '404.php'; exit; }

$states     = get_states_with_listings((int)$cat['id']);
$all_states = get_db()->query('SELECT * FROM states ORDER BY name')->fetchAll();
$year       = date('Y');
$total_biz  = array_sum(array_column($states, 'business_count'));

$jsonld = [
    [
        '@context' => 'https://schema.org',
        '@type'    => 'BreadcrumbList',
        'itemListElement' => [
            ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>'https://serviceordered.com/'],
            ['@type'=>'ListItem','position'=>2,'name'=>$cat['name'].' Contractors','item'=>'https://serviceordered.com/'.$cat['slug'].'/'],
        ],
    ],
    [
        '@context'    => 'https://schema.org',
        '@type'       => 'CollectionPage',
        'name'        => $cat['name'] . ' Contractors — All 50 States',
        'description' => $cat['description'],
        'url'         => 'https://serviceordered.com/' . $cat['slug'] . '/',
        'numberOfItems' => $total_biz,
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head([
    'title'       => 'Find ' . $cat['name'] . ' Contractors Near You (' . $year . ') | ServiceOrdered',
    'description' => 'Find trusted ' . strtolower($cat['name']) . ' contractors in every US state and city. ' . number_format($total_biz) . ' verified listings with ratings, phone numbers, and websites. ' . $cat['description'] . '.',
    'canonical'   => 'https://serviceordered.com/' . $cat['slug'] . '/',
    'jsonld'      => $jsonld,
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
        <h1><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?> Contractors Near You</h1>
        <p><?= htmlspecialchars($cat['description']) ?> — <?= number_format($total_biz) ?> verified contractors across <?= count($states) ?> states. Browse by state to find pros near you.</p>
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

<div class="container" style="margin-bottom:3rem">
    <div class="info-box">
        <h2 style="font-size:1.05rem;font-weight:700;color:var(--gray-800);margin-bottom:.75rem">
            About <?= htmlspecialchars($cat['name']) ?> Contractors on ServiceOrdered
        </h2>
        <p style="font-size:.9rem;color:var(--gray-600);line-height:1.8">
            ServiceOrdered is the largest directory of specialty <?= strtolower($cat['name']) ?> contractors in the United States,
            with <?= number_format($total_biz) ?> verified listings across <?= count($states) ?> states.
            <?= htmlspecialchars($cat['description']) ?>.
            All listings are sourced from Google My Business and include verified ratings, real customer reviews,
            direct phone numbers, and business websites — so you can compare and contact contractors without any middleman or lead fees.
            Select your state above to find <?= strtolower($cat['name']) ?> professionals near you.
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

</body>
</html>
