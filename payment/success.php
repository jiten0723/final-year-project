<?php
// ============================================
// EDUCORE - Payment Success (Mock)
// ============================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db       = getDB();
$method   = $_GET['method'] ?? 'esewa';
$courseId = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);

if (!$courseId) { header("Location: " . BASE_URL . "/courses/index.php"); exit(); }

$course = $db->prepare("SELECT * FROM courses WHERE id = ?");
$course->execute([$courseId]);
$course = $course->fetch();

if ($course && !isEnrolled($_SESSION['user_id'], $courseId)) {
    $txId = strtoupper(uniqid('TXN'));
    $db->prepare("INSERT INTO payments (user_id,course_id,amount,method,transaction_id,status,paid_at) VALUES (?,?,?,?,?,'completed',NOW())")
       ->execute([$_SESSION['user_id'], $courseId, $course['price'], $method, $txId]);

    $db->prepare("INSERT IGNORE INTO enrollments (user_id, course_id, status, progress) VALUES (?,?,'active',0)")
       ->execute([$_SESSION['user_id'], $courseId]);

    $db->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'success')")
       ->execute([$_SESSION['user_id'], "Payment successful! You are now enrolled in \"{$course['title']}\". 🎉"]);
}

$pageTitle = "Payment Successful";
include __DIR__ . '/../includes/header.php';
?>
<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;margin-top:70px;padding:40px 16px;">
    <div style="text-align:center;max-width:500px;" class="animate-zoom">
        <div style="width:100px;height:100px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 28px;box-shadow:0 0 50px rgba(34,197,94,0.4);font-size:44px;">✓</div>
        <h1 style="font-size:2rem;font-weight:900;margin-bottom:12px;">Payment Successful!</h1>
        <p style="color:var(--text-muted);font-size:16px;margin-bottom:32px;">
            You have successfully enrolled in <strong style="color:var(--primary);"><?php echo e($course['title'] ?? 'the course'); ?></strong>. 
            Start your learning journey today!
        </p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="<?php echo BASE_URL; ?>/courses/detail.php?id=<?php echo $courseId; ?>" class="btn-primary-custom">
                <i class="fas fa-play-circle"></i> Start Learning Now
            </a>
            <a href="<?php echo BASE_URL; ?>/dashboard/student.php" class="btn-outline-custom">
                <i class="fas fa-tachometer-alt"></i> My Dashboard
            </a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
