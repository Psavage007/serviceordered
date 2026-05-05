<?php
require_once '../includes/db.php';
require_once '../includes/user_auth.php';
require_once 'layout.php';

$user = auth_check();
$db   = get_db();

// If they already have a business, go to profile
$business = user_get_business($db, $user['id']);
if ($business && $business['onboarding_complete']) {
    header('Location: /dashboard/profile.php');
    exit;
}

$error   = '';
$success = false;
$step    = (int)($_GET['step'] ?? 1);
$biz_id  = $business['id'] ?? null;

// Load categories for dropdown
$categories = $db->query('SELECT id, name, icon FROM categories ORDER BY group_name, sort_order')->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_step = (int)($_POST['step'] ?? 1);

    if ($posted_step === 1) {
        // Step 1: Business basics
        $name    = trim($_POST['name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (!$name) {
            $error = 'Business name is required.';
        } else {
            $slug = slugify($name) . '-' . time();
            if ($biz_id) {
                $db->prepare('UPDATE businesses SET name=?,phone=?,email=?,website=?,address=? WHERE id=?')
                   ->execute([$name, $phone, $email, $website, $address, $biz_id]);
            } else {
                $db->prepare('INSERT INTO businesses (name,slug,phone,email,website,address) VALUES (?,?,?,?,?,?)')
                   ->execute([$name, $slug, $phone, $email, $website, $address]);
                $biz_id = $db->lastInsertId();
                // Link business to user via claimed_businesses (auto-approved for new profiles)
                $db->prepare('INSERT INTO claimed_businesses (business_id,user_id,status) VALUES (?,?,"approved")')
                   ->execute([$biz_id, $user['id']]);
                $db->prepare('UPDATE users SET role="business" WHERE id=?')->execute([$user['id']]);
                $_SESSION['user']['role'] = 'business';
            }
            header('Location: /dashboard/onboarding.php?step=2&biz=' . $biz_id);
            exit;
        }

    } elseif ($posted_step === 2) {
        // Step 2: Services & coverage
        $biz_id     = (int)($_POST['biz_id'] ?? 0);
        $cat_id     = (int)($_POST['category_id'] ?? 0);
        $service_area = trim($_POST['service_area'] ?? '');
        $free_est   = isset($_POST['free_estimates']) ? 1 : 0;
        $min_job    = trim($_POST['min_job_size'] ?? '');

        $db->prepare('UPDATE businesses SET service_area=?,free_estimates=?,min_job_size=? WHERE id=?')
           ->execute([$service_area, $free_est, $min_job, $biz_id]);

        if ($cat_id) {
            $db->prepare('INSERT IGNORE INTO business_categories (business_id,category_id) VALUES (?,?)')->execute([$biz_id, $cat_id]);
        }

        header('Location: /dashboard/onboarding.php?step=3&biz=' . $biz_id);
        exit;

    } elseif ($posted_step === 3) {
        // Step 3: Credentials
        $biz_id      = (int)($_POST['biz_id'] ?? 0);
        $years       = (int)($_POST['years_in_business'] ?? 0) ?: null;
        $license     = trim($_POST['license_number'] ?? '');
        $insured     = isset($_POST['insured']) ? 1 : 0;
        $employees   = trim($_POST['num_employees'] ?? '');

        $db->prepare('UPDATE businesses SET years_in_business=?,license_number=?,insured=?,num_employees=? WHERE id=?')
           ->execute([$years, $license, $insured, $employees, $biz_id]);

        header('Location: /dashboard/onboarding.php?step=4&biz=' . $biz_id);
        exit;

    } elseif ($posted_step === 4) {
        // Step 4: About — final step
        $biz_id  = (int)($_POST['biz_id'] ?? 0);
        $desc    = trim($_POST['description'] ?? '');

        $db->prepare('UPDATE businesses SET description=?,onboarding_complete=1 WHERE id=?')
           ->execute([$desc, $biz_id]);

        $success = true;
        $step    = 5;
    }
}

$biz_id = $biz_id ?? (int)($_GET['biz'] ?? 0);

// Load current biz data if we have it
$biz = $biz_id ? $db->prepare('SELECT * FROM businesses WHERE id=?') : null;
if ($biz) { $biz->execute([$biz_id]); $biz = $biz->fetch(); }

dash_head('Set Up Your Business', 'profile');

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}
?>

<div class="dash-page-title">Set Up Your Business Profile</div>
<p class="dash-page-sub">Tell us about your business so customers can find and contact you.</p>

