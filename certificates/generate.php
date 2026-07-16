<?php
// ============================================
// EDUCORE - Certificate Generator
// ============================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db       = getDB();
$courseId = (int)($_GET['course_id'] ?? 0);
$userId   = $_SESSION['user_id'];

if (!$courseId) { header("Location: " . BASE_URL . "/index.php"); exit(); }

// Verify enrollment
$enrollment = $db->prepare("SELECT * FROM enrollments WHERE user_id=? AND course_id=? AND status != 'cancelled'");
$enrollment->execute([$userId, $courseId]);
$enrollment = $enrollment->fetch();

if (!$enrollment) {
    header("Location: " . BASE_URL . "/courses/detail.php?id=$courseId");
    exit();
}

// Fetch course & user
$course = $db->prepare("SELECT co.*, u.name as instructor_name FROM courses co JOIN users u ON u.id=co.instructor_id WHERE co.id=?");
$course->execute([$courseId]);
$course = $course->fetch();

$user = getCurrentUser();

// Issue certificate if not exists
$cert = $db->prepare("SELECT * FROM certificates WHERE user_id=? AND course_id=?");
$cert->execute([$userId, $courseId]);
$cert = $cert->fetch();

if (!$cert) {
    $code = 'EDUCORE-' . strtoupper(substr(md5($userId . $courseId . time()), 0, 10));
    $db->prepare("INSERT INTO certificates (user_id, course_id, certificate_code) VALUES (?,?,?)")
       ->execute([$userId, $courseId, $code]);
    $cert = ['certificate_code' => $code, 'issued_at' => date('Y-m-d H:i:s')];
}

$issuedDate = date('F d, Y', strtotime($cert['issued_at']));
$pageTitle  = "Certificate — " . $course['title'];

