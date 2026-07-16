<?php
// ============================================
// EDUCORE - API: Get Lesson Content
// ============================================
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$lessonId = (int)($_GET['id'] ?? 0);
$courseId = (int)($_GET['course_id'] ?? 0);

if (!$lessonId || !$courseId) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$db     = getDB();
$lesson = $db->prepare("SELECT l.*, c.type as course_type, c.instructor_id 
                         FROM lessons l JOIN courses c ON c.id = l.course_id 
                         WHERE l.id=? AND l.course_id=?");
$lesson->execute([$lessonId, $courseId]);
$lesson = $lesson->fetch();

if (!$lesson) {
    echo json_encode(['error' => 'Lesson not found']);
    exit();
}

// Access check
$canAccess = $lesson['is_free_preview'] || $lesson['course_type'] === 'free';

if (!$canAccess && isLoggedIn()) {
    $enrolled = isEnrolled($_SESSION['user_id'], $courseId);
    $canAccess = $enrolled;
}

if (!$canAccess) {
    echo json_encode(['error' => 'Please enroll to access this lesson.']);
    exit();
}

// Mark lesson as viewed
if (isLoggedIn()) {
    $db->prepare("INSERT IGNORE INTO lesson_progress (user_id, lesson_id, completed, completed_at) VALUES (?,?,1,NOW())")
       ->execute([$_SESSION['user_id'], $lessonId]);

    // Update enrollment progress
    $totalLessons   = $db->prepare("SELECT COUNT(*) FROM lessons WHERE course_id=?"); $totalLessons->execute([$courseId]); $totalLessons=$totalLessons->fetchColumn();
    $completedLess  = $db->prepare("SELECT COUNT(*) FROM lesson_progress lp JOIN lessons l ON l.id=lp.lesson_id WHERE lp.user_id=? AND l.course_id=? AND lp.completed=1"); $completedLess->execute([$_SESSION['user_id'],$courseId]); $completedLess=$completedLess->fetchColumn();
    $progress = $totalLessons > 0 ? round(($completedLess / $totalLessons) * 100) : 0;
    $db->prepare("UPDATE enrollments SET progress=? WHERE user_id=? AND course_id=?")->execute([$progress, $_SESSION['user_id'], $courseId]);

    if ($progress >= 100) {
        $db->prepare("UPDATE enrollments SET status='completed', completed_at=NOW() WHERE user_id=? AND course_id=? AND status='active'")->execute([$_SESSION['user_id'],$courseId]);
    }
}

// Format content as HTML
$content = nl2br(htmlspecialchars($lesson['content'] ?? '', ENT_QUOTES, 'UTF-8'));

// Bold **text** syntax
$content = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $content);

// Bullet lists using -
$content = preg_replace('/^- (.+)$/m', '<li style="margin-bottom:6px;">$1</li>', $content);
$content = preg_replace('/(<li.*<\/li>\n?)+/', '<ul style="padding-left:20px;margin:12px 0;">$0</ul>', $content);

$response = [
    'title'       => $lesson['title'],
    'content'     => $content,
    'duration'    => $lesson['duration_minutes'],
    'is_preview'  => (bool)$lesson['is_free_preview'],
    'video_url'   => $lesson['video_url'],
];

echo json_encode($response);
