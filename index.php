<?php
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/seo_head.php';
require_once 'includes/user_auth.php';

$categories = get_all_categories();

$grouped = [];
foreach ($categories as $cat) {
    $grouped[$cat['group_name']][] = $cat;
}

$states = get_db()->query('SELECT name, slug FROM states ORDER BY name')->fetchAll();

$jsonld = [
    '@context' => 'https://schema.org',
    '@type'    => 'WebSite',
    'name'     => 'ServiceOrdered',
    'url'      => 'https://serviceordered.com',
    'description' => 'Find trusted specialty contractors by state and city across all 50 US states.',
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
    'title'       => 'Find Trusted Specialty Contractors Near You | ServiceOrdered',
    'description' => 'Search 50 specialty contractor categories across every US state and city. Read real reviews, compare pros, and contact the right contractor — free.',
    'canonical'   => 'https://serviceordered.com/',
    'jsonld'      => $jsonld,
]); ?>
</head>
<body>

<?php require_once 'includes/nav.php'; ?>

<section class="hero">
    <div class="container hero-content">
        <div class="hero-badge">✅ &nbsp;Verified GMB Listings &bull; All 50 States</div>
        <h1>Find Trusted Specialty<br>Contractors <em>Near You</em></h1>
        <p class="hero-sub">From electrical and plumbing to radon mitigation, dock builders, and 45 more specialty trades — real listings, real ratings.</p>
        <div class="search-wrap">
            <form class="search-box" action="/search.php" method="GET">
                <div class="search-field">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" name="q" placeholder="What service do you need?" autocomplete="off" aria-label="Search service type or company name">
                </div>
                <select name="state" aria-label="Select state">
                    <option value="">📍 All States</option>
                    <?php foreach ($states as $s): ?>
                    <option value="<?= htmlspecialchars($s['slug']) ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Search</button>
            </form>
        </div>
        <div class="hero-tags">
            <span>Popular:</span>
            <a href="/electrical/">Electrical</a>
            <a href="/plumbing/">Plumbing</a>
            <a href="/hvac/">HVAC</a>
            <a href="/roofing/">Roofing</a>
            <a href="/solar-installation/">Solar</a>
            <a href="/mold-remediation/">Mold Remediation</a>
        </div>
    </div>
</section>

<div class="trust-bar">
    <div class="container trust-inner">
        <div class="trust-item">
            <span class="trust-icon">🏆</span>
            <span><strong>50</strong> Specialty Categories</span>
        </div>
        <div class="trust-item">
            <span class="trust-icon">📍</span>
            <span><strong>All 50</strong> US States Covered</span>
        </div>
        <div class="trust-item">
            <span class="trust-icon">⭐</span>
            <span><strong>Real Ratings</strong> from Google</span>
        </div>
        <div class="trust-item">
            <span class="trust-icon">🆓</span>
            <span><strong>Always Free</strong> to Search</span>
        </div>
    </div>
</div>

<section class="how-section">
    <div class="container">
        <div class="section-header">
            <div class="section-label">How It Works</div>
            <h2>Find the right contractor in 3 steps</h2>
            <p>No sign-up required. Just search, compare, and call.</p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Browse by Service</h3>
                <p>Pick from 50 specialty contractor categories — from common trades to ultra-niche services most directories don't list.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>Filter by Location</h3>
                <p>Narrow results to your state or city. See ratings, reviews, addresses, and contact info at a glance.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Call or Visit</h3>
                <p>Contact contractors directly — no middleman, no lead fees. Just connect with the pro you want.</p>
            </div>
        </div>
    </div>
</section>

<section class="cat-section">
    <div class="container">
        <h2 class="section-title">Browse All Service Categories</h2>
        <p class="section-desc">Specialty contractors across every US state — click any category to find pros near you.</p>

        <?php foreach ($grouped as $group_name => $cats): ?>
        <div class="group-label"><?= htmlspecialchars($group_name) ?></div>
        <div class="categories-grid">
            <?php foreach ($cats as $cat): ?>
            <a href="/<?= htmlspecialchars($cat['slug']) ?>/" class="category-card">
                <div class="cat-icon-wrap"><?= $cat['icon'] ?></div>
                <div>
                    <div class="cat-name"><?= htmlspecialchars($cat['name']) ?></div>
                    <div class="cat-sub">Browse by state →</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="biz-cta-section">
    <div class="container biz-cta-inner">
        <div class="biz-cta-text">
            <div class="section-label" style="text-align:left">For Contractors</div>
            <h2 style="font-size:clamp(1.4rem,2.5vw,2rem);font-weight:800;color:var(--gray-900);letter-spacing:-.4px;margin:.35rem 0 .6rem">Grow your business with a free listing</h2>
            <p style="color:var(--gray-500);font-size:.95rem;max-width:480px">Claim your existing profile or create a new one. Add photos, services, and pricing to stand out from competitors.</p>
        </div>
        <div class="biz-cta-actions">
            <a href="/register.php" class="btn btn-primary" style="font-size:1rem;padding:.75rem 2rem">Create Free Account</a>
            <a href="/login.php" class="btn btn-outline" style="font-size:1rem;padding:.75rem 2rem">Sign In</a>
        </div>
    </div>
</section>

<section class="why-section">
    <div class="container why-inner">
        <div class="section-header">
            <div class="section-label" style="color:rgba(255,255,255,.7)">Why ServiceOrdered</div>
            <h2 style="color:#fff">Built for people who need the right contractor — fast</h2>
        </div>
        <div class="why-grid">
            <div class="why-card">
                <div class="why-icon">🔍</div>
                <h3>Specialty Focus</h3>
                <p>We list the 50 specialty trades that big directories skip — from radon mitigation to marine dock builders.</p>
            </div>
            <div class="why-card">
                <div class="why-icon">📊</div>
                <h3>Real Ratings & Reviews</h3>
                <p>Ratings pulled directly from Google My Business — authentic reviews from real customers.</p>
            </div>
            <div class="why-card">
                <div class="why-icon">🗺️</div>
                <h3>Nationwide Coverage</h3>
                <p>Every US state and major city. Whether you're in Houston or rural Vermont, find local pros.</p>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="footer-logo">Service<span>Ordered</span></div>
                <p>The specialty contractor directory for homeowners, property managers, and businesses across all 50 US states.</p>
            </div>
            <div class="footer-col">
                <h4>Top Services</h4>
                <ul>
                    <li><a href="/electrical/">Electrical</a></li>
                    <li><a href="/plumbing/">Plumbing</a></li>
                    <li><a href="/hvac/">HVAC</a></li>
                    <li><a href="/roofing/">Roofing</a></li>
                    <li><a href="/solar-installation/">Solar Installation</a></li>
                    <li><a href="/mold-remediation/">Mold Remediation</a></li>
                    <li><a href="/radon-mitigation/">Radon Mitigation</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Top States</h4>
                <ul>
                    <li><a href="/electrical/texas/">Texas</a></li>
                    <li><a href="/electrical/california/">California</a></li>
                    <li><a href="/electrical/florida/">Florida</a></li>
                    <li><a href="/electrical/new-york/">New York</a></li>
                    <li><a href="/electrical/illinois/">Illinois</a></li>
                    <li><a href="/electrical/pennsylvania/">Pennsylvania</a></li>
                    <li><a href="/electrical/ohio/">Ohio</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/search.php">Search</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> ServiceOrdered. Specialty contractor listings across all 50 US states.</p>
            <p>Data sourced from Google My Business.</p>
        </div>
    </div>
</footer>

</body>
</html>
