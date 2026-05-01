<?php
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/seo_head.php';

$categories = get_all_categories();

// Group categories
$grouped = [];
foreach ($categories as $cat) {
    $grouped[$cat['group_name']][] = $cat;
}

$jsonld = [
    '@context' => 'https://schema.org',
    '@type'    => 'WebSite',
    'name'     => 'ServiceOrdered',
    'url'      => 'https://serviceordered.com',
    'description' => 'Find specialty contractors by state and city across all 50 US states.',
    'potentialAction' => [
        '@type'       => 'SearchAction',
        'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => 'https://serviceordered.com/search.php?q={search_term_string}'],
        'query-input' => 'required name=search_term_string',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head([
    'title'       => 'Find Specialty Contractors by State & City | ServiceOrdered',
    'description' => 'Search 50 specialty contractor categories across every US state and city. From electrical and plumbing to radon mitigation, dock builders, and backflow testers.',
    'canonical'   => 'https://serviceordered.com/',
    'jsonld'      => $jsonld,
]); ?>
</head>
<body>

<nav>
    <div class="container nav-inner">
        <a href="/" class="nav-logo">Service<span>Ordered</span></a>
        <ul class="nav-links">
            <li><a href="/">Home</a></li>
            <li><a href="/search.php">Search</a></li>
        </ul>
        <form class="nav-search" action="/search.php" method="GET">
            <input type="text" name="q" placeholder="Search contractors...">
            <button type="submit">Go</button>
        </form>
    </div>
</nav>

<section class="hero">
    <div class="container hero-content">
        <div class="hero-eyebrow">All 50 US States &bull; 50 Specialty Categories</div>
        <h1>Find Specialty Contractors<br><span>In Your City</span></h1>
        <p>The most detailed specialty contractor directory in the US — from common trades to ultra-niche services most directories don't even list.</p>
        <div class="search-wrap">
            <form class="search-box" action="/search.php" method="GET">
                <input type="text" name="q" placeholder="Service type or company name..." autocomplete="off" required aria-label="Search contractors">
                <select name="state" aria-label="Select state">
                    <option value="">All States</option>
                    <?php
                    $states = get_db()->query('SELECT name, slug FROM states ORDER BY name')->fetchAll();
                    foreach ($states as $s): ?>
                    <option value="<?= htmlspecialchars($s['slug']) ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Search</button>
            </form>
        </div>
    </div>
</section>

<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat"><div class="stat-num">50</div><div class="stat-label">Specialty Categories</div></div>
        <div class="stat"><div class="stat-num">50</div><div class="stat-label">US States</div></div>
        <div class="stat"><div class="stat-num">GMB</div><div class="stat-label">Verified Listings</div></div>
        <div class="stat"><div class="stat-num">Free</div><div class="stat-label">Always Free to Search</div></div>
    </div>
</div>

<div class="container section">
    <h2 class="section-title">Browse by <span>Service Category</span></h2>

    <?php foreach ($grouped as $group_name => $cats): ?>
    <div class="group-label"><?= htmlspecialchars($group_name) ?></div>
    <div class="categories-grid">
        <?php foreach ($cats as $cat): ?>
        <a href="/<?= htmlspecialchars($cat['slug']) ?>/" class="category-card">
            <div class="cat-icon"><?= $cat['icon'] ?></div>
            <div>
                <div class="cat-name"><?= htmlspecialchars($cat['name']) ?></div>
                <div class="cat-count">Browse by state</div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>

<footer>
    <div class="container footer-inner">
        <div class="footer-logo">Service<span>Ordered</span></div>
        <p>Specialty contractor listings across all 50 US states. Data from Google My Business.</p>
    </div>
</footer>

</body>
</html>
