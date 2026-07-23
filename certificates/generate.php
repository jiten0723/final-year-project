<?php
// ============================================
// EDUCORE - Certificate Generator
// ============================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db     = getDB();
$userId = $_SESSION['user_id'];

// ── Clean URL enforcement ─────────────────────────────────────────────────
// If old ?course_id= URL is in the browser address bar, 301 to clean URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (isset($_GET['course_id']) && strpos($requestUri, '/certificates/generate/') === false) {
    $cid = (int)$_GET['course_id'];
    // Look up the slug so we can build the clean URL
    $slugRow = $db->prepare("SELECT slug FROM courses WHERE id = ?");
    $slugRow->execute([$cid]);
    $slugRow = $slugRow->fetchColumn();
    if ($slugRow) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . BASE_URL . '/certificates/generate/' . $slugRow);
        exit();
    }
}

// Accept either ?slug= (from htaccess rewrite) or ?course_id= (internal fallback)
$slug     = trim($_GET['slug'] ?? '');
$courseId = (int)($_GET['course_id'] ?? 0);

if (!$slug && !$courseId) {
    header("Location: " . BASE_URL . "/dashboard/student.php?tab=certificates");
    exit();
}

// Fetch course by slug or id
if ($slug) {
    $course = $db->prepare("SELECT co.*, u.name as instructor_name, u.signature_image as instructor_signature FROM courses co JOIN users u ON u.id=co.instructor_id WHERE co.slug=?");
    $course->execute([$slug]);
} else {
    $course = $db->prepare("SELECT co.*, u.name as instructor_name, u.signature_image as instructor_signature FROM courses co JOIN users u ON u.id=co.instructor_id WHERE co.id=?");
    $course->execute([$courseId]);
}
$course = $course->fetch();

if (!$course) { header("Location: " . BASE_URL . "/courses/index.php"); exit(); }

// Always work with the numeric ID internally
$courseId = $course['id'];

// Canonical clean URL for this certificate page
$certPageUrl = BASE_URL . '/certificates/generate/' . $course['slug'];

// Verify enrollment
$enrollment = $db->prepare("SELECT * FROM enrollments WHERE user_id=? AND course_id=? AND status != 'cancelled'");
$enrollment->execute([$userId, $courseId]);
$enrollment = $enrollment->fetch();
if (!$enrollment) {
    header("Location: " . BASE_URL . "/courses/" . $course['slug']);
    exit();
}

$user       = getCurrentUser();
$cert       = $db->prepare("SELECT * FROM certificates WHERE user_id=? AND course_id=?");
$cert->execute([$userId, $courseId]);
$cert       = $cert->fetch();

if (!$cert) {
    $code = 'EDUCORE-' . strtoupper(substr(md5($userId . $courseId . time()), 0, 10));
    $db->prepare("INSERT INTO certificates (user_id, course_id, certificate_code) VALUES (?,?,?)")
       ->execute([$userId, $courseId, $code]);
    $cert = ['certificate_code' => $code, 'issued_at' => date('Y-m-d H:i:s'), 'course_id' => $courseId];
}

$issuedDate = date('F d, Y', strtotime($cert['issued_at']));
$verifyUrl  = BASE_URL . '/certificates/verify/' . $cert['certificate_code'];
$qrUrl      = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&color=111827&bgcolor=ffffff&data=' . urlencode($verifyUrl);
$pageTitle  = "Certificate — " . $course['title'];

