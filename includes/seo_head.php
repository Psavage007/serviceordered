<?php
function seo_head(array $seo): void {
    $site_name = 'ServiceOrdered';
    $site_url  = 'https://serviceordered.com';
    $title     = htmlspecialchars($seo['title']       ?? $site_name);
    $desc      = htmlspecialchars($seo['description'] ?? 'Find specialty contractors by state and city. Detailed listings for every trade — from electrical and plumbing to radon mitigation and dock builders.');
    $canonical = htmlspecialchars($seo['canonical']   ?? $site_url);
    $noindex   = !empty($seo['noindex']);
    $jsonld    = $seo['jsonld'] ?? null;
    $ga_id     = getenv('GA_MEASUREMENT_ID') ?: '';
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <meta name="description" content="<?= $desc ?>">
    <meta name="robots" content="<?= $noindex ? 'noindex,nofollow' : 'index,follow' ?>">
    <link rel="canonical" href="<?= $canonical ?>">
    <meta property="og:type"        content="website">
    <meta property="og:title"       content="<?= $title ?>">
    <meta property="og:description" content="<?= $desc ?>">
    <meta property="og:url"         content="<?= $canonical ?>">
    <meta property="og:site_name"   content="<?= htmlspecialchars($site_name) ?>">
    <meta name="twitter:card"        content="summary">
    <meta name="twitter:title"       content="<?= $title ?>">
    <meta name="twitter:description" content="<?= $desc ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if ($ga_id): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga_id) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= htmlspecialchars($ga_id) ?>');</script>
    <?php endif; ?>
    <?php if ($jsonld): ?>
    <script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>
<?php
}
