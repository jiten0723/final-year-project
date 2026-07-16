<?php
// ============================================
// EDUCORE - eSewa Mock Payment Processor
// ============================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db       = getDB();
$courseId = (int)(explode('-', $_POST['pid'] ?? '0-0')[1] ?? 0);
$amount   = (float)($_POST['tAmt'] ?? 0);

if (!$courseId) {
    header("Location: " . BASE_URL . "/courses/index.php");
    exit();
}

// Simulate eSewa verification (in production: call eSewa verify API)
$txnId = 'ESEWA-' . strtoupper(uniqid());

// Record payment
$exists = $db->prepare("SELECT id FROM payments WHERE user_id=? AND course_id=? AND status='completed'");
$exists->execute([$_SESSION['user_id'], $courseId]);

if (!$exists->fetch()) {
    $db->prepare("INSERT INTO payments (user_id,course_id,amount,method,transaction_id,status,paid_at) VALUES (?,?,?,'esewa',?,'completed',NOW())")
       ->execute([$_SESSION['user_id'], $courseId, $amount, $txnId]);

    $db->prepare("INSERT IGNORE INTO enrollments (user_id,course_id,status,progress) VALUES (?,?,'active',0)")
       ->execute([$_SESSION['user_id'], $courseId]);

    $course = $db->prepare("SELECT title FROM courses WHERE id=?");
    $course->execute([$courseId]);
    $course = $course->fetch();

    $db->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'success')")
       ->execute([$_SESSION['user_id'], "eSewa payment successful! You're enrolled in \"{$course['title']}\". 🎉"]);
}

header("Location: " . BASE_URL . "/payment/success.php?method=esewa&course_id=$courseId");
exit();
