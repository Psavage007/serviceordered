<?php
function dash_head(string $title, string $active = '') {
    $user = $_SESSION['user'];
    $nav = [
        'index'    => ['Overview',    '/dashboard/',                '📊'],
        'profile'  => ['Business Info','/dashboard/profile.php',   '🏢'],
        'photos'   => ['Photos',       '/dashboard/photos.php',    '📷'],
        'services' => ['Services',     '/dashboard/services.php',  '💼'],
        'claim'    => ['Claim Listing','/dashboard/claim.php',     '✅'],
    ];
    echo '<!DOCTYPE html><html lang="en"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . htmlspecialchars($title) . ' | ServiceOrdered</title>';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<link rel="stylesheet" href="/assets/css/style.css">';
    echo '<link rel="stylesheet" href="/dashboard/dashboard.css">';
    echo '</head><body class="dash-body">';
    echo '<div class="dash-wrap">';
    echo '<aside class="dash-sidebar">';
    echo '<a href="/" class="dash-logo">Service<span>Ordered</span></a>';
    echo '<nav class="dash-nav">';
    foreach ($nav as $key => [$label, $href, $icon]) {
        $cls = $active === $key ? ' active' : '';
        echo "<a href=\"{$href}\" class=\"dash-link{$cls}\">{$icon} <span>{$label}</span></a>";
    }
    echo '</nav>';
    echo '<div class="dash-sidebar-footer">';
    echo '<div class="dash-user-name">' . htmlspecialchars($user['name']) . '</div>';
    echo '<div class="dash-user-email">' . htmlspecialchars($user['email']) . '</div>';
    echo '<a href="/logout.php" class="dash-logout">Sign Out</a>';
    echo '</div></aside>';
    echo '<div class="dash-main">';
    echo '<div class="dash-content">';
}

function dash_foot() {
    echo '</div></div></div></body></html>';
}
