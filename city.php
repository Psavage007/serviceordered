<?php
// /electrical/texas/houston/ → main SEO page — list of contractors
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/seo_head.php';

$cat_slug  = trim($_GET['cat']  ?? '');
$state_slug= trim($_GET['state']?? '');
$city_slug = trim($_GET['city'] ?? '');

$cat   = get_category_by_slug($cat_slug);
$state = get_state_by_slug($state_slug);

if (!$cat || !$state) { http_response_code(404); include '404.php'; exit; }

// State page (no city)
$city = null;
if ($city_slug) {
    $city = get_city_by_slug((int)$state['id'], $city_slug);
    if (!$city) { http_response_code(404); include '404.php'; exit; }
}

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset   = ($page - 1) * $per_page;

if ($city) {
    $businesses = get_businesses((int)$cat['id'], (int)$city['id'], $per_page, $offset);
    $total      = count_businesses((int)$cat['id'], (int)$city['id']);
    $location   = $city['name'] . ', ' . $state['abbreviation'];
    $canonical  = 'https://serviceordered.com/' . $cat['slug'] . '/' . $state['slug'] . '/' . $city['slug'] . '/';
    $h1         = $cat['name'] . ' Contractors in ' . $city['name'] . ', ' . $state['name'];
    $desc       = 'Find the best ' . strtolower($cat['name']) . ' contractors in ' . $city['name'] . ', ' . $state['name'] . '. ' . $total . ' local listings with ratings, phone numbers, and websites. ' . $cat['description'] . '.';
} else {
    // State-level — show cities with listings
    $cities_list = get_cities_with_listings((int)$cat['id'], (int)$state['id']);
    $businesses  = [];
    $location    = $state['name'];
    $canonical   = 'https://serviceordered.com/' . $cat['slug'] . '/' . $state['slug'] . '/';
    $h1          = $cat['name'] . ' Contractors in ' . $state['name'];
    $desc        = 'Browse ' . strtolower($cat['name']) . ' contractors across ' . $state['name'] . '. Find listings by city with ratings, phone numbers, and websites.';
}

