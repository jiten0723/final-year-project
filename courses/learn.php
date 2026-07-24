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

// Fetch completed lessons for this user
$completedStmt = $db->prepare("
    SELECT lesson_id FROM lesson_progress
    WHERE user_id = ? AND completed = 1
    AND lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)
");
$completedStmt->execute([$_SESSION['user_id'], $courseId]);
$completedLessonIds = $completedStmt->fetchAll(\PDO::FETCH_COLUMN);

// ── Sequential gating: build unlocked lesson IDs ──────────────────────────
// Lesson 0 is always unlocked. Lesson N is unlocked if lesson N-1 is completed.
$lessonIds    = array_column($lessons, 'id');
$unlockedIds  = [];
foreach ($lessons as $i => $ls) {
    if ($i === 0) {
        $unlockedIds[] = $ls['id']; // first lesson always accessible
    } elseif (in_array($lessons[$i - 1]['id'], $completedLessonIds)) {
        $unlockedIds[] = $ls['id'];
    } else {
        break; // stop — everything after a locked lesson is also locked
    }
}

// ── URL guard: redirect to last unlocked lesson if trying to access locked ──
if (!in_array($lessonId, $unlockedIds)) {
    $redirectId = end($unlockedIds) ?: $lessons[0]['id'];
    header("Location: " . BASE_URL . "/courses/learn.php?course_id={$courseId}&lesson_id={$redirectId}&gated=1");
    exit();
}

// Fetch current lesson
$lessonStmt = $db->prepare("SELECT * FROM lessons WHERE id = ? AND course_id = ?");
$lessonStmt->execute([$lessonId, $courseId]);
$lesson = $lessonStmt->fetch();

// ── Handle AJAX "Mark as Complete" POST ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    header('Content-Type: application/json');
    $markId = (int)$_POST['lesson_id'];
    // Must be in unlocked set
    if (in_array($markId, $unlockedIds)) {
        $db->prepare("INSERT IGNORE INTO lesson_progress (user_id, lesson_id, completed, completed_at) VALUES (?,?,1,NOW())")
           ->execute([$_SESSION['user_id'], $markId]);
        if (!in_array($markId, $completedLessonIds)) $completedLessonIds[] = $markId;
    }
    // Recalculate progress
    $totalLessons   = count($lessons);
    $completedCount = count($completedLessonIds);
    $progress       = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;
    $db->prepare("UPDATE enrollments SET progress=? WHERE user_id=? AND course_id=?")
       ->execute([$progress, $_SESSION['user_id'], $courseId]);
    if ($progress >= 100) {
        $db->prepare("UPDATE enrollments SET status='completed', completed_at=NOW() WHERE user_id=? AND course_id=? AND status='active'")
           ->execute([$_SESSION['user_id'], $courseId]);
    }
    echo json_encode([
        'success'      => true,
        'progress'     => $progress,
        'completed'    => $completedLessonIds,
        'courseComplete'=> $progress >= 100,
    ]);
    exit();
}

