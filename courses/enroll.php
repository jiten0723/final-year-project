<?php
// ============================================
// EDUCORE - Course Enrollment Handler
// ============================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db        = getDB();
$courseId  = (int)($_GET['id'] ?? 0);
$preview   = isset($_GET['preview']);

if (!$courseId) { header("Location: " . BASE_URL . "/courses/index.php"); exit(); }

$course = $db->prepare("SELECT * FROM courses WHERE id = ? AND status = 'approved'");
$course->execute([$courseId]);
$course = $course->fetch();
if (!$course) { header("Location: " . BASE_URL . "/courses/index.php"); exit(); }

// If premium and not preview, redirect to payment
if ($course['type'] === 'premium' && !$preview && !isEnrolled($_SESSION['user_id'], $courseId)) {
    header("Location: " . BASE_URL . "/payment/checkout.php?course_id=$courseId");
    exit();
}

// Check already enrolled
if (isEnrolled($_SESSION['user_id'], $courseId)) {
    header("Location: " . BASE_URL . "/courses/detail.php?id=$courseId&already=1");
    exit();
}

// Free enrollment
try {
    $db->prepare("INSERT IGNORE INTO enrollments (user_id, course_id, status, progress) VALUES (?, ?, 'active', 0)")
       ->execute([$_SESSION['user_id'], $courseId]);

    // Log payment as free
    $db->prepare("INSERT INTO payments (user_id, course_id, amount, method, status, paid_at) VALUES (?, ?, 0.00, 'free', 'completed', NOW())")
       ->execute([$_SESSION['user_id'], $courseId]);

    // Notify student
    $db->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')")
       ->execute([$_SESSION['user_id'], "You've enrolled in \"" . $course['title'] . "\". Happy learning! 🎉"]);

    header("Location: " . BASE_URL . "/courses/detail.php?id=$courseId&enrolled=1");
    exit();
} catch (Exception $e) {
    header("Location: " . BASE_URL . "/courses/detail.php?id=$courseId&error=1");
    exit();
}
