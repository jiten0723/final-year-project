<?php
// ============================================
// EDUCORE - API: Save Quiz Result
// ============================================
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit();
}

$db      = getDB();
$userId  = $_SESSION['user_id'];
$quizId  = (int)($data['quiz_id'] ?? 0);
$score   = (int)($data['score'] ?? 0);
$total   = (int)($data['total_questions'] ?? 0);
$pct     = (float)($data['percentage'] ?? 0);
$passed  = (int)($data['passed'] ?? 0);

if (!$quizId || !$total) {
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    $db->prepare("INSERT INTO quiz_results (user_id, quiz_id, score, total_questions, percentage, passed) VALUES (?,?,?,?,?,?)")
       ->execute([$userId, $quizId, $score, $total, $pct, $passed]);

    // If passed, check if course-linked and auto-issue cert
    if ($passed) {
        $quiz = $db->prepare("SELECT course_id FROM quizzes WHERE id=?");
        $quiz->execute([$quizId]);
        $quiz = $quiz->fetch();
        if ($quiz && $quiz['course_id']) {
            $enrolled = $db->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=?");
            $enrolled->execute([$userId, $quiz['course_id']]);
            if ($enrolled->fetch()) {
                $exists = $db->prepare("SELECT id FROM certificates WHERE user_id=? AND course_id=?");
                $exists->execute([$userId, $quiz['course_id']]);
                if (!$exists->fetch()) {
                    $code = 'EDUCORE-' . strtoupper(substr(md5($userId . $quiz['course_id'] . time()), 0, 10));
                    $db->prepare("INSERT INTO certificates (user_id, course_id, certificate_code) VALUES (?,?,?)")
                       ->execute([$userId, $quiz['course_id'], $code]);
                    $db->prepare("UPDATE enrollments SET status='completed', completed_at=NOW(), progress=100 WHERE user_id=? AND course_id=?")
                       ->execute([$userId, $quiz['course_id']]);
                }
            }
        }
    }

    echo json_encode(['success' => true, 'passed' => $passed]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
