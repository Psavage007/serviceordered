<?php
require_once 'includes/seo_head.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head([
    'title'       => 'About Us | ServiceOrdered',
    'description' => 'ServiceOrdered is the specialty contractor directory built to help homeowners and businesses find trusted local pros across all 50 US states.',
    'canonical'   => 'https://serviceordered.com/about.php',
]); ?>
</head>
<body>

<?php require_once 'includes/nav.php'; ?>

<div class="page-header">
    <div class="container">
        <h1>About ServiceOrdered</h1>
        <p>Built to connect homeowners with the right specialty contractor — fast.</p>
    </div>
</div>

<div class="container section" style="max-width:760px">

    <div class="info-box">
        <h2 style="font-size:1.2rem;font-weight:800;color:var(--gray-900);margin-bottom:.75rem">Our Mission</h2>
        <p style="font-size:.95rem;color:var(--gray-600);line-height:1.8">
            ServiceOrdered was built because finding a reliable specialty contractor shouldn't be hard.
            Whether you need a radon mitigation specialist, a dock builder, or a licensed electrician,
            most directories either don't list them or bury them under paid ads.
            We built a directory focused entirely on specialty trades — with real ratings, real phone numbers,
            and no lead fees standing between you and the pro you need.
        </p>
    </div>

    <div class="info-box">
        <h2 style="font-size:1.2rem;font-weight:800;color:var(--gray-900);margin-bottom:.75rem">What We Do</h2>
        <p style="font-size:.95rem;color:var(--gray-600);line-height:1.8">
            We index over 100,000 specialty contractor listings across all 50 US states,
            covering 50 trade categories from electrical and plumbing to solar installation,
            epoxy flooring, and structural engineering. Every listing includes verified Google ratings,
            real customer reviews, direct phone numbers, and business websites —
            so you can compare and contact contractors without any middleman.
        </p>
    </div>

    <div class="info-box">
        <h2 style="font-size:1.2rem;font-weight:800;color:var(--gray-900);margin-bottom:.75rem">For Contractors</h2>
        <p style="font-size:.95rem;color:var(--gray-600);line-height:1.8">
            Are you a contractor? Claim your free listing on ServiceOrdered to add photos, list your services and pricing,
            and stand out from competitors. Claiming your profile takes less than 5 minutes and is completely free.
        </p>
        <div style="margin-top:1.25rem;display:flex;gap:1rem;flex-wrap:wrap">
            <a href="/register.php" class="btn btn-primary" style="padding:.65rem 1.75rem;font-size:.95rem">Create Free Account</a>
            <a href="/search.php" class="btn btn-outline" style="padding:.65rem 1.75rem;font-size:.95rem">Search Contractors</a>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>

</body>
</html>
