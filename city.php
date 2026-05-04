<?php
// /electrical/texas/houston/ → main SEO page — list of contractors
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/seo_head.php';

$cat_slug   = trim($_GET['cat']   ?? '');
$state_slug = trim($_GET['state'] ?? '');
$city_slug  = trim($_GET['city']  ?? '');

$cat   = get_category_by_slug($cat_slug);
$state = get_state_by_slug($state_slug);

if (!$cat || !$state) { http_response_code(404); include '404.php'; exit; }

$city = null;
if ($city_slug) {
    $city = get_city_by_slug((int)$state['id'], $city_slug);
    if (!$city) { http_response_code(404); include '404.php'; exit; }
}

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset   = ($page - 1) * $per_page;
$year     = date('Y');

if ($city) {
    $businesses  = get_businesses((int)$cat['id'], (int)$city['id'], $per_page, $offset);
    $total       = count_businesses((int)$cat['id'], (int)$city['id']);
    $location    = $city['name'] . ', ' . $state['abbreviation'];
    $location_long = $city['name'] . ', ' . $state['name'];
    $canonical   = 'https://serviceordered.com/' . $cat['slug'] . '/' . $state['slug'] . '/' . $city['slug'] . '/';
    $h1          = 'Best ' . $cat['name'] . ' Contractors in ' . $city['name'] . ', ' . $state['abbreviation'];
    $title       = $cat['name'] . ' Contractors in ' . $city['name'] . ', ' . $state['abbreviation'] . ' (' . $year . ') | ServiceOrdered';
    $desc        = 'Find the best ' . strtolower($cat['name']) . ' contractors in ' . $location . '. ' . number_format($total) . ' local listings with verified ratings, phone numbers, and websites. Compare pros and get quotes free.';

    // Related cities
    $related_cities = get_db()->prepare("
        SELECT ci.name, ci.slug, COUNT(b.id) AS cnt
        FROM cities ci
        JOIN businesses b ON b.city_id = ci.id
        JOIN business_categories bc ON bc.business_id = b.id
        WHERE ci.state_id = ? AND ci.id != ? AND bc.category_id = ?
        GROUP BY ci.id ORDER BY cnt DESC LIMIT 8
    ");
    $related_cities->execute([$state['id'], $city['id'], $cat['id']]);
    $related_cities = $related_cities->fetchAll();

    // JSON-LD: BreadcrumbList
    $breadcrumb_ld = [
        '@context' => 'https://schema.org',
        '@type'    => 'BreadcrumbList',
        'itemListElement' => [
            ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>'https://serviceordered.com/'],
            ['@type'=>'ListItem','position'=>2,'name'=>$cat['name'],'item'=>'https://serviceordered.com/'.$cat['slug'].'/'],
            ['@type'=>'ListItem','position'=>3,'name'=>$state['name'],'item'=>'https://serviceordered.com/'.$cat['slug'].'/'.$state['slug'].'/'],
            ['@type'=>'ListItem','position'=>4,'name'=>$city['name'],'item'=>$canonical],
        ],
    ];

    // JSON-LD: ItemList of businesses
    $item_list_ld = [
        '@context' => 'https://schema.org',
        '@type'    => 'ItemList',
        'name'     => $h1,
        'url'      => $canonical,
        'numberOfItems' => $total,
        'itemListElement' => array_values(array_map(function($b, $i) {
            return [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'item'     => [
                    '@type'  => 'LocalBusiness',
                    'name'   => $b['name'],
                    'url'    => 'https://serviceordered.com/business/' . $b['slug'] . '/',
                    'telephone' => $b['phone'] ?? '',
                    'address'   => ['@type'=>'PostalAddress','streetAddress'=>$b['address'] ?? ''],
                    'aggregateRating' => $b['rating'] ? [
                        '@type' => 'AggregateRating',
                        'ratingValue' => $b['rating'],
                        'reviewCount' => $b['review_count'],
                    ] : null,
                ],
            ];
        }, array_slice($businesses, 0, 5), array_keys(array_slice($businesses, 0, 5)))),
    ];

    // JSON-LD: FAQ
    $service = strtolower($cat['name']);
    $faq_ld = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name'  => 'How much does ' . $service . ' cost in ' . $location . '?',
                'acceptedAnswer' => ['@type'=>'Answer','text'=>'The cost of ' . $service . ' in ' . $location . ' varies by scope and contractor. Most homeowners pay between $150–$500 for standard jobs. Get free quotes from multiple contractors on ServiceOrdered to find the best price.'],
            ],
            [
                '@type' => 'Question',
                'name'  => 'How do I find a licensed ' . $service . ' contractor in ' . $city['name'] . '?',
                'acceptedAnswer' => ['@type'=>'Answer','text'=>'ServiceOrdered lists verified ' . $service . ' contractors in ' . $city['name'] . ' with ratings, reviews, and contact info. Look for contractors with high Google ratings and ask for proof of license and insurance before hiring.'],
            ],
            [
                '@type' => 'Question',
                'name'  => 'What should I look for when hiring a ' . $service . ' contractor in ' . $city['name'] . '?',
                'acceptedAnswer' => ['@type'=>'Answer','text'=>'When hiring a ' . $service . ' contractor in ' . $city['name'] . ', check their Google rating, number of reviews, years in business, and whether they carry liability insurance. Always get at least 2–3 quotes before committing.'],
            ],
            [
                '@type' => 'Question',
                'name'  => 'Are ' . $service . ' contractors in ' . $location . ' insured?',
                'acceptedAnswer' => ['@type'=>'Answer','text'=>'Reputable ' . $service . ' contractors in ' . $location . ' carry general liability insurance and workers\' compensation. Always ask for proof of insurance before work begins. You can verify contractor credentials through the ' . $state['name'] . ' state licensing board.'],
            ],
        ],
    ];

    $jsonld = [$breadcrumb_ld, $item_list_ld, $faq_ld];

} else {
    // State page
    $cities_list = get_cities_with_listings((int)$cat['id'], (int)$state['id']);
    $businesses  = [];
    $location    = $state['name'];
    $location_long = $state['name'];
    $canonical   = 'https://serviceordered.com/' . $cat['slug'] . '/' . $state['slug'] . '/';
    $h1          = $cat['name'] . ' Contractors in ' . $state['name'];
    $title       = $cat['name'] . ' Contractors in ' . $state['name'] . ' (' . $year . ') | ServiceOrdered';
    $desc        = 'Find trusted ' . strtolower($cat['name']) . ' contractors across ' . $state['name'] . '. Browse ' . count($cities_list) . ' cities with verified ratings, phone numbers, and direct contact info.';
    $related_cities = [];

    $breadcrumb_ld = [
        '@context' => 'https://schema.org',
        '@type'    => 'BreadcrumbList',
        'itemListElement' => [
            ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>'https://serviceordered.com/'],
            ['@type'=>'ListItem','position'=>2,'name'=>$cat['name'],'item'=>'https://serviceordered.com/'.$cat['slug'].'/'],
            ['@type'=>'ListItem','position'=>3,'name'=>$state['name'],'item'=>$canonical],
        ],
    ];

    $jsonld = [$breadcrumb_ld, [
        '@context'    => 'https://schema.org',
        '@type'       => 'CollectionPage',
        'name'        => $h1,
        'description' => $desc,
        'url'         => $canonical,
        'areaServed'  => $state['name'],
    ]];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head([
    'title'       => $title,
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
        <p><?= number_format($total) ?> verified <?= strtolower($cat['name']) ?> contractor<?= $total !== 1 ? 's' : '' ?> in <?= htmlspecialchars($location) ?> — with ratings, phone numbers, and websites.</p>
        <?php elseif (!$city): ?>
        <p>Browse <?= strtolower($cat['name']) ?> contractors across <?= number_format(count($cities_list)) ?> cities in <?= htmlspecialchars($state['name']) ?>.</p>
        <?php endif; ?>
    </div>
</div>

<div class="container">

    <?php if (!$city): ?>
        <!-- State page: show cities -->
        <div class="section">
            <h2 class="section-title">Find <?= htmlspecialchars($cat['name']) ?> Contractors by City in <?= htmlspecialchars($state['name']) ?></h2>
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

        <!-- State SEO content -->
        <div class="info-box" style="margin:2rem 0">
            <h2 style="font-size:1.05rem;font-weight:700;color:var(--gray-800);margin-bottom:.75rem">
                Hiring a <?= htmlspecialchars($cat['name']) ?> Contractor in <?= htmlspecialchars($state['name']) ?>
            </h2>
            <p style="font-size:.9rem;color:var(--gray-600);line-height:1.8">
                ServiceOrdered makes it easy to find trusted <?= strtolower($cat['name']) ?> professionals across <?= htmlspecialchars($state['name']) ?>.
                All listings are sourced from Google My Business and include verified ratings, direct phone numbers, and business websites.
                <?= htmlspecialchars($cat['description']) ?>. Browse by city to compare local contractors and get free quotes.
            </p>
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

                <!-- City SEO content -->
                <div class="info-box" style="margin:2.5rem 0 1rem">
                    <h2 style="font-size:1.05rem;font-weight:700;color:var(--gray-800);margin-bottom:.75rem">
                        Hiring a <?= htmlspecialchars($cat['name']) ?> Contractor in <?= htmlspecialchars($location) ?>
                    </h2>
                    <p style="font-size:.9rem;color:var(--gray-600);line-height:1.8">
                        Looking for a <?= strtolower($cat['name']) ?> contractor near <?= htmlspecialchars($city['name']) ?>?
                        ServiceOrdered lists <?= number_format($total) ?> verified <?= strtolower($cat['name']) ?> specialists in <?= htmlspecialchars($location) ?>
                        with real Google ratings, direct phone numbers, and websites.
                        <?= htmlspecialchars($cat['description']) ?>.
                        Compare multiple contractors, check their reviews, and contact them directly — no middleman, no lead fees.
                    </p>
                </div>

                <!-- FAQ Section -->
                <div class="info-box" style="margin-bottom:2rem">
                    <h2 style="font-size:1.05rem;font-weight:700;color:var(--gray-800);margin-bottom:1.25rem">
                        Frequently Asked Questions
                    </h2>
                    <?php
                    $service = strtolower($cat['name']);
                    $faqs = [
                        [
                            'q' => 'How much does ' . $service . ' cost in ' . $location . '?',
                            'a' => 'The cost of ' . $service . ' in ' . $location . ' varies based on the scope of work, materials, and contractor experience. Most homeowners pay between $150–$500 for standard jobs, though larger projects may cost more. Get free quotes from multiple contractors listed here to find the best price.',
                        ],
                        [
                            'q' => 'How do I find a licensed ' . $service . ' contractor in ' . $city['name'] . '?',
                            'a' => 'Look for contractors with high Google ratings (4.0+), many reviews, and verifiable licenses. All ' . $service . ' contractors on ServiceOrdered are sourced from Google My Business. Ask each contractor for proof of license and insurance before hiring.',
                        ],
                        [
                            'q' => 'What should I look for when comparing ' . $service . ' contractors in ' . $city['name'] . '?',
                            'a' => 'Compare Google ratings, number of reviews, years in business, response time, and whether they offer free estimates. Getting at least 2–3 quotes is recommended. Check that they carry general liability insurance and workers\' compensation.',
                        ],
                        [
                            'q' => 'Do ' . $service . ' contractors in ' . $location . ' offer free estimates?',
                            'a' => 'Many ' . $service . ' contractors in ' . $location . ' offer free on-site estimates. Contact the contractors listed above directly to ask about their estimate policy, availability, and pricing.',
                        ],
                    ];
                    foreach ($faqs as $faq):
                    ?>
                    <div style="margin-bottom:1.25rem;padding-bottom:1.25rem;border-bottom:1px solid var(--gray-100)">
                        <div style="font-weight:700;color:var(--gray-800);font-size:.93rem;margin-bottom:.4rem">
                            <?= htmlspecialchars($faq['q']) ?>
                        </div>
                        <div style="font-size:.88rem;color:var(--gray-600);line-height:1.7">
                            <?= htmlspecialchars($faq['a']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </main>

            <aside>
                <div class="sidebar-box">
                    <h3>Other Services in <?= htmlspecialchars($city['name']) ?></h3>
                    <ul class="sidebar-links">
                        <?php
                        $other_cats = get_db()->query('SELECT * FROM categories ORDER BY sort_order LIMIT 12')->fetchAll();
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

                <?php if (!empty($related_cities)): ?>
                <div class="sidebar-box">
                    <h3><?= htmlspecialchars($cat['name']) ?> in Nearby Cities</h3>
                    <ul class="sidebar-links">
                        <?php foreach ($related_cities as $rc): ?>
                        <li>
                            <a href="/<?= $cat['slug'] ?>/<?= $state['slug'] ?>/<?= $rc['slug'] ?>/">
                                <?= htmlspecialchars($rc['name']) ?>
                                <span class="count"><?= number_format($rc['cnt']) ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="sidebar-box">
                    <h3><?= htmlspecialchars($cat['name']) ?> in Other States</h3>
                    <ul class="sidebar-links">
                        <?php
                        $other_states = get_db()->prepare("
                            SELECT s.name, s.slug FROM states s
                            JOIN cities ci ON ci.state_id = s.id
                            JOIN businesses b ON b.city_id = ci.id
                            JOIN business_categories bc ON bc.business_id = b.id
                            WHERE bc.category_id = ? AND s.id != ?
                            GROUP BY s.id ORDER BY COUNT(b.id) DESC LIMIT 8
                        ");
                        $other_states->execute([$cat['id'], $state['id']]);
                        foreach ($other_states->fetchAll() as $os):
                        ?>
                        <li><a href="/<?= $cat['slug'] ?>/<?= $os['slug'] ?>/"><?= htmlspecialchars($os['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>

    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

</body>
</html>
