<?php
/**
 * ServiceOrdered — Yelp Fusion API Scraper
 * Run: YELP_API_KEY=your_key php8.3 scripts/scrape_yelp.php > /var/log/so_yelp.log 2>&1 &
 * Check: tail -f /var/log/so_yelp.log
 * Resume: automatically skips already-scraped combos via progress file
 *
 * Free tier: 500 calls/day — run daily until complete
 */

$API_KEY   = getenv('YELP_API_KEY') ?: '';
$PROGRESS  = '/var/log/so_yelp_progress.json';
$MAX_REQUESTS = 490; // stay under 500/day limit with buffer

if (!$API_KEY) { die("ERROR: YELP_API_KEY env var not set\n"); }

$db = new PDO(
    'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'serviceordered') . ';charset=utf8mb4',
    getenv('DB_USER') ?: 'serviceordered',
    getenv('DB_PASS') ?: 'S0rder2024x',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ── Category mapping: Yelp term → our category slug ───────────────────────

$CATEGORIES = [
    'electricians'           => 'electrical',
    'plumbing'               => 'plumbing',
    'hvac'                   => 'hvac',
    'roofing'                => 'roofing',
    'masonry_concrete'       => 'concrete',
    'drywall'                => 'drywall',
    'painters'               => 'painting',
    'flooring'               => 'flooring',
    'insulation_installation' => 'insulation',
    'landscaping'            => 'landscaping',
    'excavationservices'     => 'excavation',
    'fireprotectionservices' => 'fire-protection',
    'demolitionservices'     => 'demolition',
    'solar'                  => 'solar-installation',
    'garagedoorservices'     => 'garage-door-repair',
    'septicservices'         => 'septic-tank-service',
    'chimneysweeps'          => 'chimney-sweep',
    'foundationrepair'       => 'foundation-repair',
    'waterproofing'          => 'waterproofing',
    'moldremediation'        => 'mold-remediation',
    'poolspa'                => 'pool-contractor',
    'gutterservices'         => 'gutter-installer',
    'fences_walls'           => 'fence-contractor',
    'locksmiths'             => 'commercial-locksmith',
    'landscapingarchitects'  => 'hardscape-contractor',
    'pestcontrol'            => 'pest-control',
    'windowsinstallation'    => 'window-door-contractor',
    'sidingwindows'          => 'siding-contractor',
    'masonry'                => 'masonry-contractor',
    'homeinspectors'         => 'home-inspector',
    'securitysystems'        => 'security-system-installer',
    'generatorinstallrepair' => 'generator-installer',
    'welding'                => 'welding-contractor',
    'stucco'                 => 'stucco-contractor',
    'waterdamagerestoration' => 'water-damage-restoration',
    'fireprotectionservices' => 'fire-restoration-contractor',
    'radonservices'          => 'radon-mitigation',
];

$CITIES = [
    ['name'=>'New York City',    'state'=>'NY', 'lat'=>40.7128,  'lng'=>-74.0060],
    ['name'=>'Los Angeles',      'state'=>'CA', 'lat'=>34.0522,  'lng'=>-118.2437],
    ['name'=>'Chicago',          'state'=>'IL', 'lat'=>41.8781,  'lng'=>-87.6298],
    ['name'=>'Houston',          'state'=>'TX', 'lat'=>29.7604,  'lng'=>-95.3698],
    ['name'=>'Phoenix',          'state'=>'AZ', 'lat'=>33.4484,  'lng'=>-112.0740],
    ['name'=>'Philadelphia',     'state'=>'PA', 'lat'=>39.9526,  'lng'=>-75.1652],
    ['name'=>'San Antonio',      'state'=>'TX', 'lat'=>29.4241,  'lng'=>-98.4936],
    ['name'=>'San Diego',        'state'=>'CA', 'lat'=>32.7157,  'lng'=>-117.1611],
    ['name'=>'Dallas',           'state'=>'TX', 'lat'=>32.7767,  'lng'=>-96.7970],
    ['name'=>'San Jose',         'state'=>'CA', 'lat'=>37.3382,  'lng'=>-121.8863],
    ['name'=>'Austin',           'state'=>'TX', 'lat'=>30.2672,  'lng'=>-97.7431],
    ['name'=>'Jacksonville',     'state'=>'FL', 'lat'=>30.3322,  'lng'=>-81.6557],
    ['name'=>'Fort Worth',       'state'=>'TX', 'lat'=>32.7555,  'lng'=>-97.3308],
    ['name'=>'Columbus',         'state'=>'OH', 'lat'=>39.9612,  'lng'=>-82.9988],
    ['name'=>'Charlotte',        'state'=>'NC', 'lat'=>35.2271,  'lng'=>-80.8431],
    ['name'=>'Indianapolis',     'state'=>'IN', 'lat'=>39.7684,  'lng'=>-86.1581],
    ['name'=>'San Francisco',    'state'=>'CA', 'lat'=>37.7749,  'lng'=>-122.4194],
    ['name'=>'Seattle',          'state'=>'WA', 'lat'=>47.6062,  'lng'=>-122.3321],
    ['name'=>'Denver',           'state'=>'CO', 'lat'=>39.7392,  'lng'=>-104.9903],
    ['name'=>'Nashville',        'state'=>'TN', 'lat'=>36.1627,  'lng'=>-86.7816],
    ['name'=>'Oklahoma City',    'state'=>'OK', 'lat'=>35.4676,  'lng'=>-97.5164],
    ['name'=>'Las Vegas',        'state'=>'NV', 'lat'=>36.1699,  'lng'=>-115.1398],
    ['name'=>'Louisville',       'state'=>'KY', 'lat'=>38.2527,  'lng'=>-85.7585],
    ['name'=>'Memphis',          'state'=>'TN', 'lat'=>35.1495,  'lng'=>-90.0490],
    ['name'=>'Portland',         'state'=>'OR', 'lat'=>45.5051,  'lng'=>-122.6750],
    ['name'=>'Baltimore',        'state'=>'MD', 'lat'=>39.2904,  'lng'=>-76.6122],
    ['name'=>'Milwaukee',        'state'=>'WI', 'lat'=>43.0389,  'lng'=>-87.9065],
    ['name'=>'Albuquerque',      'state'=>'NM', 'lat'=>35.0844,  'lng'=>-106.6504],
    ['name'=>'Atlanta',          'state'=>'GA', 'lat'=>33.7490,  'lng'=>-84.3880],
    ['name'=>'Raleigh',          'state'=>'NC', 'lat'=>35.7796,  'lng'=>-78.6382],
    ['name'=>'Minneapolis',      'state'=>'MN', 'lat'=>44.9778,  'lng'=>-93.2650],
    ['name'=>'Tampa',            'state'=>'FL', 'lat'=>27.9506,  'lng'=>-82.4572],
    ['name'=>'New Orleans',      'state'=>'LA', 'lat'=>29.9511,  'lng'=>-90.0715],
    ['name'=>'Orlando',          'state'=>'FL', 'lat'=>28.5383,  'lng'=>-81.3792],
    ['name'=>'Pittsburgh',       'state'=>'PA', 'lat'=>40.4406,  'lng'=>-79.9959],
    ['name'=>'Cincinnati',       'state'=>'OH', 'lat'=>39.1031,  'lng'=>-84.5120],
    ['name'=>'Salt Lake City',   'state'=>'UT', 'lat'=>40.7608,  'lng'=>-111.8910],
    ['name'=>'Boston',           'state'=>'MA', 'lat'=>42.3601,  'lng'=>-71.0589],
    ['name'=>'Richmond',         'state'=>'VA', 'lat'=>37.5407,  'lng'=>-77.4360],
    ['name'=>'Boise',            'state'=>'ID', 'lat'=>43.6150,  'lng'=>-116.2023],
    ['name'=>'Buffalo',          'state'=>'NY', 'lat'=>42.8864,  'lng'=>-78.8784],
    ['name'=>'Grand Rapids',     'state'=>'MI', 'lat'=>42.9634,  'lng'=>-85.6681],
    ['name'=>'Tucson',           'state'=>'AZ', 'lat'=>32.2226,  'lng'=>-110.9747],
    ['name'=>'Fresno',           'state'=>'CA', 'lat'=>36.7378,  'lng'=>-119.7871],
    ['name'=>'Sacramento',       'state'=>'CA', 'lat'=>38.5816,  'lng'=>-121.4944],
    ['name'=>'Kansas City',      'state'=>'MO', 'lat'=>39.0997,  'lng'=>-94.5786],
    ['name'=>'Omaha',            'state'=>'NE', 'lat'=>41.2565,  'lng'=>-95.9345],
    ['name'=>'Colorado Springs', 'state'=>'CO', 'lat'=>38.8339,  'lng'=>-104.8214],
    ['name'=>'Virginia Beach',   'state'=>'VA', 'lat'=>36.8529,  'lng'=>-75.9780],
    ['name'=>'Honolulu',         'state'=>'HI', 'lat'=>21.3069,  'lng'=>-157.8583],
];

// ── Progress tracking ─────────────────────────────────────────────────────

$progress  = file_exists($PROGRESS) ? json_decode(file_get_contents($PROGRESS), true) : ['done' => []];
$done_set  = array_flip($progress['done']);
$total     = count($CATEGORIES) * count($CITIES);
$imported  = 0;
$req_count = 0;

log_msg("Yelp scraper starting: " . count($CATEGORIES) . " categories × " . count($CITIES) . " cities = $total searches");
log_msg("Already done: " . count($done_set) . " — daily cap: $MAX_REQUESTS");

// ── Main loop ─────────────────────────────────────────────────────────────

foreach ($CATEGORIES as $yelp_term => $cat_slug) {
    foreach ($CITIES as $city) {
        $key = $yelp_term . '|' . $city['name'] . ',' . $city['state'];
        if (isset($done_set[$key])) continue;

        if ($req_count >= $MAX_REQUESTS) {
            log_msg("Daily cap reached ($MAX_REQUESTS). Run again tomorrow to continue.");
            break 2;
        }

        $places = yelp_search($yelp_term, $city['lat'], $city['lng'], $API_KEY);
        $count  = 0;
        $req_count++;

        foreach ($places as $place) {
            if (import_yelp($db, $place, $cat_slug, $city)) $count++;
        }

        $imported += $count;
        $progress['done'][] = $key;
        file_put_contents($PROGRESS, json_encode($progress));

        $done_count = count($progress['done']);
        log_msg("[$done_count/$total] $yelp_term in {$city['name']}, {$city['state']} → $count imported");

        usleep(250000); // 250ms between calls = 4 req/sec
    }
}

log_msg("Done! Total imported this run: $imported");

// ── Yelp Fusion API ───────────────────────────────────────────────────────

function yelp_search(string $term, float $lat, float $lng, string $key): array {
    $params = http_build_query([
        'term'       => $term,
        'latitude'   => $lat,
        'longitude'  => $lng,
        'limit'      => 50,
        'radius'     => 40000, // 25 miles in meters
    ]);
    $ctx = stream_context_create(['http' => [
        'method'  => 'GET',
        'header'  => "Authorization: Bearer $key\r\nAccept: application/json\r\n",
        'timeout' => 15,
        'ignore_errors' => true,
    ]]);
    $raw  = @file_get_contents('https://api.yelp.com/v3/businesses/search?' . $params, false, $ctx);
    $data = json_decode($raw, true);
    if (isset($data['error'])) {
        log_msg("Yelp error: " . ($data['error']['description'] ?? 'unknown'));
        return [];
    }
    return $data['businesses'] ?? [];
}

// ── Import one Yelp business into DB ──────────────────────────────────────

function import_yelp(PDO $db, array $p, string $cat_slug, array $city): bool {
    $name    = trim($p['name'] ?? '');
    $phone   = $p['phone'] ?? '';
    $website = $p['url']   ?? ''; // Yelp page URL as fallback
    $rating  = $p['rating'] ?? null;
    $reviews = $p['review_count'] ?? 0;
    $address = implode(', ', array_filter([
        $p['location']['address1'] ?? '',
        $p['location']['city'] ?? '',
        $p['location']['state'] ?? '',
        $p['location']['zip_code'] ?? '',
    ]));
    $lat     = $p['coordinates']['latitude']  ?? null;
    $lng     = $p['coordinates']['longitude'] ?? null;
    $yelp_id = $p['id'] ?? null;

    if (!$name || !$yelp_id) return false;

    // Find state
    $stmt = $db->prepare('SELECT id FROM states WHERE abbreviation = ?');
    $stmt->execute([strtoupper($city['state'])]);
    $state_id = $stmt->fetchColumn();
    if (!$state_id) return false;

    // Find or create city
    $city_slug_val = slugify($city['name']);
    $stmt = $db->prepare('SELECT id FROM cities WHERE state_id = ? AND slug = ?');
    $stmt->execute([$state_id, $city_slug_val]);
    $city_id = $stmt->fetchColumn();
    if (!$city_id) {
        $stmt = $db->prepare('INSERT INTO cities (state_id, name, slug, lat, lng) VALUES (?,?,?,?,?)');
        $stmt->execute([$state_id, $city['name'], $city_slug_val, $lat, $lng]);
        $city_id = $db->lastInsertId();
    }

    // Find category
    $stmt = $db->prepare('SELECT id FROM categories WHERE slug = ?');
    $stmt->execute([$cat_slug]);
    $cat_id = $stmt->fetchColumn() ?: null;

    // Upsert — use yelp_id to avoid duplicates, skip if already from Google
    $biz_slug = slugify($name) . '-' . $city_slug_val;
    $stmt = $db->prepare('
        INSERT INTO businesses (name, slug, address, city_id, phone, website, rating, review_count, yelp_id)
        VALUES (?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            rating=IF(VALUES(rating) > rating, VALUES(rating), rating),
            review_count=IF(VALUES(review_count) > review_count, VALUES(review_count), review_count),
            updated_at=NOW()
    ');

    try {
        $stmt->execute([$name, $biz_slug, $address, $city_id, $phone ?: null, $website ?: null, $rating, $reviews, $yelp_id]);
    } catch (PDOException $e) {
        // slug collision — add suffix
        $stmt2 = $db->prepare('
            INSERT IGNORE INTO businesses (name, slug, address, city_id, phone, website, rating, review_count, yelp_id)
            VALUES (?,?,?,?,?,?,?,?,?)
        ');
        $stmt2->execute([$name, $biz_slug . '-' . substr($yelp_id, 0, 6), $address, $city_id, $phone ?: null, $website ?: null, $rating, $reviews, $yelp_id]);
    }

    $biz_id = $db->lastInsertId() ?: get_biz_id_yelp($db, $yelp_id);

    if ($cat_id && $biz_id) {
        $db->prepare('INSERT IGNORE INTO business_categories (business_id, category_id) VALUES (?,?)')->execute([$biz_id, $cat_id]);
    }

    return true;
}

function get_biz_id_yelp(PDO $db, string $yelp_id): ?int {
    $stmt = $db->prepare('SELECT id FROM businesses WHERE yelp_id = ?');
    $stmt->execute([$yelp_id]);
    return $stmt->fetchColumn() ?: null;
}

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function log_msg(string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}