// ── PRINT / PDF MODE ─────────────────────────────────────────────────────────
$printMode = isset($_GET['print']);
if ($printMode): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate — <?php echo e($course['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #fff; }
        @page { size: A4 landscape; margin: 0; }
        @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }

        .page {
            width: 297mm; height: 210mm;
            background: #ffffff;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 22px 72px 20px;
            font-family: 'Montserrat', sans-serif;
            color: #111827;
            border: 2.5px solid #1f2937;
        }

        /* ── Corner triangles ── */
        .corner-tl {
            position: absolute; top: 0; left: 0;
            width: 130px; height: 130px;
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 60%, #4ade80 100%);
            clip-path: polygon(0 0, 100% 0, 0 100%);
        }
        .corner-tr {
            position: absolute; top: 0; right: 0;
            width: 130px; height: 130px;
            background: linear-gradient(225deg, #16a34a 0%, #22c55e 60%, #4ade80 100%);
            clip-path: polygon(100% 0, 100% 100%, 0 0);
        }

        /* ── Dot-grid watermarks ── */
        .dots-left, .dots-right {
            position: absolute;
            top: 50%; transform: translateY(-50%);
            display: grid;
            grid-template-columns: repeat(6, 8px);
            gap: 7px;
        }
        .dots-left  { left: 18px; }
        .dots-right { right: 18px; }
        .dots-left span, .dots-right span {
            width: 3px; height: 3px;
            background: #d1d5db;
            border-radius: 50%;
            display: block;
        }

        /* ── Logo ── */
        .logo-row {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 6px; position: relative; z-index: 1;
        }
        .logo-box {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .logo-text { font-size: 19px; font-weight: 900; color: #111827; }
        .logo-text em { color: #16a34a; font-style: normal; }

        /* ── Titles ── */
        .cert-title {
            font-size: 42px; font-weight: 900; letter-spacing: 4px;
            text-transform: uppercase; color: #111827;
            line-height: 1; margin-bottom: 3px;
        }
        .cert-subtitle {
            font-size: 12px; font-weight: 800; letter-spacing: 6px;
            text-transform: uppercase; color: #16a34a;
            margin-bottom: 10px;
        }

        /* ── Course pill ── */
        .course-pill {
            display: inline-flex; align-items: center; gap: 8px;
            background: #16a34a;
            color: #fff;
            font-size: 9px; font-weight: 800; letter-spacing: 2.5px;
            text-transform: uppercase;
            padding: 4px 16px; border-radius: 50px;
            margin-bottom: 10px;
        }
        .course-pill::before, .course-pill::after {
            content: '•'; font-size: 12px;
        }

        /* ── Body ── */
        .certify-text { font-size: 11px; color: #6b7280; margin-bottom: 4px; }
        .recipient-name {
            font-size: 38px; font-weight: 900; letter-spacing: 2px;
            text-transform: uppercase; color: #111827; margin-bottom: 6px;
        }
        .diamond-divider {
            width: 120px; height: 2px; background: #d1d5db;
            margin: 0 auto 10px; position: relative;
        }
        .diamond-divider::after {
            content: '◆'; position: absolute;
            left: 50%; top: 50%; transform: translate(-50%,-50%);
            color: #16a34a; font-size: 10px;
            background: #fff; padding: 0 6px;
        }
        .desc-text {
            font-size: 11.5px; color: #4b5563; max-width: 440px;
            line-height: 1.7; text-align: center; margin-bottom: 0;
        }
        .desc-text strong { color: #16a34a; }
        .desc-text b { color: #111827; }

        /* ── Footer row ── */
        .footer-row {
            width: 100%; display: flex; align-items: flex-end;
            justify-content: space-between; padding: 0 20px;
        }
        .footer-col { text-align: center; min-width: 150px; }

        .footer-icon {
            width: 34px; height: 34px; border: 2px solid #16a34a;
            border-radius: 50%; margin: 0 auto 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
        }
        .footer-line {
            width: 130px; height: 1.5px; background: #111827;
            margin: 6px auto 5px;
        }
        .footer-label { font-size: 10px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: #16a34a; }
        .footer-val   { font-size: 11px; color: #4b5563; margin-top: 2px; }
        .footer-sig   {
            font-family: 'Dancing Script', cursive;
            font-size: 22px; color: #111827; margin-top: 2px;
        }

        /* ── QR column ── */
        .qr-col { text-align: center; }
        .qr-col img { border: 1.5px solid #e5e7eb; border-radius: 5px; display: block; margin: 0 auto; }
        .scan-btn {
            display: inline-block; margin-top: 6px;
            background: #16a34a; color: #fff;
            font-size: 8px; font-weight: 800; letter-spacing: 1.5px;
            text-transform: uppercase; padding: 4px 12px; border-radius: 50px;
        }
        .cert-code { font-size: 8px; font-family: monospace; color: #9ca3af; margin-top: 3px; }
    </style>
</head>
<body>
<div class="page">
    <!-- Corners -->
    <div class="corner-tl"></div>
    <div class="corner-tr"></div>

    <!-- Dot watermarks -->
    <div class="dots-left">
        <?php for($i=0;$i<48;$i++) echo '<span></span>'; ?>
    </div>
    <div class="dots-right">
        <?php for($i=0;$i<48;$i++) echo '<span></span>'; ?>
    </div>

    <!-- Logo -->
    <div class="logo-row">
        <div class="logo-box">🎓</div>
        <div class="logo-text">EDU<em>CORE</em></div>
    </div>

    <!-- Title -->
    <div class="cert-title">Certificate</div>
    <div class="cert-subtitle">of completion</div>

    <!-- Course pill -->
    <div class="course-pill"><?php echo e(strtoupper($course['title'])); ?></div>

    <!-- Body text -->
    <div class="certify-text">This is to certify that</div>
    <div class="recipient-name"><?php echo e(strtoupper($user['name'])); ?></div>
    <div class="diamond-divider"></div>
    <div class="desc-text">
        successfully completed the <strong><?php echo e($course['title']); ?></strong>
        course on the EDUCORE platform, instructed by <b><?php echo e($course['instructor_name']); ?></b>.
    </div>

    <!-- Footer -->
    <div class="footer-row">

        <!-- Date -->
        <div class="footer-col">
            <div class="footer-icon">📅</div>
            <div class="footer-line"></div>
            <div class="footer-label">Date</div>
            <div class="footer-val"><?php echo $issuedDate; ?></div>
        </div>

        <!-- QR -->
        <div class="qr-col">
            <img src="<?php echo $qrUrl; ?>" width="90" height="90" alt="Verify">
            <div class="scan-btn">Scan to Verify</div>
            <div class="cert-code"><?php echo e($cert['certificate_code']); ?></div>
        </div>

        <!-- Instructor -->
        <div class="footer-col">
            <?php if (!empty($course['instructor_signature'])): ?>
                <img src="<?php echo e($course['instructor_signature']); ?>" alt="Signature"
                     style="max-height:60px;max-width:160px;object-fit:contain;display:block;margin:0 auto 2px;">
            <?php else: ?>
                <div class="footer-sig"><?php echo e($course['instructor_name']); ?></div>
            <?php endif; ?>
            <div class="footer-line"></div>
            <div class="footer-label">Instructor</div>
            <div class="footer-val"><?php echo e($course['instructor_name']); ?></div>
        </div>

    </div>
</div>
<script>window.onload = () => window.print();</script>
</body>
</html>
<?php exit(); endif;

// ── WEB PREVIEW ──────────────────────────────────────────────────────────────
include __DIR__ . '/../includes/header.php'; ?>

<div style="margin-top:70px;min-height:100vh;padding:50px 0;background:var(--gradient-hero);">
<div class="container">

    <!-- Action bar -->
    <div style="display:flex;flex-direction:column;align-items:center;margin-bottom:32px;gap:20px;text-align:center;">
        <div>
            <h1 style="font-size:1.6rem;font-weight:900;margin-bottom:4px;">🏆 Course Certificate</h1>
            <p style="color:var(--text-muted);font-size:14px;">Certificate of completion for <strong><?php echo e($course['title']); ?></strong></p>
        </div>
    </div>

    <!-- ══════════════════════════════════════
         CERTIFICATE CARD
    ══════════════════════════════════════ -->
    <div style="
        background:#ffffff;
        border:2.5px solid #1f2937;
        border-radius:16px;
        max-width:900px;
        margin:0 auto;
        position:relative;
        overflow:hidden;
        display:flex;
        flex-direction:column;
        align-items:center;
        padding:36px 80px 32px;
        text-align:center;
        font-family:'Montserrat','Poppins',sans-serif;
        color:#111827;
        min-height:520px;
    ">
        <!-- Green corner triangles -->
        <div style="position:absolute;top:0;left:0;width:130px;height:130px;background:linear-gradient(135deg,#16a34a 0%,#22c55e 60%,#4ade80 100%);clip-path:polygon(0 0,100% 0,0 100%);z-index:0;"></div>
        <div style="position:absolute;top:0;right:0;width:130px;height:130px;background:linear-gradient(225deg,#16a34a 0%,#22c55e 60%,#4ade80 100%);clip-path:polygon(100% 0,100% 100%,0 0);z-index:0;"></div>

        <!-- Dot-grid watermark left -->
        <div style="position:absolute;left:18px;top:50%;transform:translateY(-50%);display:grid;grid-template-columns:repeat(6,8px);gap:7px;z-index:0;">
            <?php for($i=0;$i<48;$i++) echo '<span style="width:3px;height:3px;background:#d1d5db;border-radius:50%;display:block;"></span>'; ?>
        </div>
        <!-- Dot-grid watermark right -->
        <div style="position:absolute;right:18px;top:50%;transform:translateY(-50%);display:grid;grid-template-columns:repeat(6,8px);gap:7px;z-index:0;">
            <?php for($i=0;$i<48;$i++) echo '<span style="width:3px;height:3px;background:#d1d5db;border-radius:50%;display:block;"></span>'; ?>
        </div>

        <!-- ── Logo ── -->
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;position:relative;z-index:1;">
            <div style="width:44px;height:44px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:0 2px 12px rgba(22,163,74,0.35);">🎓</div>
            <div style="font-size:22px;font-weight:900;color:#111827;letter-spacing:-0.5px;">EDU<span style="color:#16a34a;">CORE</span></div>
        </div>

        <!-- ── Main title ── -->
        <div style="font-size:clamp(28px,5vw,46px);font-weight:900;letter-spacing:4px;text-transform:uppercase;color:#111827;line-height:1;margin-bottom:4px;position:relative;z-index:1;">
            CERTIFICATE
        </div>
        <div style="font-size:13px;font-weight:800;letter-spacing:6px;text-transform:uppercase;color:#16a34a;margin-bottom:14px;position:relative;z-index:1;">
            OF COMPLETION
        </div>

        <!-- ── Course name pill ── -->
        <div style="display:inline-flex;align-items:center;gap:8px;background:#16a34a;color:#fff;font-size:10px;font-weight:800;letter-spacing:2.5px;text-transform:uppercase;padding:6px 20px;border-radius:50px;margin-bottom:16px;position:relative;z-index:1;">
            &bull;&nbsp;<?php echo e(strtoupper($course['title'])); ?>&nbsp;&bull;
        </div>

        <!-- ── Certify text ── -->
        <div style="font-size:14px;color:#6b7280;margin-bottom:6px;position:relative;z-index:1;">This is to certify that</div>

        <!-- ── Recipient name ── -->
        <div style="font-size:clamp(26px,4.5vw,42px);font-weight:900;letter-spacing:3px;text-transform:uppercase;color:#111827;margin-bottom:10px;position:relative;z-index:1;line-height:1.1;">
            <?php echo e(strtoupper($user['name'])); ?>
        </div>

        <!-- ── Diamond divider ── -->
        <div style="display:flex;align-items:center;gap:0;margin-bottom:12px;position:relative;z-index:1;">
            <div style="width:80px;height:1.5px;background:#d1d5db;"></div>
            <span style="color:#16a34a;font-size:10px;margin:0 8px;">◆</span>
            <div style="width:80px;height:1.5px;background:#d1d5db;"></div>
        </div>

        <!-- ── Description ── -->
        <div style="font-size:13px;color:#4b5563;max-width:480px;line-height:1.8;margin-bottom:24px;position:relative;z-index:1;">
            successfully completed the <strong style="color:#16a34a;"><?php echo e($course['title']); ?></strong>
            course on the EDUCORE platform, instructed by <strong style="color:#111827;"><?php echo e($course['instructor_name']); ?></strong>.
        </div>

        <!-- ── Footer row: Date | QR | Instructor ── -->
        <div style="width:100%;display:flex;align-items:flex-end;justify-content:space-between;padding:0 10px;margin-top:auto;position:relative;z-index:1;">

            <!-- Date -->
            <div style="text-align:center;min-width:140px;">
                <div style="width:34px;height:34px;border:2px solid #16a34a;border-radius:50%;margin:0 auto 8px;display:flex;align-items:center;justify-content:center;font-size:15px;">📅</div>
                <div style="width:130px;height:1.5px;background:#111827;margin:0 auto 6px;"></div>
                <div style="font-size:10px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:#16a34a;">Date</div>
                <div style="font-size:12px;color:#4b5563;margin-top:3px;"><?php echo $issuedDate; ?></div>
            </div>

            <!-- QR Code -->
            <div style="text-align:center;">
                <img src="<?php echo $qrUrl; ?>" width="100" height="100" alt="Scan to verify"
                     style="border:1.5px solid #e5e7eb;border-radius:6px;display:block;margin:0 auto;">
                <a href="<?php echo $verifyUrl; ?>" target="_blank"
                   style="display:inline-block;margin-top:7px;background:#16a34a;color:#fff;font-size:9px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;padding:5px 14px;border-radius:50px;text-decoration:none;">
                    Scan to Verify
                </a>
                <div style="font-size:9px;font-family:monospace;color:#9ca3af;margin-top:4px;"><?php echo e($cert['certificate_code']); ?></div>
            </div>

            <!-- Instructor -->
            <div style="text-align:center;min-width:140px;">
                <?php if (!empty($course['instructor_signature'])): ?>
                    <img src="<?php echo e($course['instructor_signature']); ?>" alt="Signature"
                         style="max-height:70px;max-width:180px;object-fit:contain;display:block;margin:0 auto 4px;">
                <?php else: ?>
                    <div style="font-family:'Dancing Script','Brush Script MT',cursive;font-size:24px;color:#111827;margin-bottom:4px;font-weight:700;">
                        <?php echo e($course['instructor_name']); ?>
                    </div>
                <?php endif; ?>
                <div style="width:130px;height:1.5px;background:#111827;margin:0 auto 6px;"></div>
                <div style="font-size:10px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:#16a34a;">Instructor</div>
                <div style="font-size:12px;color:#4b5563;margin-top:3px;"><?php echo e($course['instructor_name']); ?></div>
            </div>

        </div>

    </div><!-- end certificate card -->

    <!-- Action buttons -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:32px;">
        <a href="<?php echo $certPageUrl; ?>?print=1" target="_blank" class="btn-primary-custom" style="padding:10px 22px;font-size:14px;">
            <i class="fas fa-print"></i> Print / Save PDF
        </a>
        <button onclick="navigator.clipboard.writeText('<?php echo BASE_URL . '/certificates/verify/' . $cert['certificate_code']; ?>').then(()=>alert('Verification link copied!'))" class="btn-outline-custom" style="padding:10px 20px;font-size:14px;">
            <i class="fas fa-share-alt"></i> Share
        </button>
        <a href="<?php echo BASE_URL; ?>/dashboard/student.php?tab=certificates" class="btn-outline-custom" style="padding:10px 20px;font-size:14px;">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <!-- Load Dancing Script for cursive signature in web preview -->
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap" rel="stylesheet">

</div>
</div>

<?php
// Close body and html — no footer on certificate pages
?>
    <!-- Copyright bar -->
    <div style="text-align:center;padding:14px 0;font-size:12px;color:var(--text-muted);border-top:1px solid var(--border);margin-top:24px;">
        &copy; <?php echo date('Y'); ?> <a href="<?php echo BASE_URL; ?>/index.php" style="color:var(--primary);font-weight:600;text-decoration:none;">EDUCORE</a> — All rights reserved.
    </div>
</body>
</html>
