<?php
// ============================================
// EDUCORE - PayPal Mock Payment Processor
// ============================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db       = getDB();
$courseId = (int)($_POST['course_id'] ?? 0);
$amount   = (float)($_POST['amount'] ?? 0);

if (!$courseId) {
    header("Location: " . BASE_URL . "/courses/index.php");
    exit();
}

// Simulate PayPal verification
$txnId = 'PAYPAL-' . strtoupper(uniqid());

$exists = $db->prepare("SELECT id FROM payments WHERE user_id=? AND course_id=? AND status='completed'");
$exists->execute([$_SESSION['user_id'], $courseId]);

if (!$exists->fetch()) {
    $db->prepare("INSERT INTO payments (user_id,course_id,amount,method,transaction_id,status,paid_at) VALUES (?,?,?,'paypal',?,'completed',NOW())")
       ->execute([$_SESSION['user_id'], $courseId, $amount, $txnId]);

    $db->prepare("INSERT IGNORE INTO enrollments (user_id,course_id,status,progress) VALUES (?,?,'active',0)")
       ->execute([$_SESSION['user_id'], $courseId]);

    $course = $db->prepare("SELECT title FROM courses WHERE id=?");
    $course->execute([$courseId]);
    $course = $course->fetch();

    $db->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'success')")
       ->execute([$_SESSION['user_id'], "PayPal payment successful! You're enrolled in \"{$course['title']}\". 🎉"]);
}

header("Location: " . BASE_URL . "/payment/success.php?method=paypal&course_id=$courseId");
exit();
