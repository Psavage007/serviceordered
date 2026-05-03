<?php
/**
 * ServiceOrdered — Google Places API Scraper
 * Run: nohup php scrape.php > /var/log/so_scrape.log 2>&1 &
 * Check progress: tail -f /var/log/so_scrape.log
 * Resume: automatically skips already-scraped combos via progress file
 */

$API_KEY     = getenv('GOOGLE_PLACES_KEY') ?: '';
$PROGRESS    = '/var/log/so_scrape_progress.json';
$MAX_RESULTS = 20;
$DELAY_MS    = 200; // ms between API calls (5 req/sec max on free tier)
$MAX_REQUESTS = 11500; // hard cap — 11,500 requests × $0.017 = ~$196, uses full $200 free credit

if (!$API_KEY) { die("ERROR: GOOGLE_PLACES_KEY env var not set\n"); }

// DB connection
$db = new PDO(
    'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'serviceordered') . ';charset=utf8mb4',
    getenv('DB_USER') ?: 'serviceordered',
    getenv('DB_PASS') ?: 'S0rder2024x',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ── Data ──────────────────────────────────────────────────────────────────

$CATEGORIES = [
    'electrical contractor',      'plumber',
    'HVAC contractor',            'roofing contractor',
    'concrete contractor',        'drywall contractor',
    'painting contractor',        'flooring contractor',
    'insulation contractor',      'landscaping contractor',
    'excavation contractor',      'fire protection contractor',
    'demolition contractor',      'solar panel installer',
    'elevator company',           'garage door repair',
    'septic tank service',        'well drilling company',
    'chimney sweep',              'foundation repair contractor',
    'waterproofing contractor',   'mold remediation contractor',
    'asbestos removal contractor','radon mitigation contractor',
    'dock builder',               'pool contractor',
    'gutter installer',           'fence contractor',
    'crane rental company',       'scaffolding rental',
    'commercial locksmith',       'backflow preventer testing',
    'hardscape contractor',       'pest control',
    'window and door contractor', 'siding contractor',
    'masonry contractor',         'structural engineer',
    'home inspector',             'energy auditor',
    'generator installer',        'ev charging installer',
    'smart home installer',       'security system installer',
    'fire restoration contractor','water damage restoration',
    'welding contractor',         'sandblasting service',
    'epoxy flooring contractor',  'stucco contractor',
];

$CITIES = [
    ['name'=>'New York City',    'state'=>'New York',         'abbr'=>'NY'],
    ['name'=>'Los Angeles',      'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Chicago',          'state'=>'Illinois',         'abbr'=>'IL'],
    ['name'=>'Houston',          'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'Phoenix',          'state'=>'Arizona',          'abbr'=>'AZ'],
    ['name'=>'Philadelphia',     'state'=>'Pennsylvania',     'abbr'=>'PA'],
    ['name'=>'San Antonio',      'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'San Diego',        'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Dallas',           'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'San Jose',         'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Austin',           'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'Jacksonville',     'state'=>'Florida',          'abbr'=>'FL'],
    ['name'=>'Fort Worth',       'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'Columbus',         'state'=>'Ohio',             'abbr'=>'OH'],
    ['name'=>'Charlotte',        'state'=>'North Carolina',   'abbr'=>'NC'],
    ['name'=>'Indianapolis',     'state'=>'Indiana',          'abbr'=>'IN'],
    ['name'=>'San Francisco',    'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Seattle',          'state'=>'Washington',       'abbr'=>'WA'],
    ['name'=>'Denver',           'state'=>'Colorado',         'abbr'=>'CO'],
    ['name'=>'Nashville',        'state'=>'Tennessee',        'abbr'=>'TN'],
    ['name'=>'Oklahoma City',    'state'=>'Oklahoma',         'abbr'=>'OK'],
    ['name'=>'El Paso',          'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'Washington',       'state'=>'DC',               'abbr'=>'DC'],
    ['name'=>'Las Vegas',        'state'=>'Nevada',           'abbr'=>'NV'],
    ['name'=>'Louisville',       'state'=>'Kentucky',         'abbr'=>'KY'],
    ['name'=>'Memphis',          'state'=>'Tennessee',        'abbr'=>'TN'],
    ['name'=>'Portland',         'state'=>'Oregon',           'abbr'=>'OR'],
    ['name'=>'Baltimore',        'state'=>'Maryland',         'abbr'=>'MD'],
    ['name'=>'Milwaukee',        'state'=>'Wisconsin',        'abbr'=>'WI'],
    ['name'=>'Albuquerque',      'state'=>'New Mexico',       'abbr'=>'NM'],
    ['name'=>'Tucson',           'state'=>'Arizona',          'abbr'=>'AZ'],
    ['name'=>'Fresno',           'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Sacramento',       'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Mesa',             'state'=>'Arizona',          'abbr'=>'AZ'],
    ['name'=>'Kansas City',      'state'=>'Missouri',         'abbr'=>'MO'],
    ['name'=>'Atlanta',          'state'=>'Georgia',          'abbr'=>'GA'],
    ['name'=>'Omaha',            'state'=>'Nebraska',         'abbr'=>'NE'],
    ['name'=>'Colorado Springs', 'state'=>'Colorado',         'abbr'=>'CO'],
    ['name'=>'Raleigh',          'state'=>'North Carolina',   'abbr'=>'NC'],
    ['name'=>'Long Beach',       'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Virginia Beach',   'state'=>'Virginia',         'abbr'=>'VA'],
    ['name'=>'Minneapolis',      'state'=>'Minnesota',        'abbr'=>'MN'],
    ['name'=>'Tampa',            'state'=>'Florida',          'abbr'=>'FL'],
    ['name'=>'New Orleans',      'state'=>'Louisiana',        'abbr'=>'LA'],
    ['name'=>'Arlington',        'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'Bakersfield',      'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Honolulu',         'state'=>'Hawaii',           'abbr'=>'HI'],
    ['name'=>'Anaheim',          'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Aurora',           'state'=>'Colorado',         'abbr'=>'CO'],
    ['name'=>'Santa Ana',        'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Corpus Christi',   'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'Riverside',        'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Lexington',        'state'=>'Kentucky',         'abbr'=>'KY'],
    ['name'=>'St. Louis',        'state'=>'Missouri',         'abbr'=>'MO'],
    ['name'=>'Pittsburgh',       'state'=>'Pennsylvania',     'abbr'=>'PA'],
    ['name'=>'Anchorage',        'state'=>'Alaska',           'abbr'=>'AK'],
    ['name'=>'Cincinnati',       'state'=>'Ohio',             'abbr'=>'OH'],
    ['name'=>'Greensboro',       'state'=>'North Carolina',   'abbr'=>'NC'],
    ['name'=>'Toledo',           'state'=>'Ohio',             'abbr'=>'OH'],
    ['name'=>'Newark',           'state'=>'New Jersey',       'abbr'=>'NJ'],
    ['name'=>'Plano',            'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'Henderson',        'state'=>'Nevada',           'abbr'=>'NV'],
    ['name'=>'Orlando',          'state'=>'Florida',          'abbr'=>'FL'],
    ['name'=>'Chandler',         'state'=>'Arizona',          'abbr'=>'AZ'],
    ['name'=>'Laredo',           'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'Madison',          'state'=>'Wisconsin',        'abbr'=>'WI'],
    ['name'=>'Durham',           'state'=>'North Carolina',   'abbr'=>'NC'],
    ['name'=>'Lubbock',          'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'Garland',          'state'=>'Texas',            'abbr'=>'TX'],
    ['name'=>'Glendale',         'state'=>'Arizona',          'abbr'=>'AZ'],
    ['name'=>'Hialeah',          'state'=>'Florida',          'abbr'=>'FL'],
    ['name'=>'Reno',             'state'=>'Nevada',           'abbr'=>'NV'],
    ['name'=>'Baton Rouge',      'state'=>'Louisiana',        'abbr'=>'LA'],
    ['name'=>'Irvine',           'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Chesapeake',       'state'=>'Virginia',         'abbr'=>'VA'],
    ['name'=>'Scottsdale',       'state'=>'Arizona',          'abbr'=>'AZ'],
    ['name'=>'Fremont',          'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Gilbert',          'state'=>'Arizona',          'abbr'=>'AZ'],
    ['name'=>'San Bernardino',   'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Birmingham',       'state'=>'Alabama',          'abbr'=>'AL'],
    ['name'=>'Rochester',        'state'=>'New York',         'abbr'=>'NY'],
    ['name'=>'Richmond',         'state'=>'Virginia',         'abbr'=>'VA'],
    ['name'=>'Spokane',          'state'=>'Washington',       'abbr'=>'WA'],
    ['name'=>'Des Moines',       'state'=>'Iowa',             'abbr'=>'IA'],
    ['name'=>'Montgomery',       'state'=>'Alabama',          'abbr'=>'AL'],
    ['name'=>'Modesto',          'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Fayetteville',     'state'=>'North Carolina',   'abbr'=>'NC'],
    ['name'=>'Tacoma',           'state'=>'Washington',       'abbr'=>'WA'],
    ['name'=>'Shreveport',       'state'=>'Louisiana',        'abbr'=>'LA'],
    ['name'=>'Akron',            'state'=>'Ohio',             'abbr'=>'OH'],
    ['name'=>'Yonkers',          'state'=>'New York',         'abbr'=>'NY'],
    ['name'=>'Huntington Beach', 'state'=>'California',       'abbr'=>'CA'],
    ['name'=>'Salt Lake City',   'state'=>'Utah',             'abbr'=>'UT'],
    ['name'=>'Tallahassee',      'state'=>'Florida',          'abbr'=>'FL'],
    ['name'=>'Knoxville',        'state'=>'Tennessee',        'abbr'=>'TN'],
    ['name'=>'Worcester',        'state'=>'Massachusetts',    'abbr'=>'MA'],
    ['name'=>'Boston',           'state'=>'Massachusetts',    'abbr'=>'MA'],
    ['name'=>'Providence',       'state'=>'Rhode Island',     'abbr'=>'RI'],
];

// ── Progress tracking ─────────────────────────────────────────────────────

$progress = file_exists($PROGRESS) ? json_decode(file_get_contents($PROGRESS), true) : ['done' => []];
$done_set  = array_flip($progress['done']);

$total     = count($CATEGORIES) * count($CITIES);
$skipped   = count($done_set);
$imported  = 0;
$errors    = 0;
$req_count = $skipped; // count previously done requests toward cap

log_msg("Starting scrape: " . count($CATEGORIES) . " categories × " . count($CITIES) . " cities = $total searches");
log_msg("Already done: $skipped — resuming from where we left off");
log_msg("Hard cap: $MAX_REQUESTS requests (~\$" . number_format($MAX_REQUESTS * 0.017, 2) . " max cost)");

// ── Main loop ─────────────────────────────────────────────────────────────

foreach ($CATEGORIES as $category) {
    foreach ($CITIES as $city) {
        $key = $category . '|' . $city['name'] . ',' . $city['abbr'];
        if (isset($done_set[$key])) continue; // already done

        // Hard cap check
        if ($req_count >= $MAX_REQUESTS) {
            log_msg("HARD CAP REACHED ($MAX_REQUESTS requests). Stopping safely. Run again next month or increase cap.");
            break 2;
        }

        $query   = "$category in {$city['name']}, {$city['state']}";
        $places  = places_search($query, $API_KEY, $MAX_RESULTS);
        $count   = 0;
        $req_count++;

        foreach ($places as $place) {
            if (import_place($db, $place, $category, $city)) $count++;
        }

        $imported += $count;
        $progress['done'][] = $key;
        file_put_contents($PROGRESS, json_encode($progress));

        $done_count = count($progress['done']);
        log_msg("[$done_count/$total] $query → $count imported");

        usleep($DELAY_MS * 1000);
    }
}

log_msg("Done! Total imported: $imported | Errors: $errors");

// ── Google Places API ─────────────────────────────────────────────────────

function places_search(string $query, string $key, int $max): array {
    $url  = 'https://places.googleapis.com/v1/places:searchText';
    $body = json_encode([
        'textQuery'      => $query,
        'maxResultCount' => $max,
        'languageCode'   => 'en',
    ]);
    $fields = 'places.id,places.displayName,places.formattedAddress,places.nationalPhoneNumber,places.websiteUri,places.rating,places.userRatingCount,places.location,places.regularOpeningHours,places.primaryType';
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nX-Goog-Api-Key: $key\r\nX-Goog-FieldMask: $fields\r\n",
        'content' => $body,
        'timeout' => 15,
        'ignore_errors' => true,
    ]]);
    $raw  = @file_get_contents($url, false, $ctx);
    $data = json_decode($raw, true);
    if (isset($data['error'])) {
        log_msg("API error: " . ($data['error']['message'] ?? 'unknown'));
        return [];
    }
    return $data['places'] ?? [];
}

// ── Import one place into DB ──────────────────────────────────────────────

function import_place(PDO $db, array $p, string $category, array $city): bool {
    $name     = $p['displayName']['text'] ?? '';
    $address  = $p['formattedAddress']    ?? '';
    $phone    = $p['nationalPhoneNumber'] ?? '';
    $website  = $p['websiteUri']          ?? '';
    $rating   = $p['rating']              ?? null;
    $reviews  = $p['userRatingCount']     ?? 0;
    $place_id = $p['id']                  ?? null;
    $lat      = $p['location']['latitude']  ?? null;
    $lng      = $p['location']['longitude'] ?? null;
    $hours    = isset($p['regularOpeningHours']['weekdayDescriptions'])
                ? json_encode($p['regularOpeningHours']['weekdayDescriptions'])
                : null;

    if (!$name || !$place_id) return false;

    // Find state
    $stmt = $db->prepare('SELECT id FROM states WHERE abbreviation = ?');
    $stmt->execute([strtoupper($city['abbr'])]);
    $state_id = $stmt->fetchColumn();
    if (!$state_id) return false;

    // Find or create city
    $city_slug = slugify($city['name']);
    $stmt = $db->prepare('SELECT id FROM cities WHERE state_id = ? AND slug = ?');
    $stmt->execute([$state_id, $city_slug]);
    $city_id = $stmt->fetchColumn();
    if (!$city_id) {
        $stmt = $db->prepare('INSERT INTO cities (state_id, name, slug, lat, lng) VALUES (?,?,?,?,?)');
        $stmt->execute([$state_id, $city['name'], $city_slug, $lat, $lng]);
        $city_id = $db->lastInsertId();
    }

    // Find category
    $stmt = $db->prepare('SELECT id FROM categories WHERE name LIKE ?');
    $stmt->execute(['%' . explode(' ', $category)[0] . '%']);
    $cat_id = $stmt->fetchColumn() ?: null;

    // Upsert business
    $biz_slug = slugify($name) . '-' . $city_slug;
    $stmt = $db->prepare('
        INSERT INTO businesses (name, slug, address, city_id, phone, website, rating, review_count, gmb_place_id, hours)
        VALUES (?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            name=VALUES(name), address=VALUES(address), phone=VALUES(phone),
            website=VALUES(website), rating=VALUES(rating), review_count=VALUES(review_count),
            hours=VALUES(hours), updated_at=NOW()
    ');
    $stmt->execute([$name, $biz_slug, $address, $city_id, $phone ?: null, $website ?: null, $rating, $reviews, $place_id, $hours]);
    $biz_id = $db->lastInsertId() ?: get_biz_id($db, $place_id);

    // Link category
    if ($cat_id && $biz_id) {
        $db->prepare('INSERT IGNORE INTO business_categories (business_id, category_id) VALUES (?,?)')
           ->execute([$biz_id, $cat_id]);
    }

    return true;
}

function get_biz_id(PDO $db, string $place_id): ?int {
    $stmt = $db->prepare('SELECT id FROM businesses WHERE gmb_place_id = ?');
    $stmt->execute([$place_id]);
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
