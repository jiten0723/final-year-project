<?php
// ============================================
// EDUCORE - Course Learning Page
// ============================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db       = getDB();
$courseId = (int)($_GET['course_id'] ?? 0);
$lessonId = (int)($_GET['lesson_id'] ?? 0);

if (!$courseId) { header("Location: " . BASE_URL . "/courses/index.php"); exit(); }

// Fetch course
$course = $db->prepare("
    SELECT co.*, u.name as instructor_name, cat.name as category_name
    FROM courses co
    JOIN users u ON u.id = co.instructor_id
    LEFT JOIN categories cat ON cat.id = co.category_id
    WHERE co.id = ? AND co.status = 'approved'
");
$course->execute([$courseId]);
$course = $course->fetch();
if (!$course) { header("Location: " . BASE_URL . "/courses/index.php"); exit(); }

// Must be enrolled
if (!isEnrolled($_SESSION['user_id'], $courseId)) {
    header("Location: " . BASE_URL . "/courses/" . $course['slug']);
    exit();
}

// Fetch all lessons
$lessonsStmt = $db->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_num");
$lessonsStmt->execute([$courseId]);
$lessons = $lessonsStmt->fetchAll();

if (empty($lessons)) {
    header("Location: " . BASE_URL . "/courses/" . $course['slug']);
    exit();
}

// Determine which lesson to show — default to first
if (!$lessonId || !in_array($lessonId, array_column($lessons, 'id'))) {
    $lessonId = $lessons[0]['id'];
}

// Fetch current lesson
$lessonStmt = $db->prepare("SELECT * FROM lessons WHERE id = ? AND course_id = ?");
$lessonStmt->execute([$lessonId, $courseId]);
$lesson = $lessonStmt->fetch();

// Fetch completed lessons for this user
$completedStmt = $db->prepare("
    SELECT lesson_id FROM lesson_progress
    WHERE user_id = ? AND completed = 1
    AND lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)
");
$completedStmt->execute([$_SESSION['user_id'], $courseId]);
$completedLessonIds = $completedStmt->fetchAll(\PDO::FETCH_COLUMN);

// Mark current lesson as completed
if (!in_array($lessonId, $completedLessonIds)) {
    $db->prepare("INSERT IGNORE INTO lesson_progress (user_id, lesson_id, completed, completed_at) VALUES (?,?,1,NOW())")
       ->execute([$_SESSION['user_id'], $lessonId]);
    $completedLessonIds[] = $lessonId;
}

// Update enrollment progress
$totalLessons    = count($lessons);
$completedCount  = count($completedLessonIds);
$progress        = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;
$db->prepare("UPDATE enrollments SET progress=? WHERE user_id=? AND course_id=?")
   ->execute([$progress, $_SESSION['user_id'], $courseId]);

// Mark as completed if 100%
if ($progress >= 100) {
    $db->prepare("UPDATE enrollments SET status='completed', completed_at=NOW() WHERE user_id=? AND course_id=? AND status='active'")
       ->execute([$_SESSION['user_id'], $courseId]);
}

// Enrollment row
$enrollRow = $db->prepare("SELECT * FROM enrollments WHERE user_id=? AND course_id=?");
$enrollRow->execute([$_SESSION['user_id'], $courseId]);
$enrollment = $enrollRow->fetch();

// Certificate exists?
$cert = $db->prepare("SELECT * FROM certificates WHERE user_id=? AND course_id=?");
$cert->execute([$_SESSION['user_id'], $courseId]);
$certificate = $cert->fetch();

// Format content
$content = nl2br(htmlspecialchars($lesson['content'] ?? '', ENT_QUOTES, 'UTF-8'));
$content = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $content);
$content = preg_replace('/^### (.+)$/m', '<h4 style="font-size:16px;font-weight:700;margin:20px 0 8px;">$1</h4>', $content);
$content = preg_replace('/^## (.+)$/m',  '<h3 style="font-size:18px;font-weight:800;margin:24px 0 10px;">$1</h3>', $content);
$content = preg_replace('/^- (.+)$/m', '<li style="margin-bottom:8px;">$1</li>', $content);
$content = preg_replace('/(<li.*<\/li>\n?)+/', '<ul style="padding-left:24px;margin:14px 0;">$0</ul>', $content);

