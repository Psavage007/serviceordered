<?php
require_once 'includes/seo_head.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head(['title' => 'Page Not Found | ServiceOrdered', 'noindex' => true]); ?>
</head>
<body>
<nav><div class="container nav-inner"><a href="/" class="nav-logo">Service<span>Ordered</span></a></div></nav>
<div class="container" style="padding:5rem 1.5rem;text-align:center">
    <div style="font-size:4rem">🔍</div>
    <h1 style="font-size:2rem;margin:1rem 0 .5rem;color:var(--navy)">Page Not Found</h1>
    <p style="color:var(--gray-400);margin-bottom:2rem">That page doesn't exist. Try searching for a contractor.</p>
    <a href="/" class="btn btn-primary" style="font-size:1rem;padding:.75rem 2rem">Back to Home</a>
</div>
</body>
</html>
