<?php
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/seo_head.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { http_response_code(404); include '404.php'; exit; }

$db   = get_db();
$stmt = $db->prepare('
    SELECT b.*, ci.name AS city_name, ci.slug AS city_slug,
           s.name AS state_name, s.slug AS state_slug, s.abbreviation AS state_abbr
    FROM businesses b
    LEFT JOIN cities ci ON ci.id = b.city_id
    LEFT JOIN states s  ON s.id  = ci.state_id
    WHERE b.slug = ?
');
$stmt->execute([$slug]);
$biz = $stmt->fetch();

if (!$biz) { http_response_code(404); include '404.php'; exit; }

// Get categories for this business
$stmt = $db->prepare('
    SELECT c.* FROM categories c
    JOIN business_categories bc ON bc.category_id = c.id
    WHERE bc.business_id = ?
');
$stmt->execute([$biz['id']]);
$cats = $stmt->fetchAll();
$primary_cat = $cats[0] ?? null;

// Parse hours
$hours = $biz['hours'] ? json_decode($biz['hours'], true) : [];
$photos = $biz['photos'] ? json_decode($biz['photos'], true) : [];

$location  = $biz['city_name'] ? $biz['city_name'] . ', ' . $biz['state_abbr'] : '';
$initials  = strtoupper(substr($biz['name'], 0, 2));

// SEO
$cat_name  = $primary_cat ? $primary_cat['name'] : 'Contractor';
$seo_title = $biz['name'] . ' — ' . $cat_name . ' in ' . $location . ' | ServiceOrdered';
$seo_desc  = $biz['name'] . ' is a ' . strtolower($cat_name) . ' serving ' . $location . '.';
if ($biz['rating']) {
    $seo_desc .= ' Rated ' . number_format((float)$biz['rating'], 1) . '/5 based on ' . number_format((int)$biz['review_count']) . ' reviews.';
}

// JSON-LD
$jsonld = [
    '@context' => 'https://schema.org',
    '@type'    => 'LocalBusiness',
    'name'     => $biz['name'],
    'address'  => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => $biz['address'] ?? '',
        'addressLocality' => $biz['city_name'] ?? '',
        'addressRegion'   => $biz['state_abbr'] ?? '',
        'addressCountry'  => 'US',
    ],
];
if ($biz['phone'])   $jsonld['telephone']     = $biz['phone'];
if ($biz['website']) $jsonld['url']            = $biz['website'];
if ($biz['rating'])  $jsonld['aggregateRating'] = [
    '@type'       => 'AggregateRating',
    'ratingValue' => $biz['rating'],
    'reviewCount' => $biz['review_count'],
    'bestRating'  => 5,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head([
    'title'     => $seo_title,
    'description' => $seo_desc,
    'canonical' => 'https://serviceordered.com/business/' . urlencode($slug) . '/',
    'jsonld'    => $jsonld,
]); ?>
</head>
<body>

<?php require_once 'includes/nav.php'; ?>

<div class="profile-hero">
    <div class="container">
        <!-- Breadcrumb -->
        <ol class="breadcrumb" aria-label="Breadcrumb">
            <li><a href="/">Home</a></li>
            <?php if ($primary_cat): ?>
            <li><a href="/<?= $primary_cat['slug'] ?>/"><?= htmlspecialchars($primary_cat['name']) ?></a></li>
            <?php if ($biz['state_slug']): ?>
            <li><a href="/<?= $primary_cat['slug'] ?>/<?= $biz['state_slug'] ?>/"><?= htmlspecialchars($biz['state_name']) ?></a></li>
            <?php if ($biz['city_slug']): ?>
            <li><a href="/<?= $primary_cat['slug'] ?>/<?= $biz['state_slug'] ?>/<?= $biz['city_slug'] ?>/"><?= htmlspecialchars($biz['city_name']) ?></a></li>
            <?php endif; endif; endif; ?>
            <li><?= htmlspecialchars($biz['name']) ?></li>
        </ol>

        <div class="profile-top">
            <div class="profile-logo-lg"><?= $initials ?></div>
            <div class="profile-info">
                <h1><?= htmlspecialchars($biz['name']) ?></h1>
                <div class="profile-meta">
                    <?php foreach ($cats as $c): ?>
                    <span><?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?></span>
                    <?php endforeach; ?>
                    <?php if ($location): ?>
                    <span>📍 <?= htmlspecialchars($location) ?></span>
                    <?php endif; ?>
                    <?php if ($biz['rating']): ?>
                    <span style="color:#f59e0b">
                        <?= render_stars((float)$biz['rating']) ?>
                        <?= number_format((float)$biz['rating'], 1) ?>
                        (<?= number_format((int)$biz['review_count']) ?> reviews)
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($biz['featured']): ?>
            <span class="badge-featured">Featured</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container" style="margin-top:2rem;margin-bottom:3rem">
    <div class="content-sidebar">
        <main>

            <?php if ($biz['description']): ?>
            <div class="info-box" style="margin-bottom:1.25rem">
                <h2 style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400);margin-bottom:.75rem">About</h2>
                <p style="font-size:.92rem;color:var(--gray-600);line-height:1.7"><?= nl2br(htmlspecialchars($biz['description'])) ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($hours)): ?>
            <div class="info-box" style="margin-bottom:1.25rem">
                <h2 style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400);margin-bottom:.75rem">Hours of Operation</h2>
                <table style="width:100%;font-size:.88rem;border-collapse:collapse">
                    <?php foreach ($hours as $day): ?>
                    <tr>
                        <td style="padding:.35rem 0;color:var(--gray-400);width:40%"><?= htmlspecialchars($day['day'] ?? '') ?></td>
                        <td style="font-weight:500"><?= htmlspecialchars($day['hours'] ?? 'Closed') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>

            <!-- Categories -->
            <?php if (!empty($cats)): ?>
            <div class="info-box">
                <h2 style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400);margin-bottom:.75rem">Service Categories</h2>
                <div>
                    <?php foreach ($cats as $c): ?>
                    <a href="/<?= $c['slug'] ?>/" class="tag" style="font-size:.82rem;padding:.3rem .7rem;margin:.2rem">
                        <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </main>

        <aside>
            <!-- Contact Card -->
            <div class="sidebar-box">
                <h3>Contact</h3>
                <div style="display:flex;flex-direction:column;gap:.75rem">
                    <?php if ($biz['phone']): ?>
                    <a href="tel:<?= preg_replace('/\D/','',$biz['phone']) ?>" class="btn btn-primary" style="text-align:center;padding:.75rem">
                        📞 <?= htmlspecialchars(format_phone($biz['phone'])) ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($biz['website']): ?>
                    <a href="<?= htmlspecialchars($biz['website']) ?>" target="_blank" rel="noopener nofollow" class="btn btn-outline" style="text-align:center;padding:.75rem">
                        🌐 Visit Website
                    </a>
                    <?php endif; ?>
                    <?php if ($biz['gmb_url']): ?>
                    <a href="<?= htmlspecialchars($biz['gmb_url']) ?>" target="_blank" rel="noopener nofollow" class="btn btn-outline" style="text-align:center;padding:.75rem">
                        📍 View on Google Maps
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Details Card -->
            <div class="sidebar-box">
                <h3>Details</h3>
                <table style="width:100%;font-size:.85rem;border-collapse:collapse">
                    <?php if ($biz['address']): ?>
                    <tr>
                        <td style="color:var(--gray-400);padding:.35rem 0;vertical-align:top;width:40%">Address</td>
                        <td style="font-weight:500;padding:.35rem 0"><?= nl2br(htmlspecialchars($biz['address'])) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($location): ?>
                    <tr>
                        <td style="color:var(--gray-400);padding:.35rem 0">Location</td>
                        <td style="font-weight:500;padding:.35rem 0"><?= htmlspecialchars($location) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($biz['rating']): ?>
                    <tr>
                        <td style="color:var(--gray-400);padding:.35rem 0">Rating</td>
                        <td style="padding:.35rem 0">
                            <span style="color:#f59e0b;font-weight:700"><?= number_format((float)$biz['rating'],1) ?></span>
                            <span style="color:var(--gray-400);font-size:.8rem"> / 5 (<?= number_format((int)$biz['review_count']) ?> reviews)</span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Back to Listings -->
            <?php if ($primary_cat && $biz['city_slug'] && $biz['state_slug']): ?>
            <div class="sidebar-box">
                <h3>More in <?= htmlspecialchars($biz['city_name']) ?></h3>
                <ul class="sidebar-links">
                    <?php
                    $more_cats = get_db()->query('SELECT * FROM categories ORDER BY sort_order LIMIT 8')->fetchAll();
                    foreach ($more_cats as $mc):
                    ?>
                    <li>
                        <a href="/<?= $mc['slug'] ?>/<?= $biz['state_slug'] ?>/<?= $biz['city_slug'] ?>/">
                            <?= $mc['icon'] ?> <?= htmlspecialchars($mc['name']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

</body>
</html>