// Prev / Next
$lessonIds   = array_column($lessons, 'id');
$currentIdx  = array_search($lessonId, $lessonIds);
$prevLesson  = $currentIdx > 0 ? $lessons[$currentIdx - 1] : null;
$nextLesson  = $currentIdx < count($lessons) - 1 ? $lessons[$currentIdx + 1] : null;

$catIcons = ['Programming'=>'💻','Design'=>'🎨','Business'=>'💼','Music'=>'🎵','Photography'=>'📷','Marketing'=>'📢','Data Science'=>'📊','Personal Dev'=>'🚀'];
$icon = $catIcons[$course['category_name']] ?? '📚';

$pageTitle = $lesson['title'] . ' — ' . $course['title'];
include __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Learn Page Layout ── */
.learn-wrapper {
    display: flex;
    min-height: calc(100vh - 70px);
    margin-top: 70px;
    background: var(--bg-dark);
}

/* Sidebar */
.learn-sidebar {
    width: 320px;
    flex-shrink: 0;
    background: var(--bg-card);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: sticky;
    top: 70px;
    height: calc(100vh - 70px);
}
.learn-sidebar-header {
    padding: 20px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, #0d1a2e, #0a1a10);
}
.learn-sidebar-title {
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 10px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.learn-progress-wrap { margin-top: 10px; }
.learn-progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 6px;
}
.learn-progress-bar {
    height: 6px;
    background: var(--bg-input);
    border-radius: 3px;
    overflow: hidden;
}
.learn-progress-fill {
    height: 100%;
    background: var(--gradient-primary);
    border-radius: 3px;
    transition: width 0.6s ease;
}
.learn-lesson-list {
    flex: 1;
    overflow-y: auto;
    padding: 12px 0;
}
.learn-lesson-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    cursor: pointer;
    transition: all 0.2s;
    border-left: 3px solid transparent;
    text-decoration: none;
}
.learn-lesson-item:hover {
    background: rgba(34,197,94,0.05);
    border-left-color: rgba(34,197,94,0.3);
}
.learn-lesson-item.active {
    background: rgba(34,197,94,0.08);
    border-left-color: var(--primary);
}
.learn-lesson-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
}
.learn-lesson-num.done { background: rgba(34,197,94,0.15); color: var(--primary); }
.learn-lesson-num.current { background: var(--gradient-primary); color: #fff; }
.learn-lesson-num.pending { background: var(--bg-input); color: var(--text-muted); }
.learn-lesson-info { flex: 1; min-width: 0; }
.learn-lesson-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.learn-lesson-meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

/* Main content area */
.learn-main {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}
.learn-topbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(10,15,30,0.95);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    padding: 14px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.learn-content-body {
    max-width: 820px;
    width: 100%;
    margin: 0 auto;
    padding: 40px 32px 80px;
}
.lesson-content-area {
    color: var(--text-secondary);
    font-size: 15.5px;
    line-height: 1.95;
}
.lesson-content-area strong { color: var(--text-primary); }
.lesson-content-area h3, .lesson-content-area h4 { color: #fff; }
.lesson-content-area ul { color: var(--text-secondary); }

/* Nav buttons */
.learn-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 20px;
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    border: 1px solid var(--border);
    background: var(--bg-card);
    color: var(--text-secondary);
}
.learn-nav-btn:hover { border-color: var(--primary); color: var(--primary); }
.learn-nav-btn.primary {
    background: var(--gradient-primary);
    color: #fff;
    border-color: transparent;
}
.learn-nav-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(34,197,94,0.35); color:#fff; }

/* Mobile: hide sidebar on small screens */
@media (max-width: 900px) {
    .learn-sidebar { display: none; }
}

/* Certificate banner */
.cert-banner {
    background: linear-gradient(135deg, rgba(34,197,94,0.08), rgba(59,130,246,0.08));
    border: 1px solid rgba(34,197,94,0.25);
    border-radius: var(--radius-lg);
    padding: 28px 32px;
    margin-top: 48px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}
.cert-banner-icon { font-size: 48px; flex-shrink: 0; }
.cert-banner-text h3 { font-size: 18px; font-weight: 800; margin-bottom: 4px; color:#fff; }
.cert-banner-text p { font-size: 14px; color: var(--text-muted); margin:0; }
</style>

<div class="learn-wrapper">

    <!-- ── Sidebar ── -->
    <aside class="learn-sidebar">
        <div class="learn-sidebar-header">
            <a href="<?php echo BASE_URL; ?>/courses/<?php echo e($course['slug']); ?>" style="font-size:11px;color:var(--primary);display:flex;align-items:center;gap:5px;margin-bottom:10px;text-decoration:none;">
                <i class="fas fa-arrow-left"></i> Back to Course
            </a>
            <div class="learn-sidebar-title"><?php echo e($course['title']); ?></div>
            <div class="learn-progress-wrap">
                <div class="learn-progress-label">
                    <span><?php echo $completedCount; ?>/<?php echo $totalLessons; ?> lessons</span>
                    <span style="color:var(--primary);font-weight:700;"><?php echo $progress; ?>%</span>
                </div>
                <div class="learn-progress-bar">
                    <div class="learn-progress-fill" style="width:<?php echo $progress; ?>%;"></div>
                </div>
            </div>
        </div>

        <div class="learn-lesson-list">
            <?php foreach ($lessons as $i => $ls):
                $isDone    = in_array($ls['id'], $completedLessonIds);
                $isCurrent = $ls['id'] === $lessonId;
                $numClass  = $isCurrent ? 'current' : ($isDone ? 'done' : 'pending');
            ?>
            <a href="<?php echo BASE_URL; ?>/courses/learn.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $ls['id']; ?>"
               class="learn-lesson-item <?php echo $isCurrent ? 'active' : ''; ?>">
                <div class="learn-lesson-num <?php echo $numClass; ?>">
                    <?php if ($isDone && !$isCurrent): ?>
                        <i class="fas fa-check"></i>
                    <?php else: ?>
                        <?php echo $i + 1; ?>
                    <?php endif; ?>
                </div>
                <div class="learn-lesson-info">
                    <div class="learn-lesson-name"><?php echo e($ls['title']); ?></div>
                    <div class="learn-lesson-meta">
                        <?php echo $ls['video_url'] ? '<i class="fas fa-video"></i> Video' : '<i class="fas fa-file-alt"></i> Reading'; ?>
                        · <?php echo $ls['duration_minutes']; ?> min
                        <?php if ($isDone): ?><span style="color:var(--primary);margin-left:4px;">✓</span><?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- ── Main content ── -->
    <main class="learn-main">

        <!-- Top bar -->
        <div class="learn-topbar">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                <a href="<?php echo BASE_URL; ?>/courses/<?php echo e($course['slug']); ?>" style="color:var(--text-muted);font-size:13px;text-decoration:none;white-space:nowrap;"><i class="fas fa-arrow-left me-1"></i> Course</a>
                <span style="color:var(--border);">›</span>
                <span style="font-size:13px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($lesson['title']); ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                <!-- Mobile progress badge -->
                <span style="font-size:12px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:var(--primary);padding:4px 12px;border-radius:50px;font-weight:700;"><?php echo $progress; ?>% Complete</span>
                <?php if ($progress >= 100): ?>
                    <a href="<?php echo BASE_URL; ?>/certificates/generate.php?course_id=<?php echo $courseId; ?>" class="learn-nav-btn primary">
                        <i class="fas fa-certificate"></i> Get Certificate
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content body -->
        <div class="learn-content-body">

            <!-- Lesson header -->
            <div style="margin-bottom:32px;">
                <div style="font-size:12px;color:var(--primary);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">
                    <?php echo e($course['category_name']); ?> · Lesson <?php echo $currentIdx + 1; ?> of <?php echo $totalLessons; ?>
                </div>
                <h1 style="font-size:1.8rem;font-weight:900;margin-bottom:12px;line-height:1.25;"><?php echo e($lesson['title']); ?></h1>
                <div style="display:flex;gap:16px;font-size:13px;color:var(--text-muted);">
                    <span><i class="fas fa-clock me-1"></i><?php echo $lesson['duration_minutes']; ?> min read</span>
                    <span><i class="fas fa-user me-1"></i><?php echo e($course['instructor_name']); ?></span>
                    <?php if (in_array($lessonId, $completedLessonIds)): ?>
                        <span style="color:var(--primary);"><i class="fas fa-check-circle me-1"></i>Completed</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Divider -->
            <div style="height:1px;background:var(--border);margin-bottom:32px;"></div>

            <!-- Video if available -->
            <?php if (!empty($lesson['video_url'])): ?>
            <?php
                $embedUrl = $lesson['video_url'];
                $embedUrl = str_replace('watch?v=', 'embed/', $embedUrl);
                $embedUrl = str_replace('youtu.be/', 'www.youtube.com/embed/', $embedUrl);
            ?>
            <div style="position:relative;padding-bottom:56.25%;height:0;margin-bottom:32px;border-radius:var(--radius-lg);overflow:hidden;background:#000;border:1px solid var(--border);">
                <iframe src="<?php echo e($embedUrl); ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
            <?php endif; ?>

            <!-- Lesson content -->
            <div class="lesson-content-area">
                <?php echo $content; ?>
            </div>

            <!-- Nav: Prev / Next -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:52px;padding-top:24px;border-top:1px solid var(--border);gap:12px;flex-wrap:wrap;">
                <?php if ($prevLesson): ?>
                    <a href="<?php echo BASE_URL; ?>/courses/learn.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $prevLesson['id']; ?>" class="learn-nav-btn">
                        <i class="fas fa-arrow-left"></i> <?php echo e(truncate($prevLesson['title'], 30)); ?>
                    </a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <?php if ($nextLesson): ?>
                    <a href="<?php echo BASE_URL; ?>/courses/learn.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $nextLesson['id']; ?>" class="learn-nav-btn primary">
                        <?php echo e(truncate($nextLesson['title'], 30)); ?> <i class="fas fa-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <?php if ($progress >= 100): ?>
                        <a href="<?php echo BASE_URL; ?>/certificates/generate.php?course_id=<?php echo $courseId; ?>" class="learn-nav-btn primary">
                            <i class="fas fa-certificate"></i> Generate Certificate
                        </a>
                    <?php else: ?>
                        <span style="font-size:13px;color:var(--text-muted);">You've reached the last lesson.</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Certificate completion banner -->
            <?php if ($progress >= 100): ?>
            <div class="cert-banner animate-fade-up">
                <div class="cert-banner-icon">🏆</div>
                <div class="cert-banner-text">
                    <h3>Congratulations! You've completed this course!</h3>
                    <p>You've finished all <?php echo $totalLessons; ?> lesson(s) in <strong style="color:#fff;"><?php echo e($course['title']); ?></strong>. Your certificate is ready to download.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/certificates/generate.php?course_id=<?php echo $courseId; ?>" class="learn-nav-btn primary" style="flex-shrink:0;padding:12px 28px;font-size:14px;">
                    <i class="fas fa-download"></i> Download Certificate
                </a>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
