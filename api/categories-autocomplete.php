<?php
require_once '../includes/db.php';
header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) { echo '[]'; exit; }

$db   = get_db();
$stmt = $db->prepare("SELECT name, slug FROM categories WHERE name LIKE ? ORDER BY name LIMIT 10");
$stmt->execute(['%' . $q . '%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(array_values($rows));
