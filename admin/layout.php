<?php
// Usage: admin_layout_head('Page Title'); ... admin_layout_foot();
function admin_layout_head(string $title, string $active = '') {
    $name = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
    $nav = [
        'index'      => ['Dashboard',  '/admin/',                 '📊'],
        'businesses' => ['Businesses', '/admin/businesses.php',   '🏢'],
        'users'      => ['Users',      '/admin/users.php',        '👤'],
        'categories' => ['Categories', '/admin/categories.php',   '📂'],
        'claims'     => ['Claims',     '/admin/claims.php',       '✅'],
    ];
    echo '<!DOCTYPE html><html lang="en"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . htmlspecialchars($title) . ' | SO Admin</title>';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<link rel="stylesheet" href="/assets/css/style.css">';
    echo '<link rel="stylesheet" href="/admin/admin.css">';
    echo '</head><body class="admin-body">';
    echo '<div class="admin-wrap">';
    // Sidebar
    echo '<aside class="admin-sidebar">';
    echo '<div class="sidebar-logo">Service<span>Ordered</span></div>';
    echo '<div class="sidebar-label">Admin Panel</div>';
    echo '<nav class="sidebar-nav">';
    foreach ($nav as $key => [$label, $href, $icon]) {
        $cls = $active === $key ? ' active' : '';
        echo "<a href=\"{$href}\" class=\"sidebar-link{$cls}\">{$icon} {$label}</a>";
    }
    echo '</nav>';
    echo '<div class="sidebar-footer">';
    echo "<div class=\"sidebar-user\">{$name}</div>";
    echo '<a href="/admin/logout.php" class="sidebar-logout">Sign Out</a>';
    echo '</div></aside>';
    // Main
    echo '<div class="admin-main">';
    echo '<div class="admin-topbar"><h1 class="admin-page-title">' . htmlspecialchars($title) . '</h1></div>';
    echo '<div class="admin-content">';
}

function admin_layout_foot() {
    echo '</div></div></div></body></html>';
}