// Print mode
$printMode = isset($_GET['print']);
if ($printMode): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate — <?php echo e($course['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #fff; font-family: 'Inter', sans-serif; }
        @page { size: A4 landscape; margin: 0; }
        @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
        .cert { width: 100vw; height: 100vh; background: linear-gradient(135deg,#0a1628,#0d2210); display: flex; align-items: center; justify-content: center; padding: 40px; }
        .cert-inner { border: 3px solid rgba(34,197,94,0.5); border-radius: 20px; padding: 50px 60px; text-align: center; width: 100%; max-width: 860px; position: relative; color: #fff; }
        .corner { position: absolute; width: 80px; height: 80px; }
        .tl { top: 12px; left: 12px; border-top: 3px solid #22c55e; border-left: 3px solid #22c55e; border-radius: 12px 0 0 0; }
        .tr { top: 12px; right: 12px; border-top: 3px solid #22c55e; border-right: 3px solid #22c55e; border-radius: 0 12px 0 0; }
        .bl { bottom: 12px; left: 12px; border-bottom: 3px solid #22c55e; border-left: 3px solid #22c55e; border-radius: 0 0 0 12px; }
        .br { bottom: 12px; right: 12px; border-bottom: 3px solid #22c55e; border-right: 3px solid #22c55e; border-radius: 0 0 12px 0; }
        .seal { width: 80px; height: 80px; background: linear-gradient(135deg,#22c55e,#3b82f6); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 36px; box-shadow: 0 0 30px rgba(34,197,94,0.5); }
        .sub { font-size: 12px; letter-spacing: 4px; color: #9ca3af; text-transform: uppercase; margin-bottom: 6px; }
        .heading { font-family: 'Poppins', sans-serif; font-size: 36px; font-weight: 900; margin-bottom: 6px; }
        .cert-sub { font-size: 14px; color: #9ca3af; margin-bottom: 20px; }
        .presented { font-size: 13px; color: #9ca3af; margin-bottom: 6px; }
        .name { font-family: Georgia, serif; font-style: italic; font-size: 40px; font-weight: 700; background: linear-gradient(135deg,#22c55e,#3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 8px; }
        .for { font-size: 14px; color: #9ca3af; margin-bottom: 6px; }
        .course-name { font-family: 'Poppins', sans-serif; font-size: 22px; font-weight: 800; margin-bottom: 20px; }
        .divider { height: 1px; background: linear-gradient(90deg, transparent, rgba(34,197,94,0.4), transparent); margin: 20px auto; max-width: 280px; }
        .footer-row { display: flex; justify-content: center; gap: 60px; margin-top: 20px; }
        .footer-item { text-align: center; }
        .footer-val { font-size: 14px; font-weight: 700; margin-bottom: 2px; }
        .footer-lbl { font-size: 11px; color: #9ca3af; border-top: 1px solid rgba(255,255,255,0.15); padding-top: 8px; margin-top: 8px; }
        .cert-id { font-size: 10px; font-family: monospace; color: #6b7280; margin-top: 20px; }
    </style>
</head>
<body>
<div class="cert">
    <div class="cert-inner">
        <div class="corner tl"></div><div class="corner tr"></div>
        <div class="corner bl"></div><div class="corner br"></div>
        <div class="seal">🎓</div>
        <div class="sub">EDUCORE · Certificate of Completion</div>
        <div class="heading">Certificate of Achievement</div>
        <div class="cert-sub">This is to proudly certify that</div>
        <div class="presented">This certificate is presented to</div>
        <div class="name"><?php echo e($user['name']); ?></div>
        <div class="for">has successfully completed the course</div>
        <div class="course-name"><?php echo e($course['title']); ?></div>
        <div class="divider"></div>
        <div class="footer-row">
            <div class="footer-item">
                <div class="footer-val"><?php echo e($course['instructor_name']); ?></div>
                <div class="footer-lbl">Instructor</div>
            </div>
            <div class="footer-item">
                <div class="footer-val"><?php echo $issuedDate; ?></div>
                <div class="footer-lbl">Date of Completion</div>
            </div>
            <div class="footer-item">
                <div class="footer-val">EDUCORE</div>
                <div class="footer-lbl">Platform Certified</div>
            </div>
        </div>
        <div class="cert-id">Certificate ID: <?php echo e($cert['certificate_code']); ?></div>
    </div>
</div>
<script>window.onload = () => window.print();</script>
</body>
</html>
<?php exit(); endif;

include __DIR__ . '/../includes/header.php'; ?>

<div style="margin-top:70px;min-height:100vh;padding:60px 0;background:var(--gradient-hero);">
<div class="container">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;flex-wrap:wrap;gap:14px;">
        <div>
            <h1 style="font-size:1.6rem;font-weight:900;margin-bottom:4px;">🏆 Course Certificate</h1>
            <p style="color:var(--text-muted);font-size:14px;">Your certificate of completion for <strong><?php echo e($course['title']); ?></strong></p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="?course_id=<?php echo $courseId; ?>&print=1" target="_blank" class="btn-primary-custom" style="padding:10px 22px;font-size:14px;">
                <i class="fas fa-print"></i> Print / Save PDF
            </a>
            <button onclick="copyToClipboard('<?php echo BASE_URL; ?>/certificates/verify.php?code=<?php echo $cert['certificate_code']; ?>')" class="btn-outline-custom" style="padding:10px 20px;font-size:14px;">
                <i class="fas fa-share-alt"></i> Share
            </button>
            <a href="<?php echo BASE_URL; ?>/dashboard/student.php?tab=certificates" class="btn-outline-custom" style="padding:10px 20px;font-size:14px;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Certificate Preview -->
    <div class="certificate-wrap animate-zoom">
        <div class="certificate-corner cert-corner-tl"></div>
        <div class="certificate-corner cert-corner-tr"></div>
        <div class="certificate-corner cert-corner-bl"></div>
        <div class="certificate-corner cert-corner-br"></div>

        <div class="cert-seal">🎓</div>
        <div class="cert-title">EDUCORE · Certificate of Completion</div>
        <h2 class="cert-heading">Certificate of Achievement</h2>
        <p class="cert-sub">This is to proudly certify that</p>

        <div style="font-size:14px;color:var(--text-muted);margin-bottom:8px;">This certificate is presented to</div>
        <div class="cert-name"><?php echo e($user['name']); ?></div>

        <div class="cert-sub">has successfully completed the course</div>
        <div class="cert-course"><?php echo e($course['title']); ?></div>

        <div style="display:flex;justify-content:center;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <span style="padding:4px 14px;border-radius:50px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);font-size:12px;color:var(--primary);">
                <i class="fas fa-signal me-1"></i><?php echo ucfirst($course['level']); ?>
            </span>
            <span style="padding:4px 14px;border-radius:50px;background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.3);font-size:12px;color:#60a5fa;">
                <i class="fas fa-clock me-1"></i><?php echo e($course['duration']); ?>
            </span>
            <span class="course-badge badge-<?php echo $course['type']; ?>" style="position:static;"><?php echo strtoupper($course['type']); ?></span>
        </div>

        <div class="cert-divider"></div>

        <div class="cert-details">
            <div class="cert-detail-item">
                <div class="cert-detail-value"><?php echo e($course['instructor_name']); ?></div>
                <div class="cert-detail-label">Instructor</div>
            </div>
            <div class="cert-detail-item">
                <div class="cert-detail-value"><?php echo $issuedDate; ?></div>
                <div class="cert-detail-label">Date of Completion</div>
            </div>
            <div class="cert-detail-item">
                <div class="cert-detail-value" style="color:var(--primary);">EDUCORE</div>
                <div class="cert-detail-label">Platform Certified</div>
            </div>
        </div>

        <div style="margin-top:24px;padding:10px 20px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;display:inline-block;">
            <span style="font-size:11px;color:var(--text-muted);">Certificate ID: </span>
            <span style="font-size:12px;font-family:monospace;color:var(--primary);font-weight:700;"><?php echo e($cert['certificate_code']); ?></span>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
