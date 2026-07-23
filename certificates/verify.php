<?php
// ============================================
// EDUCORE - Certificate Verification
// ============================================
require_once __DIR__ . '/../includes/auth.php';

$db   = getDB();

// ── Clean URL enforcement ─────────────────────────────────────────────────
// Detect if the browser actually sent ?code=XXXX in the visible URL
// (not an internal rewrite). REQUEST_URI will contain '?code=' in that case.
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '?code=') !== false && strpos($requestUri, '/certificates/verify/') === false) {
    $clean = preg_replace('/[^A-Za-z0-9_-]/', '', $_GET['code'] ?? '');
    if ($clean) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . BASE_URL . '/certificates/verify/' . $clean);
        exit();
    }
}

// Code is passed via the htaccess rewrite as ?code=
$code = preg_replace('/[^A-Za-z0-9_-]/', '', trim($_GET['code'] ?? ''));

// Canonical base for building all verify links
$verifyBase = BASE_URL . '/certificates/verify';

$cert = null;

if ($code) {
    $stmt = $db->prepare("
        SELECT cert.*, co.title as course_title, co.level, co.duration, co.type as course_type,
               co.slug as course_slug,
               u.name as student_name, u.email as student_email,
               ins.name as instructor_name
        FROM certificates cert
        JOIN courses co  ON co.id  = cert.course_id
        JOIN users   u   ON u.id   = cert.user_id
        JOIN users   ins ON ins.id = co.instructor_id
        WHERE cert.certificate_code = ?
    ");
    $stmt->execute([$code]);
    $cert = $stmt->fetch();
}

$pageTitle = "Verify Certificate";
$minimalHeader = true;
include __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Verify page overrides ─────────────────────────────────── */
.verify-page {
    margin-top: 70px;
    min-height: 100vh;
    padding: 56px 0 80px;
    background: linear-gradient(160deg, #0f172a 0%, #1e293b 60%, #0f2017 100%);
}
.verify-wrap {
    max-width: 640px;
    margin: 0 auto;
    padding: 0 16px;
}

/* page heading */
.verify-page-title {
    font-size: 1.45rem;
    font-weight: 800;
    color: #f1f5f9;
    margin-bottom: 6px;
    letter-spacing: -0.3px;
    text-align: center;
}
.verify-page-sub {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 28px;
    text-align: center;
}

/* white card */
.verify-card {
    background: #ffffff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.35), 0 2px 8px rgba(0,0,0,0.2);
}

/* top accent bar */
.verify-card-bar {
    height: 5px;
    background: linear-gradient(90deg, #1e3a5f 0%, #22c55e 50%, #1d4ed8 100%);
}

.verify-card-body {
    padding: 40px 44px 36px;
}

/* ── Stamp ── */
.stamp-wrap {
    margin-bottom: 30px;
}

/* ── Congrats heading ── */
.congrats-label {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #1e293b;
    margin-bottom: 4px;
}
.congrats-sub {
    font-size: 15px;
    font-weight: 700;
    color: #1e3a5f;
    margin-bottom: 26px;
}

/* ── Details table ── */
.detail-list {
    list-style: none;
    padding: 0;
    margin: 0 0 28px;
    display: flex;
    flex-direction: column;
    gap: 0;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.detail-row {
    display: flex;
    align-items: center;
    gap: 0;
    border-bottom: 1px solid #e2e8f0;
}
.detail-row:last-child { border-bottom: none; }
.detail-row:nth-child(even) { background: #f8fafc; }
.detail-label {
    width: 150px;
    flex-shrink: 0;
    padding: 11px 16px;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-right: 1px solid #e2e8f0;
    background: #f1f5f9;
}
.detail-value {
    padding: 11px 16px;
    font-size: 13.5px;
    font-weight: 600;
    color: #0f172a;
    flex: 1;
    font-family: inherit;
}
.detail-value.mono {
    font-family: 'Courier New', monospace;
    font-size: 12.5px;
    color: #1e3a5f;
    letter-spacing: 0.5px;
}
.detail-value.accent { color: #15803d; }

/* ── Divider ── */
.verify-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 0 0 24px;
}

/* ── Action buttons ── */
.verify-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
.btn-verify-primary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 24px;
    border-radius: 50px;
    background: #1e3a5f;
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(30,58,95,0.25);
}
.btn-verify-primary:hover {
    background: #1e40af;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(30,58,95,0.35);
    color: #fff;
}
.btn-verify-outline {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px;
    border-radius: 50px;
    background: transparent;
    color: #1e3a5f;
    font-size: 14px;
    font-weight: 700;
    border: 2px solid #1e3a5f;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-verify-outline:hover {
    background: #1e3a5f;
    color: #fff;
    transform: translateY(-1px);
}

/* ── Security note ── */
.security-note {
    font-size: 12px;
    color: #94a3b8;
    font-style: italic;
    line-height: 1.7;
    border-top: 1px solid #e2e8f0;
    padding-top: 18px;
}
.security-note i { color: #cbd5e1; margin-right: 5px; }

/* ── Search form ── */
.verify-search-form {
    display: flex;
    gap: 10px;
    margin-top: 24px;
}
.verify-search-input {
    flex: 1;
    padding: 13px 18px;
    background: rgba(255,255,255,0.07);
    border: 1.5px solid rgba(255,255,255,0.12);
    border-radius: 50px;
    color: #f1f5f9;
    font-size: 14px;
    font-family: 'Courier New', monospace;
    outline: none;
    transition: border-color 0.2s, background 0.2s;
}
.verify-search-input::placeholder { color: #475569; }
.verify-search-input:focus {
    border-color: #22c55e;
    background: rgba(255,255,255,0.10);
}
.btn-verify-search {
    padding: 13px 28px;
    border-radius: 50px;
    background: #22c55e;
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    white-space: nowrap;
}
.btn-verify-search:hover {
    background: #16a34a;
    transform: translateY(-1px);
}

/* default state card */
.verify-default-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 18px;
    padding: 52px 44px;
    text-align: center;
}
</style>

<div class="verify-page">
<div class="verify-wrap">

    <?php if ($cert): ?>
    <!-- ════════════════════════════════════════
         VERIFIED
    ════════════════════════════════════════ -->
    <div class="verify-page-title">
        EDUCORE Verified Certificate &ndash; <?php echo e($cert['student_name']); ?>
    </div>
    <div class="verify-page-sub">This certificate has been verified as authentic and issued by EDUCORE.</div>

    <div class="verify-card">
        <div class="verify-card-bar"></div>
        <div class="verify-card-body">

            <!-- Stamp -->
            <div class="stamp-wrap">
                <img src="<?php echo BASE_URL; ?>/assets/images/verified-stamp.png"
                     alt="Verified Stamp"
                     style="width:140px;height:140px;object-fit:contain;filter:drop-shadow(0 6px 18px rgba(34,197,94,0.30));">
            </div>

            <!-- Congrats -->
            <div class="congrats-label">🎉 Congratulations 🎉</div>
            <div class="congrats-sub">It's a verified certificate from EDUCORE</div>

            <!-- Details table -->
            <ul class="detail-list">
                <?php
                $rows = [
                    ['Learner Full Name', $cert['student_name'],                         'accent'],
                    ['Course',           $cert['course_title'],                           'accent'],
                    ['Instructor',       $cert['instructor_name'],                        ''],
                    ['Level',            ucfirst($cert['level']),                         ''],
                    ['Duration',         e($cert['duration']),                            ''],
                    ['Issued On',        date('d/m/Y', strtotime($cert['issued_at'])),   'accent'],
                    ['Certificate ID',   $cert['certificate_code'],                       'mono'],
                ];
                foreach ($rows as [$label, $value, $cls]): ?>
                <li class="detail-row">
                    <div class="detail-label"><?php echo $label; ?></div>
                    <div class="detail-value <?php echo $cls; ?>"><?php echo e($value); ?></div>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="verify-divider"></div>

            <!-- Actions -->
            <div class="verify-actions">
                <a href="<?php echo BASE_URL; ?>/certificates/generate/<?php echo e($cert['course_slug']); ?>"
                   class="btn-verify-primary">
                    <i class="fas fa-eye"></i> View Certificate
                </a>
                <button class="btn-verify-outline"
                    onclick="navigator.clipboard.writeText('<?php echo $verifyBase . '/' . $cert['certificate_code']; ?>').then(()=>{
                        this.innerHTML='<i class=\'fas fa-check\'></i> Copied!';
                        setTimeout(()=>this.innerHTML='<i class=\'fas fa-copy\'></i> Copy Link',2200);
                    })">
                    <i class="fas fa-copy"></i> Copy Link
                </button>
            </div>

            <!-- Security note -->
            <p class="security-note">
                <i class="fas fa-lock"></i>
                The details accessed via the QR code are secure and will only display the information related to the learner's Course Completion.
            </p>

        </div>
    </div>

    <?php elseif ($code && !$cert): ?>
    <!-- ════════════════════════════════════════
         INVALID
    ════════════════════════════════════════ -->
    <div class="verify-page-title">Certificate Verification</div>
    <div class="verify-page-sub">We could not find a matching certificate for the provided ID.</div>

    <div class="verify-card">
        <div style="height:5px;background:linear-gradient(90deg,#b91c1c,#ef4444);"></div>
        <div class="verify-card-body" style="text-align:center;">
            <div class="stamp-wrap" style="display:flex;justify-content:center;">
                <img src="<?php echo BASE_URL; ?>/assets/images/verified-stamp.png"
                     alt="Invalid Stamp"
                     style="width:140px;height:140px;object-fit:contain;filter:grayscale(100%) sepia(100%) hue-rotate(300deg) saturate(4) brightness(0.85) drop-shadow(0 6px 18px rgba(239,68,68,0.30));">
            </div>
            <h3 style="font-size:18px;font-weight:800;color:#1e293b;margin-bottom:10px;">Certificate Not Found</h3>
            <p style="font-size:14px;color:#64748b;margin-bottom:8px;">
                The ID <code style="background:#fee2e2;color:#b91c1c;padding:2px 9px;border-radius:5px;font-size:13px;"><?php echo e($code); ?></code><br>
                does not match any record in our system.
            </p>
            <p style="font-size:13px;color:#94a3b8;font-style:italic;margin-top:14px;">
                Please check the certificate ID or scan the QR code directly from the original certificate.
            </p>
        </div>
    </div>

    <?php else: ?>
    <!-- ════════════════════════════════════════
         DEFAULT — no code yet
    ════════════════════════════════════════ -->
    <div class="verify-page-title">Certificate Verification</div>
    <div class="verify-page-sub">Confirm the authenticity of an EDUCORE certificate instantly.</div>

    <div class="verify-default-card">
        <div style="font-size:52px;margin-bottom:16px;">🛡️</div>
        <div style="font-size:18px;font-weight:800;color:#f1f5f9;margin-bottom:8px;">Verify a Certificate</div>
        <div style="font-size:14px;color:#64748b;max-width:400px;margin:0 auto;">
            Enter the Certificate ID printed on the certificate, or scan the QR code to verify its authenticity instantly.
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Search / re-verify (always shown) ── -->
     <!-- <div class="verify-search-form">
        <input type="text"
               id="codeInput"
               value="<?php echo e($code); ?>"
               placeholder="Enter Certificate ID  e.g. EDUCORE-95830B0BD5"
               class="verify-search-input"
               onkeydown="if(event.key==='Enter'){verifyCode();}">
        <button type="button" class="btn-verify-search" onclick="verifyCode()">
            <i class="fas fa-search"></i> Verify
        </button>
    </div> -->
    <!-- <script>
    function verifyCode() {
        var c = document.getElementById('codeInput').value.trim();
        if (c) window.location.href = '<?php echo $verifyBase; ?>/' + encodeURIComponent(c);
        else   window.location.href = '<?php echo $verifyBase; ?>';
    }
    </script>  -->

</div>
</div>

    <!-- Copyright bar -->
    <div style="text-align:center;padding:14px 0;font-size:12px;color:var(--text-muted);border-top:1px solid var(--border);margin-top:24px;">
        &copy; <?php echo date('Y'); ?> <a href="<?php echo BASE_URL; ?>/index.php" style="color:var(--primary);font-weight:600;text-decoration:none;">EDUCORE</a> — All rights reserved.
    </div>

</body>
</html>
