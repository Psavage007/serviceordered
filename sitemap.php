<?php
/**
 * Dynamic sitemap — covers all category, state, and city pages
 * Accessible at /sitemap.php (Nginx rewrites /sitemap.xml → this file)
 */
require_once 'includes/db.php';

header('Content-Type: application/xml; charset=utf-8');
$db   = get_db();
$base = 'https://serviceordered.com';
$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages
$static = [
    ['/', '1.0', 'daily'],
];
foreach ($static as [$path, $pri, $freq]) {
    echo "<url><loc>{$base}{$path}</loc><lastmod>{$today}</lastmod><changefreq>{$freq}</changefreq><priority>{$pri}</priority></url>\n";
}

// Category pages
$cats = $db->query('SELECT slug FROM categories ORDER BY sort_order')->fetchAll(PDO::FETCH_COLUMN);
foreach ($cats as $slug) {
    echo "<url><loc>{$base}/{$slug}/</loc><lastmod>{$today}</lastmod><changefreq>weekly</changefreq><priority>0.9</priority></url>\n";
}

// State pages: /category/state/
$state_pages = $db->query("
    SELECT DISTINCT c.slug AS cat, s.slug AS state
    FROM categories c
    JOIN business_categories bc ON bc.category_id = c.id
    JOIN businesses b ON b.id = bc.business_id
    JOIN cities ci ON ci.id = b.city_id
    JOIN states s ON s.id = ci.state_id
    ORDER BY c.slug, s.slug
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($state_pages as $r) {
    echo "<url><loc>{$base}/{$r['cat']}/{$r['state']}/</loc><lastmod>{$today}</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>\n";
}

// City pages: /category/state/city/
$city_pages = $db->query("
    SELECT DISTINCT c.slug AS cat, s.slug AS state, ci.slug AS city,
                    MAX(b.updated_at) AS last_updated
    FROM categories c
    JOIN business_categories bc ON bc.category_id = c.id
    JOIN businesses b ON b.id = bc.business_id
    JOIN cities ci ON ci.id = b.city_id
    JOIN states s ON s.id = ci.state_id
    GROUP BY c.slug, s.slug, ci.slug
    ORDER BY c.slug, s.slug, ci.slug
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($city_pages as $r) {
    $lastmod = $r['last_updated'] ? date('Y-m-d', strtotime($r['last_updated'])) : $today;
    echo "<url><loc>{$base}/{$r['cat']}/{$r['state']}/{$r['city']}/</loc><lastmod>{$lastmod}</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
}

echo '</urlset>';
