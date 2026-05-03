<?php
$current = basename($_SERVER['PHP_SELF'], '.php');
if (session_status() === PHP_SESSION_NONE) session_start();
$_nav_user = $_SESSION['user'] ?? null;
?>
<nav>
    <div class="container nav-inner">
        <a href="/" class="nav-logo">Service<span>Ordered</span></a>
        <ul class="nav-links">
            <li><a href="/">Home</a></li>
            <li><a href="/electrical/">Electrical</a></li>
            <li><a href="/plumbing/">Plumbing</a></li>
            <li><a href="/hvac/">HVAC</a></li>
            <li><a href="/search.php">All Services</a></li>
        </ul>
        <div class="nav-actions">
            <?php if ($_nav_user): ?>
                <a href="/dashboard/" class="nav-link-auth">My Dashboard</a>
                <a href="/logout.php" class="nav-link-auth">Sign Out</a>
            <?php else: ?>
                <a href="/login.php" class="nav-link-auth">Sign In</a>
                <a href="/register.php" class="nav-cta-outline">List Your Business</a>
            <?php endif; ?>
            <a href="/search.php" class="nav-cta">Find a Pro</a>
        </div>
    </div>
</nav>