<!-- Progress bar -->
<?php if ($step < 5): ?>
<div style="display:flex;gap:.5rem;margin-bottom:2rem">
    <?php
    $steps = ['Business Info','Services','Credentials','About You'];
    foreach ($steps as $i => $label):
        $num = $i + 1;
        $active = $num === $step;
        $done   = $num < $step;
    ?>
    <div style="flex:1;text-align:center">
        <div style="width:32px;height:32px;border-radius:50%;margin:0 auto .4rem;display:flex;align-items:center;justify-content:center;font-size:.82rem;font-weight:700;
            background:<?= $done ? 'var(--blue)' : ($active ? 'var(--blue)' : 'var(--gray-200)') ?>;
            color:<?= ($done||$active) ? '#fff' : 'var(--gray-400)' ?>">
            <?= $done ? '✓' : $num ?>
        </div>
        <div style="font-size:.75rem;font-weight:<?= $active?'700':'500' ?>;color:<?= $active?'var(--blue)':'var(--gray-400)' ?>"><?= $label ?></div>
    </div>
    <?php if ($num < 4): ?>
    <div style="flex:0 0 2rem;display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem">
        <div style="height:2px;width:100%;background:<?= $done?'var(--blue)':'var(--gray-200)' ?>"></div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="dash-alert dash-alert-red"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($step === 1): ?>
<!-- ── Step 1: Business Basics ── -->
<div class="dash-card">
    <div class="dash-card-title">Step 1 of 4 — Business Information</div>
    <form method="POST">
        <input type="hidden" name="step" value="1">
        <div class="field-group">
            <label>Business Name *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($biz['name'] ?? '') ?>" placeholder="e.g. Smith Electric LLC" required autofocus>
        </div>
        <div class="field-row">
            <div class="field-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($biz['phone'] ?? '') ?>" placeholder="(555) 555-5555">
            </div>
            <div class="field-group">
                <label>Business Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($biz['email'] ?? '') ?>" placeholder="you@yourbusiness.com">
            </div>
        </div>
        <div class="field-group">
            <label>Website</label>
            <input type="url" name="website" value="<?= htmlspecialchars($biz['website'] ?? '') ?>" placeholder="https://yourbusiness.com">
        </div>
        <div class="field-group">
            <label>Business Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($biz['address'] ?? '') ?>" placeholder="123 Main St, City, State, ZIP">
            <div class="field-hint">Your street address helps customers find you locally.</div>
        </div>
        <button type="submit" class="btn-save">Continue →</button>
    </form>
</div>

<?php elseif ($step === 2): ?>
<!-- ── Step 2: Services & Coverage ── -->
<div class="dash-card">
    <div class="dash-card-title">Step 2 of 4 — Services &amp; Coverage</div>
    <form method="POST">
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="biz_id" value="<?= $biz_id ?>">

        <div class="field-group">
            <label>Primary Service Category *</label>
            <select name="category_id" required>
                <option value="">Select your main trade...</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="field-hint">Choose the category that best describes your primary service.</div>
        </div>

        <div class="field-group">
            <label>Service Area</label>
            <input type="text" name="service_area" value="<?= htmlspecialchars($biz['service_area'] ?? '') ?>"
                   placeholder="e.g. Greater Phoenix, AZ — 30 mile radius">
            <div class="field-hint">Describe the cities or radius you serve so customers know if you cover their area.</div>
        </div>

        <div class="field-group">
            <label>Minimum Job Size</label>
            <select name="min_job_size">
                <option value="">No minimum</option>
                <option value="$200">$200</option>
                <option value="$500">$500</option>
                <option value="$1,000">$1,000</option>
                <option value="$2,500">$2,500</option>
                <option value="$5,000">$5,000</option>
                <option value="$10,000+">$10,000+</option>
            </select>
        </div>

        <div class="field-group">
            <div class="toggle-wrap">
                <input type="checkbox" name="free_estimates" id="free_estimates" <?= !empty($biz['free_estimates']) ? 'checked' : '' ?>>
                <label for="free_estimates" style="margin:0;font-size:.9rem;font-weight:500;color:var(--gray-700)">We offer free estimates</label>
            </div>
        </div>

        <div style="display:flex;gap:.75rem">
            <a href="/dashboard/onboarding.php?step=1&biz=<?= $biz_id ?>" class="btn-save" style="background:var(--gray-200);color:var(--gray-700);text-decoration:none">← Back</a>
            <button type="submit" class="btn-save">Continue →</button>
        </div>
    </form>
</div>

