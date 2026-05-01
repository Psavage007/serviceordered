<?php
function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function current_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function render_stars(float $rating): string {
    $full  = floor($rating);
    $half  = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
}

function format_phone(string $phone): string {
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 10) {
        return '(' . substr($digits,0,3) . ') ' . substr($digits,3,3) . '-' . substr($digits,6);
    }
    return $phone;
}

function get_category_by_slug(string $slug): ?array {
    $db  = get_db();
    $stmt = $db->prepare('SELECT * FROM categories WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function get_state_by_slug(string $slug): ?array {
    $db   = get_db();
    $stmt = $db->prepare('SELECT * FROM states WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function get_city_by_slug(string $state_id, string $city_slug): ?array {
    $db   = get_db();
    $stmt = $db->prepare('SELECT * FROM cities WHERE state_id = ? AND slug = ?');
    $stmt->execute([$state_id, $city_slug]);
    return $stmt->fetch() ?: null;
}

function get_businesses(int $category_id, int $city_id, int $limit = 20, int $offset = 0): array {
    $db   = get_db();
    $stmt = $db->prepare('
        SELECT b.* FROM businesses b
        JOIN business_categories bc ON bc.business_id = b.id
        WHERE bc.category_id = ? AND b.city_id = ?
        ORDER BY b.featured DESC, b.rating DESC, b.review_count DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$category_id, $city_id, $limit, $offset]);
    return $stmt->fetchAll();
}

function count_businesses(int $category_id, int $city_id): int {
    $db   = get_db();
    $stmt = $db->prepare('
        SELECT COUNT(*) FROM businesses b
        JOIN business_categories bc ON bc.business_id = b.id
        WHERE bc.category_id = ? AND b.city_id = ?
    ');
    $stmt->execute([$category_id, $city_id]);
    return (int)$stmt->fetchColumn();
}

function get_all_categories(): array {
    $db = get_db();
    return $db->query('SELECT * FROM categories ORDER BY group_name, sort_order')->fetchAll();
}

function get_cities_with_listings(int $category_id, int $state_id): array {
    $db   = get_db();
    $stmt = $db->prepare('
        SELECT ci.*, COUNT(DISTINCT bc.business_id) as business_count
        FROM cities ci
        JOIN businesses b ON b.city_id = ci.id
        JOIN business_categories bc ON bc.business_id = b.id
        WHERE bc.category_id = ? AND ci.state_id = ?
        GROUP BY ci.id
        ORDER BY business_count DESC, ci.name ASC
    ');
    $stmt->execute([$category_id, $state_id]);
    return $stmt->fetchAll();
}

function get_states_with_listings(int $category_id): array {
    $db   = get_db();
    $stmt = $db->prepare('
        SELECT s.*, COUNT(DISTINCT bc.business_id) as business_count
        FROM states s
        JOIN cities ci ON ci.state_id = s.id
        JOIN businesses b ON b.city_id = ci.id
        JOIN business_categories bc ON bc.business_id = b.id
        WHERE bc.category_id = ?
        GROUP BY s.id
        ORDER BY business_count DESC, s.name ASC
    ');
    $stmt->execute([$category_id]);
    return $stmt->fetchAll();
}