$jsonld = [
    '@context'    => 'https://schema.org',
    '@type'       => 'LocalBusiness',
    'name'        => $h1,
    'description' => $desc,
    'url'         => $canonical,
    'areaServed'  => $location,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head([
    'title'       => $h1 . ' | ServiceOrdered',
    'description' => $desc,
    'canonical'   => $canonical,
    'jsonld'      => $jsonld,
]); ?>
</head>
<body>

<?php require_once 'includes/nav.php'; ?>

<div class="page-header">
    <div class="container">
        <ol class="breadcrumb" aria-label="Breadcrumb">
            <li><a href="/">Home</a></li>
            <li><a href="/<?= $cat['slug'] ?>/"><?= htmlspecialchars($cat['name']) ?></a></li>
            <li><a href="/<?= $cat['slug'] ?>/<?= $state['slug'] ?>/"><?= htmlspecialchars($state['name']) ?></a></li>
            <?php if ($city): ?><li><?= htmlspecialchars($city['name']) ?></li><?php endif; ?>
        </ol>
        <h1><?= htmlspecialchars($h1) ?></h1>
        <?php if ($city && $total > 0): ?>
        <p><?= number_format($total) ?> <?= strtolower($cat['name']) ?> contractor<?= $total !== 1 ? 's' : '' ?> found in <?= htmlspecialchars($location) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="container">

    <?php if (!$city): ?>
        <!-- State page: show cities -->
        <div class="section">
            <h2 class="section-title">Cities in <span><?= htmlspecialchars($state['name']) ?></span></h2>
            <?php if (!empty($cities_list)): ?>
            <div class="states-grid">
                <?php foreach ($cities_list as $c): ?>
                <a href="/<?= $cat['slug'] ?>/<?= $state['slug'] ?>/<?= $c['slug'] ?>/" class="state-card">
                    <div class="state-name"><?= htmlspecialchars($c['name']) ?></div>
                    <div class="state-count"><?= number_format($c['business_count']) ?> contractors</div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <h2>No listings yet in <?= htmlspecialchars($state['name']) ?></h2>
                <p>We're adding <?= htmlspecialchars(strtolower($cat['name'])) ?> contractors in <?= htmlspecialchars($state['name']) ?> soon.</p>
            </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- City page: show businesses -->
        <div class="content-sidebar">
            <main>
                <?php if (empty($businesses)): ?>
                <div class="empty-state" style="margin-top:2rem">
                    <div style="font-size:3rem"><?= $cat['icon'] ?></div>
                    <h2>No listings yet in <?= htmlspecialchars($city['name']) ?></h2>
                    <p>We're adding <?= htmlspecialchars(strtolower($cat['name'])) ?> contractors here soon.<br>
                    <a href="/<?= $cat['slug'] ?>/<?= $state['slug'] ?>/">Browse other cities in <?= htmlspecialchars($state['name']) ?></a></p>
                </div>
                <?php else: ?>
                <div class="businesses-grid">
                    <?php foreach ($businesses as $biz): ?>
                    <div class="business-card">
                        <div class="biz-logo"><?= strtoupper(substr($biz['name'], 0, 2)) ?></div>
                        <div class="biz-info">
                            <div class="biz-name">
                                <?php if ($biz['featured']): ?><span class="badge-featured">Featured</span> <?php endif; ?>
                                <a href="/business/<?= htmlspecialchars($biz['slug']) ?>/"><?= htmlspecialchars($biz['name']) ?></a>
                            </div>
                            <div class="biz-address">📍 <?= htmlspecialchars($biz['address'] ?? $location) ?></div>
                            <?php if ($biz['rating']): ?>
                            <div class="biz-rating">
                                <span class="stars"><?= render_stars((float)$biz['rating']) ?></span>
                                <span class="rating-num"><?= number_format((float)$biz['rating'], 1) ?></span>
                                <span class="review-count">(<?= number_format((int)$biz['review_count']) ?> reviews)</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="biz-actions">
                            <?php if ($biz['phone']): ?>
                            <a href="tel:<?= preg_replace('/\D/','',$biz['phone']) ?>" class="btn btn-primary">📞 Call</a>
                            <?php endif; ?>
                            <?php if ($biz['website']): ?>
                            <a href="<?= htmlspecialchars($biz['website']) ?>" target="_blank" rel="noopener nofollow" class="btn btn-outline">Website</a>
                            <?php endif; ?>
                            <a href="/business/<?= htmlspecialchars($biz['slug']) ?>/" class="btn btn-outline">View Profile</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php
                $total_pages = ceil($total / $per_page);
                if ($total_pages > 1):
                    $base = '/'. $cat['slug'] .'/'. $state['slug'] .'/'. $city['slug'] .'/';
                ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="<?= $base ?>?page=<?= $page-1 ?>">&laquo; Prev</a>
                    <?php endif; ?>
                    <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
                    <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                    <?php else: ?>
                    <a href="<?= $base ?>?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                    <a href="<?= $base ?>?page=<?= $page+1 ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </main>

            <aside>
                <div class="sidebar-box">
                    <h3>Other Categories in <?= htmlspecialchars($city['name']) ?></h3>
                    <ul class="sidebar-links">
                        <?php
                        $other_cats = get_db()->query('SELECT * FROM categories ORDER BY sort_order LIMIT 10')->fetchAll();
                        foreach ($other_cats as $oc):
                            if ($oc['id'] == $cat['id']) continue;
                        ?>
                        <li>
                            <a href="/<?= $oc['slug'] ?>/<?= $state['slug'] ?>/<?= $city['slug'] ?>/">
                                <?= $oc['icon'] ?> <?= htmlspecialchars($oc['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="sidebar-box">
                    <h3>Nearby States</h3>
                    <ul class="sidebar-links">
                        <?php
                        $nearby = get_db()->query('SELECT * FROM states ORDER BY name LIMIT 8')->fetchAll();
                        foreach ($nearby as $ns):
                            if ($ns['id'] == $state['id']) continue;
                        ?>
                        <li><a href="/<?= $cat['slug'] ?>/<?= $ns['slug'] ?>/"><?= htmlspecialchars($ns['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>

        <!-- SEO Content Block -->
        <div class="info-box" style="margin:3rem 0">
            <h2 style="font-size:1rem;font-weight:700;color:var(--gray-800);margin-bottom:.75rem">
                About <?= htmlspecialchars($cat['name']) ?> Services in <?= htmlspecialchars($location) ?>
            </h2>
            <p style="font-size:.9rem;color:var(--gray-600);line-height:1.7">
                Looking for a <?= strtolower($cat['name']) ?> contractor in <?= htmlspecialchars($location) ?>?
                ServiceOrdered lists verified <?= strtolower($cat['name']) ?> specialists with ratings, contact information,
                and service details. <?= htmlspecialchars($cat['description']) ?>.
                All listings are sourced from Google My Business and verified for accuracy.
            </p>
        </div>

    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

</body>
</html>