<?php elseif ($step === 3): ?>
<!-- ── Step 3: Credentials ── -->
<div class="dash-card">
    <div class="dash-card-title">Step 3 of 4 — Credentials &amp; Experience</div>
    <form method="POST">
        <input type="hidden" name="step" value="3">
        <input type="hidden" name="biz_id" value="<?= $biz_id ?>">

        <div class="field-row">
            <div class="field-group">
                <label>Years in Business</label>
                <input type="number" name="years_in_business" min="0" max="200"
                       value="<?= htmlspecialchars($biz['years_in_business'] ?? '') ?>" placeholder="e.g. 12">
            </div>
            <div class="field-group">
                <label>Number of Employees</label>
                <select name="num_employees">
                    <option value="">Select...</option>
                    <?php foreach (['Just me (solo)','2–5','6–10','11–25','26–50','50+'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($biz['num_employees'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field-group">
            <label>Contractor License Number</label>
            <input type="text" name="license_number" value="<?= htmlspecialchars($biz['license_number'] ?? '') ?>"
                   placeholder="e.g. LIC-123456">
            <div class="field-hint">Your state contractor license number. Customers look for this when hiring.</div>
        </div>

        <div class="field-group">
            <div class="toggle-wrap">
                <input type="checkbox" name="insured" id="insured" <?= !empty($biz['insured']) ? 'checked' : '' ?>>
                <label for="insured" style="margin:0;font-size:.9rem;font-weight:500;color:var(--gray-700)">We carry general liability insurance</label>
            </div>
        </div>

        <div style="display:flex;gap:.75rem">
            <a href="/dashboard/onboarding.php?step=2&biz=<?= $biz_id ?>" class="btn-save" style="background:var(--gray-200);color:var(--gray-700);text-decoration:none">← Back</a>
            <button type="submit" class="btn-save">Continue →</button>
        </div>
    </form>
</div>

<?php elseif ($step === 4): ?>
<!-- ── Step 4: About ── -->
<div class="dash-card">
    <div class="dash-card-title">Step 4 of 4 — About Your Business</div>
    <form method="POST">
        <input type="hidden" name="step" value="4">
        <input type="hidden" name="biz_id" value="<?= $biz_id ?>">

        <div class="field-group">
            <label>Business Description</label>
            <textarea name="description" rows="5" placeholder="Tell customers about your business — what you specialize in, your experience, why they should choose you..."><?= htmlspecialchars($biz['description'] ?? '') ?></textarea>
            <div class="field-hint">A good description helps customers trust you before they call. Aim for 2–4 sentences.</div>
        </div>

        <div style="background:var(--blue-light);border-radius:8px;padding:1rem;margin-bottom:1.25rem;font-size:.85rem;color:var(--blue)">
            💡 <strong>Tip:</strong> Mention your specialty, how long you've been in business, and what areas you serve. Businesses with descriptions get 3× more profile views.
        </div>

        <div style="display:flex;gap:.75rem">
            <a href="/dashboard/onboarding.php?step=3&biz=<?= $biz_id ?>" class="btn-save" style="background:var(--gray-200);color:var(--gray-700);text-decoration:none">← Back</a>
            <button type="submit" class="btn-save">Complete Setup ✓</button>
        </div>
    </form>
</div>

<?php elseif ($step === 5): ?>
<!-- ── Step 5: Done ── -->
<div class="dash-card" style="text-align:center;padding:3rem 2rem">
    <div style="font-size:3rem;margin-bottom:1rem">🎉</div>
    <h2 style="font-size:1.4rem;font-weight:800;color:var(--gray-900);margin-bottom:.5rem">Your profile is live!</h2>
    <p style="color:var(--gray-500);font-size:.95rem;margin-bottom:2rem">
        Your business is now listed on ServiceOrdered. Here's what to do next to get more customers.
    </p>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:2rem;text-align:left">
        <a href="/dashboard/photos.php" style="display:block;padding:1.25rem;background:var(--gray-50);border-radius:10px;border:1.5px solid var(--gray-200);text-decoration:none;color:inherit">
            <div style="font-size:1.5rem;margin-bottom:.5rem">📷</div>
            <div style="font-weight:700;color:var(--gray-900);margin-bottom:.25rem">Add Photos</div>
            <div style="font-size:.82rem;color:var(--gray-500)">Businesses with photos get 5× more clicks</div>
        </a>
        <a href="/dashboard/services.php" style="display:block;padding:1.25rem;background:var(--gray-50);border-radius:10px;border:1.5px solid var(--gray-200);text-decoration:none;color:inherit">
            <div style="font-size:1.5rem;margin-bottom:.5rem">💼</div>
            <div style="font-weight:700;color:var(--gray-900);margin-bottom:.25rem">List Services</div>
            <div style="font-size:.82rem;color:var(--gray-500)">Add pricing so customers know what to expect</div>
        </a>
        <a href="/dashboard/" style="display:block;padding:1.25rem;background:var(--gray-50);border-radius:10px;border:1.5px solid var(--gray-200);text-decoration:none;color:inherit">
            <div style="font-size:1.5rem;margin-bottom:.5rem">📊</div>
            <div style="font-weight:700;color:var(--gray-900);margin-bottom:.25rem">View Dashboard</div>
            <div style="font-size:.82rem;color:var(--gray-500)">Track your profile performance</div>
        </a>
    </div>
    <?php if ($biz_id): ?>
    <?php
    $b = $db->prepare('SELECT slug FROM businesses WHERE id=?');
    $b->execute([$biz_id]);
    $slug = $b->fetchColumn();
    ?>
    <a href="/business/<?= htmlspecialchars($slug) ?>/" target="_blank" class="btn-save" style="display:inline-block;text-decoration:none">View Your Public Profile →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php dash_foot(); ?>