// Progress for display
$totalLessons    = count($lessons);
$completedCount  = count($completedLessonIds);
$progress        = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;

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
.learn-lesson-num.locked { background: rgba(107,114,128,0.12); color: #6b7280; }
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
</style>

<script>
const BASE_URL   = '<?php echo BASE_URL; ?>';
const COURSE_ID  = <?php echo $courseId; ?>;
const LESSON_ID  = <?php echo $lessonId; ?>;
const NEXT_ID    = <?php echo $nextLesson ? $nextLesson['id'] : 'null'; ?>;
const IS_LAST    = <?php echo $isLastLesson ? 'true' : 'false'; ?>;
const COURSE_SLUG = '<?php echo e($course['slug']); ?>';

// Show gated toast if redirected here from a locked lesson
<?php if (isset($_GET['gated'])): ?>
document.addEventListener('DOMContentLoaded', () => {
    showToast('info', '🔒 Please complete the previous lesson first.');
});
<?php endif; ?>

function markLessonComplete(lessonId) {
    const btn = document.getElementById('markCompleteBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...'; }

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `mark_complete=1&lesson_id=${lessonId}`
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { showToast('error', 'Could not save progress. Try again.'); return; }

        showToast('success', '✅ Lesson marked as complete!');

        // Update progress bar in sidebar
        const fill = document.querySelector('.learn-progress-fill');
        const label = document.querySelector('.learn-progress-label span:last-child');
        if (fill)  fill.style.width = data.progress + '%';
        if (label) label.textContent = data.progress + '%';

        // Update top badge
        const badge = document.querySelector('.learn-topbar span[style*="var(--primary)"]');
        if (badge) badge.textContent = data.progress + '% Complete';

        // Mark sidebar item green
        const activeNum = document.querySelector('.learn-lesson-num.current');
        if (activeNum) { activeNum.classList.replace('current','done'); activeNum.innerHTML = '<i class="fas fa-check"></i>'; }

        // Unlock Next button
        const nextBtn = document.getElementById('nextBtn');
        if (data.courseComplete) {
            // 100% complete — swap Next for cert button or unlock cert
            if (IS_LAST) {
                window.location.reload(); // simplest — cert banner + cert btn will render
            } else {
                window.location.href = `${BASE_URL}/courses/learn.php?course_id=${COURSE_ID}&lesson_id=${NEXT_ID}`;
            }
        } else if (nextBtn && NEXT_ID) {
            // Enable next button
            nextBtn.disabled = false;
            nextBtn.style.opacity = '1';
            nextBtn.style.cursor  = 'pointer';
            nextBtn.onclick = () => { window.location.href = `${BASE_URL}/courses/learn.php?course_id=${COURSE_ID}&lesson_id=${NEXT_ID}`; };
            nextBtn.innerHTML = nextBtn.innerHTML.replace('fa-lock','fa-arrow-right');
        } else {
            window.location.reload();
        }

        // Replace "mark as complete" area with "Completed!" note
        const markArea = btn?.closest('div[style*="rgba(34,197,94"]');
        if (markArea) {
            markArea.outerHTML = `<div style="margin-top:40px;padding:14px 20px;background:rgba(34,197,94,0.06);border:1px solid rgba(34,197,94,0.2);border-radius:12px;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-check-circle" style="color:var(--primary);font-size:16px;"></i>
                <span style="font-size:13px;color:var(--primary);font-weight:600;">Lesson completed!</span>
            </div>`;
        }
    })
    .catch(() => showToast('error', 'Network error. Please try again.'));
}
</script>
<style>
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
                $isDone     = in_array($ls['id'], $completedLessonIds);
                $isCurrent  = $ls['id'] === $lessonId;
                $isUnlocked = in_array($ls['id'], $unlockedIds);
                $numClass   = $isCurrent ? 'current' : ($isDone ? 'done' : ($isUnlocked ? 'pending' : 'locked'));
            ?>
            <?php if ($isUnlocked): ?>
            <a href="<?php echo BASE_URL; ?>/courses/learn.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $ls['id']; ?>"
               class="learn-lesson-item <?php echo $isCurrent ? 'active' : ''; ?>">
            <?php else: ?>
            <div class="learn-lesson-item locked-item" style="opacity:0.45;pointer-events:none;cursor:default;">
            <?php endif; ?>
                <div class="learn-lesson-num <?php echo $numClass; ?>">
                    <?php if (!$isUnlocked): ?>
                        <i class="fas fa-lock" style="font-size:10px;"></i>
                    <?php elseif ($isDone && !$isCurrent): ?>
                        <i class="fas fa-check"></i>
                    <?php else: ?>
                        <?php echo $i + 1; ?>
                    <?php endif; ?>
                </div>
                <div class="learn-lesson-info">
                    <div class="learn-lesson-name" style="<?php echo !$isUnlocked ? 'color:var(--text-muted);' : ''; ?>"><?php echo e($ls['title']); ?></div>
                    <div class="learn-lesson-meta">
                        <?php echo $ls['video_url'] ? '<i class="fas fa-video"></i> Video' : '<i class="fas fa-file-alt"></i> Reading'; ?>
                        · <?php echo $ls['duration_minutes']; ?> min
                        <?php if ($isDone): ?><span style="color:var(--primary);margin-left:4px;">✓</span>
                        <?php elseif (!$isUnlocked): ?><span style="color:var(--text-muted);margin-left:4px;font-size:10px;">🔒 Locked</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php if ($isUnlocked): ?></a><?php else: ?></div><?php endif; ?>
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
                    <a href="<?php echo BASE_URL; ?>/certificates/generate/<?php echo e($course['slug']); ?>" class="learn-nav-btn primary">
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

            <!-- Mark as Complete + Nav bar -->
            <?php
                $isCurrentDone  = in_array($lessonId, $completedLessonIds);
                $isLastLesson   = ($currentIdx === $totalLessons - 1);
                $isCourseComplete = ($progress >= 100);
            ?>

            <!-- Mark as Complete button (shown if lesson not yet done) -->
            <?php if (!$isCurrentDone): ?>
            <div style="margin-top:40px;padding:20px 24px;background:linear-gradient(135deg,rgba(34,197,94,0.06),rgba(59,130,246,0.04));border:1px solid rgba(34,197,94,0.2);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
                <div>
                    <div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:2px;">Finished with this lesson?</div>
                    <div style="font-size:12px;color:var(--text-muted);">Mark it complete to unlock the next lesson.</div>
                </div>
                <button id="markCompleteBtn" onclick="markLessonComplete(<?php echo $lessonId; ?>)"
                        class="learn-nav-btn primary" style="padding:11px 28px;font-size:14px;">
                    <i class="fas fa-check-circle"></i> Mark as Complete
                </button>
            </div>
            <?php else: ?>
            <div style="margin-top:40px;padding:14px 20px;background:rgba(34,197,94,0.06);border:1px solid rgba(34,197,94,0.2);border-radius:var(--radius-md);display:flex;align-items:center;gap:10px;">
                <i class="fas fa-check-circle" style="color:var(--primary);font-size:16px;"></i>
                <span style="font-size:13px;color:var(--primary);font-weight:600;">Lesson completed!</span>
            </div>
            <?php endif; ?>

            <!-- Bottom nav: Prev / Next (or Cert CTA) -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:24px;padding-top:24px;border-top:1px solid var(--border);gap:12px;flex-wrap:wrap;">

                <!-- Prev -->
                <?php if ($prevLesson): ?>
                    <a href="<?php echo BASE_URL; ?>/courses/learn.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $prevLesson['id']; ?>" class="learn-nav-btn">
                        <i class="fas fa-arrow-left"></i> <?php echo e(truncate($prevLesson['title'], 28)); ?>
                    </a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <!-- Next / Cert -->
                <?php if ($isLastLesson): ?>
                    <?php if ($isCourseComplete): ?>
                        <a href="<?php echo BASE_URL; ?>/certificates/generate/<?php echo e($course['slug']); ?>"
                           class="learn-nav-btn primary" style="padding:12px 28px;font-size:14px;gap:10px;">
                            🎓 <span>Get Your Certificate</span>
                        </a>
                    <?php else: ?>
                        <button disabled class="learn-nav-btn primary" id="nextBtn"
                                style="opacity:0.4;cursor:not-allowed;"
                                title="Mark this lesson complete to unlock your certificate">
                            🎓 Get Certificate <i class="fas fa-lock" style="font-size:11px;"></i>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($isCurrentDone): ?>
                        <a href="<?php echo BASE_URL; ?>/courses/learn.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $nextLesson['id']; ?>"
                           class="learn-nav-btn primary" id="nextBtn">
                            <?php echo e(truncate($nextLesson['title'], 28)); ?> <i class="fas fa-arrow-right"></i>
                        </a>
                    <?php else: ?>
                        <button disabled class="learn-nav-btn primary" id="nextBtn"
                                style="opacity:0.4;cursor:not-allowed;"
                                title="Mark this lesson complete first">
                            <?php echo e(truncate($nextLesson['title'], 28)); ?> <i class="fas fa-lock" style="font-size:11px;"></i>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Certificate completion banner (shown when course = 100%) -->
            <?php if ($isCourseComplete): ?>
            <div class="cert-banner animate-fade-up">
                <div class="cert-banner-icon">🏆</div>
                <div class="cert-banner-text">
                    <h3>Congratulations! You've completed this course!</h3>
                    <p>You've finished all <?php echo $totalLessons; ?> lesson(s) in <strong style="color:#fff;"><?php echo e($course['title']); ?></strong>. Your certificate is ready.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/certificates/generate/<?php echo e($course['slug']); ?>"
                   class="learn-nav-btn primary" style="flex-shrink:0;padding:12px 28px;font-size:14px;">
                    <i class="fas fa-download"></i> Download Certificate
                </a>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
