<?php
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/seo_head.php';

$query      = trim($_GET['q']     ?? '');
$state_slug = trim($_GET['state'] ?? '');
$results    = [];
$searched   = false;

if ($query) {
    $searched = true;
    $db       = get_db();
    $state_id = null;

    if ($state_slug) {
        $st = get_state_by_slug($state_slug);
        $state_id = $st['id'] ?? null;
    }

    $sql    = '
        SELECT DISTINCT b.*, ci.name AS city_name, ci.slug AS city_slug,
               s.name AS state_name, s.slug AS state_slug, s.abbreviation AS state_abbr
        FROM businesses b
        LEFT JOIN cities ci ON ci.id = b.city_id
        LEFT JOIN states s  ON s.id  = ci.state_id
        WHERE b.name LIKE ?
    ';
    $params = ['%' . $query . '%'];

    if ($state_id) {
        $sql    .= ' AND ci.state_id = ?';
        $params[] = $state_id;
    }

    $sql    .= ' ORDER BY b.featured DESC, b.rating DESC LIMIT 40';
    $stmt    = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Also search by category name and aliases
    if (count($results) < 40) {
        $sql2 = '
            SELECT DISTINCT b.*, ci.name AS city_name, ci.slug AS city_slug,
                   s.name AS state_name, s.slug AS state_slug, s.abbreviation AS state_abbr
            FROM businesses b
            JOIN business_categories bc ON bc.business_id = b.id
            JOIN categories c ON c.id = bc.category_id
            LEFT JOIN cities ci ON ci.id = b.city_id
            LEFT JOIN states s  ON s.id  = ci.state_id
            WHERE (c.name LIKE ? OR c.aliases LIKE ?)
        ';
        $like = '%' . $query . '%';
        $p2 = [$like, $like];
        if ($state_id) { $sql2 .= ' AND ci.state_id = ?'; $p2[] = $state_id; }
        $sql2 .= ' ORDER BY b.featured DESC, b.rating DESC LIMIT 40';
        $stmt2 = $db->prepare($sql2);
        $stmt2->execute($p2);
        $cat_results = $stmt2->fetchAll();
        $existing_ids = array_column($results, 'id');
        foreach ($cat_results as $r) {
            if (!in_array($r['id'], $existing_ids)) $results[] = $r;
        }
    }
}

$states = get_db()->query('SELECT name, slug FROM states ORDER BY name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head([
    'title'     => $query ? 'Search results for "' . $query . '" | ServiceOrdered' : 'Search Specialty Contractors | ServiceOrdered',
    'description' => 'Search specialty contractors by name or service type across all 50 US states.',
    'canonical' => 'https://serviceordered.com/search.php' . ($query ? '?q=' . urlencode($query) : ''),
    'noindex'   => true,
]); ?>
</head>
<body>

<?php require_once 'includes/nav.php'; ?>

<div class="page-header">
    <div class="container">
        <h1>Search Contractors</h1>
        <div style="position:relative;max-width:660px;margin-top:1rem">
            <form class="search-box" action="/search.php" method="GET">
                <div class="search-field">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" name="q" id="search-q" value="<?= htmlspecialchars($query) ?>" placeholder="Service type or company name..." autocomplete="off">
                </div>
                <select name="state">
                    <option value="">All States</option>
                    <?php foreach ($states as $s): ?>
                    <option value="<?= htmlspecialchars($s['slug']) ?>" <?= $state_slug === $s['slug'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Search</button>
            </form>
            <ul id="ac-list" class="ac-dropdown" style="list-style:none;padding:0;margin:0"></ul>
        </div>
    </div>
</div>

<div class="container section">

    <?php if ($searched && empty($results)): ?>
    <div class="empty-state">
        <div style="font-size:3rem">🔍</div>
        <h2>No results for "<?= htmlspecialchars($query) ?>"</h2>
        <p>Try a different search term or <a href="/">browse by category</a>.</p>
    </div>

    <?php elseif (!empty($results)): ?>
    <p style="color:var(--gray-400);font-size:.88rem;margin-bottom:1rem">
        Found <strong><?= count($results) ?></strong> result<?= count($results) !== 1 ? 's' : '' ?>
        for "<strong><?= htmlspecialchars($query) ?></strong>"
    </p>
    <div class="businesses-grid">
        <?php foreach ($results as $biz): ?>
        <div class="business-card">
            <div class="biz-logo"><?= strtoupper(substr($biz['name'],0,2)) ?></div>
            <div class="biz-info">
                <div class="biz-name">
                    <a href="/business/<?= htmlspecialchars($biz['slug']) ?>/"><?= htmlspecialchars($biz['name']) ?></a>
                </div>
                <?php if ($biz['city_name']): ?>
                <div class="biz-address">📍 <?= htmlspecialchars($biz['city_name'] . ', ' . $biz['state_abbr']) ?></div>
                <?php endif; ?>
                <?php if ($biz['rating']): ?>
                <div class="biz-rating">
                    <span class="stars"><?= render_stars((float)$biz['rating']) ?></span>
                    <span class="rating-num"><?= number_format((float)$biz['rating'],1) ?></span>
                    <span class="review-count">(<?= number_format((int)$biz['review_count']) ?> reviews)</span>
                </div>
                <?php endif; ?>
            </div>
            <div class="biz-actions">
                <?php if ($biz['phone']): ?>
                <a href="tel:<?= preg_replace('/\D/','',$biz['phone']) ?>" class="btn btn-primary">📞 Call</a>
                <?php endif; ?>
                <a href="/business/<?= htmlspecialchars($biz['slug']) ?>/" class="btn btn-outline">View Profile</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty-state" style="padding:5rem 2rem">
        <div style="font-size:3rem">🔍</div>
        <h2>Search any specialty contractor</h2>
        <p>Search by company name, service type, or <a href="/">browse all categories</a>.</p>
    </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>

<script>
(function(){
    const input = document.getElementById('search-q');
    const list  = document.getElementById('ac-list');
    const form  = input && input.closest('form');
    if (!input || !list) return;
    let timer;

    const icon = `<svg class="ac-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>`;

    function open(items) {
        list.innerHTML = items.map(i =>
            `<li data-val="${i.name.replace(/"/g,'&quot;')}" style="list-style:none">${icon}<span>${i.name}</span></li>`
        ).join('');
        list.style.display = 'block';
        if (form) { form.style.borderBottomLeftRadius='0'; form.style.borderBottomRightRadius='0'; }
    }

    function close() {
        list.innerHTML=''; list.style.display='none';
        if (form) { form.style.borderBottomLeftRadius=''; form.style.borderBottomRightRadius=''; }
    }

    input.addEventListener('input', function(){
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { close(); return; }
        timer = setTimeout(() => {
            fetch('/api/categories-autocomplete.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(items => { items.length ? open(items) : close(); });
        }, 150);
    });

    list.addEventListener('mousedown', function(e){
        const li = e.target.closest('li');
        if (li) { input.value = li.dataset.val; close(); input.closest('form').submit(); }
    });

    document.addEventListener('click', function(e){
        if (!input.contains(e.target) && !list.contains(e.target)) close();
    });
})();
</script>

</body>
</html>
