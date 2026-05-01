<?php
/**
 * Apify Webhook Receiver
 * Receives completed dataset from Apify Google Maps scraper runs
 * and imports businesses into the database.
 *
 * Configure in Apify: Webhook URL = https://serviceordered.com/api/webhook.php
 * Secret token set via APIFY_WEBHOOK_SECRET env var
 */
require_once '../includes/db.php';
require_once '../includes/helpers.php';

// Verify secret token
$secret = getenv('APIFY_WEBHOOK_SECRET') ?: '';
$token  = $_GET['token'] ?? $_SERVER['HTTP_X_APIFY_TOKEN'] ?? '';
if ($secret && $token !== $secret) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Accept JSON body or array from Apify dataset
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// Apify sends { eventType, eventData: { actorRunId } } on run finish
// Or we can receive items directly if called as dataset export
$items = [];
if (isset($data['resource']['defaultDatasetId'])) {
    // Fetch dataset items via Apify API
    $dataset_id = $data['resource']['defaultDatasetId'];
    $api_token  = getenv('APIFY_API_TOKEN') ?: '';
    $url        = "https://api.apify.com/v2/datasets/{$dataset_id}/items?clean=true&format=json";
    $ctx = stream_context_create(['http' => [
        'header'  => "Authorization: Bearer {$api_token}\r\n",
        'timeout' => 30,
    ]]);
    $body  = @file_get_contents($url, false, $ctx);
    $items = json_decode($body, true) ?? [];
} elseif (is_array($data)) {
    $items = $data;
}

$db      = get_db();
$imported = 0;
$skipped  = 0;

foreach ($items as $item) {
    try {
        $name     = trim($item['title'] ?? $item['name'] ?? '');
        $address  = trim($item['address'] ?? $item['street'] ?? '');
        $phone    = trim($item['phone']   ?? $item['phoneUnformatted'] ?? '');
        $website  = trim($item['website'] ?? '');
        $rating   = isset($item['totalScore']) ? (float)$item['totalScore'] : (isset($item['rating']) ? (float)$item['rating'] : null);
        $reviews  = (int)($item['reviewsCount'] ?? $item['reviews'] ?? 0);
        $place_id = $item['placeId'] ?? $item['place_id'] ?? null;
        $gmb_url  = $item['url']     ?? $item['gmb_url']  ?? null;
        $category = trim($item['categoryName'] ?? $item['category'] ?? '');
        $city_name= trim($item['city']  ?? extract_city($address) ?? '');
        $state_abbr=trim($item['state'] ?? extract_state($address) ?? '');
        $lat      = $item['location']['lat']  ?? $item['lat'] ?? null;
        $lng      = $item['location']['lng']  ?? $item['lng'] ?? null;
        $hours    = $item['openingHours']     ?? $item['hours'] ?? null;

        if (!$name || !$city_name || !$state_abbr) { $skipped++; continue; }

        // Find or create state
        $stmt = $db->prepare('SELECT id FROM states WHERE abbreviation = ? OR slug = ?');
        $stmt->execute([strtoupper($state_abbr), slugify($state_abbr)]);
        $state_row = $stmt->fetch();
        if (!$state_row) { $skipped++; continue; }

        // Find or create city
        $city_slug = slugify($city_name);
        $stmt = $db->prepare('SELECT id FROM cities WHERE state_id = ? AND slug = ?');
        $stmt->execute([$state_row['id'], $city_slug]);
        $city_row = $stmt->fetch();
        if (!$city_row) {
            $stmt = $db->prepare('INSERT INTO cities (state_id, name, slug, lat, lng) VALUES (?,?,?,?,?)');
            $stmt->execute([$state_row['id'], $city_name, $city_slug, $lat, $lng]);
            $city_id = $db->lastInsertId();
        } else {
            $city_id = $city_row['id'];
        }

        // Find or create category
        $cat_slug = slugify($category);
        $stmt = $db->prepare('SELECT id FROM categories WHERE slug = ? OR name LIKE ?');
        $stmt->execute([$cat_slug, '%' . $category . '%']);
        $cat_row = $stmt->fetch();
        $cat_id  = $cat_row ? $cat_row['id'] : null;

        // Upsert business
        $biz_slug = slugify($name) . '-' . $city_slug;
        $stmt = $db->prepare('
            INSERT INTO businesses (name, slug, address, city_id, phone, website, rating, review_count, gmb_place_id, gmb_url, hours)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                name=VALUES(name), address=VALUES(address), phone=VALUES(phone),
                website=VALUES(website), rating=VALUES(rating), review_count=VALUES(review_count),
                gmb_url=VALUES(gmb_url), hours=VALUES(hours), updated_at=NOW()
        ');
        $stmt->execute([
            $name, $biz_slug, $address, $city_id, $phone, $website ?: null,
            $rating, $reviews, $place_id, $gmb_url,
            $hours ? json_encode($hours) : null,
        ]);
        $biz_id = $db->lastInsertId() ?: get_business_id_by_slug($db, $biz_slug);

        // Link to category
        if ($cat_id && $biz_id) {
            $db->prepare('INSERT IGNORE INTO business_categories (business_id, category_id) VALUES (?,?)')
               ->execute([$biz_id, $cat_id]);
        }

        $imported++;

    } catch (Exception $e) {
        error_log('Webhook import error: ' . $e->getMessage());
        $skipped++;
    }
}

http_response_code(200);
echo json_encode(['imported' => $imported, 'skipped' => $skipped, 'total' => count($items)]);

// Helpers
function extract_city(string $address): string {
    // "123 Main St, Houston, TX 77001" → "Houston"
    $parts = array_map('trim', explode(',', $address));
    return count($parts) >= 2 ? $parts[count($parts) - 2] : '';
}

function extract_state(string $address): string {
    // "123 Main St, Houston, TX 77001" → "TX"
    if (preg_match('/,\s*([A-Z]{2})\s*\d{5}/', $address, $m)) return $m[1];
    return '';
}

function get_business_id_by_slug(PDO $db, string $slug): ?int {
    $stmt = $db->prepare('SELECT id FROM businesses WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetchColumn() ?: null;
}
