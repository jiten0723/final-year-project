<?php
// ============================================
// EDUCORE - API: Notifications
// ============================================
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode([]);
    exit();
}

$db     = getDB();
$userId = $_SESSION['user_id'];

// Mark all read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'mark_read') {
    $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$userId]);
    echo json_encode(['success' => true]);
    exit();
}

// Get latest notifications
$stmt = $db->prepare("SELECT *, 
    CASE 
        WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), ' min ago')
        WHEN TIMESTAMPDIFF(HOUR, created_at, NOW()) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), ' hours ago')
        ELSE DATE_FORMAT(created_at, '%b %d')
    END as time_ago
    FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 8
");
$stmt->execute([$userId]);
echo json_encode($stmt->fetchAll());
