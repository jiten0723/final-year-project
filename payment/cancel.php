<?php
// Payment cancel/failure redirect
require_once __DIR__ . '/../includes/auth.php';
$courseId = (int)($_GET['course_id'] ?? 0);
$pageTitle = "Payment Cancelled";
include __DIR__ . '/../includes/header.php';
?>
<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;margin-top:70px;padding:40px 16px;">
    <div style="text-align:center;max-width:460px;" class="animate-zoom">
        <div style="font-size:80px;margin-bottom:20px;">😔</div>
        <h1 style="font-size:1.8rem;font-weight:900;margin-bottom:10px;">Payment Cancelled</h1>
        <p style="color:var(--text-muted);margin-bottom:28px;">Your payment was cancelled. No charges were made. You can try again anytime.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <?php if ($courseId): ?>
                <a href="<?php echo BASE_URL; ?>/payment/checkout.php?course_id=<?php echo $courseId; ?>" class="btn-primary-custom">
                    <i class="fas fa-redo"></i> Try Again
                </a>
                <a href="<?php echo BASE_URL; ?>/courses/detail.php?id=<?php echo $courseId; ?>" class="btn-outline-custom">
                    <i class="fas fa-arrow-left"></i> Back to Course
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/courses/index.php" class="btn-primary-custom">Browse Courses</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
