<?php
// ============================================
// EDUCORE - Student Dashboard
// ============================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['user_role'] !== 'student') {
    header("Location: " . BASE_URL . "/dashboard/" . $_SESSION['user_role'] . ".php"); exit();
}

$db   = getDB();
$uid  = $_SESSION['user_id'];
$tab  = $_GET['tab'] ?? 'overview';
$user = getCurrentUser();

// Stats
$enrolledCount   = $db->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id=? AND status='active'"); $enrolledCount->execute([$uid]); $enrolledCount = $enrolledCount->fetchColumn();
$completedCount  = $db->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id=? AND status='completed'"); $completedCount->execute([$uid]); $completedCount = $completedCount->fetchColumn();
$certCount       = $db->prepare("SELECT COUNT(*) FROM certificates WHERE user_id=?"); $certCount->execute([$uid]); $certCount = $certCount->fetchColumn();
$quizCount       = $db->prepare("SELECT COUNT(*) FROM quiz_results WHERE user_id=?"); $quizCount->execute([$uid]); $quizCount = $quizCount->fetchColumn();

// --- Student Performance Scoring Algorithm ---
// Quiz component (40% weight): avg_quiz_percentage = AVG(quiz_results.percentage)
$avgQuizQuery = $db->prepare("SELECT AVG(percentage) FROM quiz_results WHERE user_id = ?");
$avgQuizQuery->execute([$uid]);
$avgQuizPercentage = $avgQuizQuery->fetchColumn();
$avgQuizPercentage = $avgQuizPercentage !== null ? floatval($avgQuizPercentage) : 0.0;
$quizScore = $avgQuizPercentage * 0.4;

// Progress component (35% weight): avg_progress = AVG(enrollments.progress)
$avgProgressQuery = $db->prepare("SELECT AVG(progress) FROM enrollments WHERE user_id = ? AND status IN ('active', 'completed')");
$avgProgressQuery->execute([$uid]);
$avgProgress = $avgProgressQuery->fetchColumn();
$avgProgress = $avgProgress !== null ? floatval($avgProgress) : 0.0;
$progressScore = $avgProgress * 0.35;

// Completion component (25% weight): completion_rate = completed_courses / total_enrolled * 100
$totalEnrolled = intval($enrolledCount) + intval($completedCount);
$completionRate = $totalEnrolled > 0 ? (intval($completedCount) / $totalEnrolled) * 100 : 0.0;
$completionScore = $completionRate * 0.25;

// Total Performance Score
$performanceScore = $quizScore + $progressScore + $completionScore;

// Determine Grade & Themes
if ($performanceScore >= 90) {
    $gradeLabel = "A — Outstanding 🏆";
    $gradeColor = "#22c55e"; // Green
} elseif ($performanceScore >= 75) {
    $gradeLabel = "B — Good 👍";
    $gradeColor = "#3b82f6"; // Blue
} elseif ($performanceScore >= 60) {
    $gradeLabel = "C — Average 📚";
    $gradeColor = "#f59e0b"; // Orange/Yellow
} else {
    $gradeLabel = "D — Needs Improvement 💪";
    $gradeColor = "#ef4444"; // Red
}

// Enrolled courses
$enrolledCourses = $db->prepare("
    SELECT e.*, co.title, co.type, co.total_lessons, co.price, co.level,
           u.name as instructor_name, cat.name as category_name
    FROM enrollments e
    JOIN courses co ON co.id = e.course_id
    JOIN users u ON u.id = co.instructor_id
    LEFT JOIN categories cat ON cat.id = co.category_id
    WHERE e.user_id = ? ORDER BY e.enrolled_at DESC
");
$enrolledCourses->execute([$uid]);
$enrolledCourses = $enrolledCourses->fetchAll();

// Certificates
$certificates = $db->prepare("
    SELECT cert.*, co.title as course_title, co.type as course_type
    FROM certificates cert
    JOIN courses co ON co.id = cert.course_id
    WHERE cert.user_id = ? ORDER BY cert.issued_at DESC
");
$certificates->execute([$uid]);
$certificates = $certificates->fetchAll();

// Quiz results
$quizResults = $db->prepare("
    SELECT qr.*, q.title as quiz_title FROM quiz_results qr
    JOIN quizzes q ON q.id = qr.quiz_id
    WHERE qr.user_id = ? ORDER BY qr.taken_at DESC LIMIT 10
");
$quizResults->execute([$uid]);
$quizResults = $quizResults->fetchAll();

// Notifications
$notifs = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$notifs->execute([$uid]);
$notifs = $notifs->fetchAll();

// Recommended courses (not enrolled, approved)
$recommended = $db->prepare("
    SELECT co.*, u.name as instructor_name, cat.name as category_name, AVG(r.rating) as avg_rating
    FROM courses co JOIN users u ON u.id=co.instructor_id
    LEFT JOIN categories cat ON cat.id=co.category_id
    LEFT JOIN reviews r ON r.course_id=co.id
    WHERE co.status='approved'
    AND co.id NOT IN (SELECT course_id FROM enrollments WHERE user_id=?)
    GROUP BY co.id ORDER BY avg_rating DESC LIMIT 4
");
$recommended->execute([$uid]);
$recommended = $recommended->fetchAll();

$catIcons = ['Programming'=>'💻','Design'=>'🎨','Business'=>'💼','Music'=>'🎵','Photography'=>'📷','Marketing'=>'📢','Data Science'=>'📊','Personal Dev'=>'🚀'];
$pageTitle = "My Dashboard";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?php echo strtoupper(substr($user['name'],0,1)); ?></div>
            <div class="sidebar-name"><?php echo e($user['name']); ?></div>
            <div class="sidebar-email"><?php echo e($user['email']); ?></div>
            <span class="role-badge role-student mt-2">Student</span>
        </div>
        <nav class="sidebar-nav">
            <a href="?tab=overview"      class="sidebar-link <?php echo $tab==='overview'?'active':''; ?>"><i class="fas fa-home"></i> Overview</a>
            <a href="?tab=courses"       class="sidebar-link <?php echo $tab==='courses'?'active':''; ?>"><i class="fas fa-book-open"></i> My Courses <span class="sidebar-link-badge"><?php echo $enrolledCount; ?></span></a>
            <a href="?tab=quizzes"       class="sidebar-link <?php echo $tab==='quizzes'?'active':''; ?>"><i class="fas fa-brain"></i> Quiz Results</a>
            <a href="?tab=certificates"  class="sidebar-link <?php echo $tab==='certificates'?'active':''; ?>"><i class="fas fa-certificate"></i> Certificates <span class="sidebar-link-badge"><?php echo $certCount; ?></span></a>
            <a href="?tab=recommended"   class="sidebar-link <?php echo $tab==='recommended'?'active':''; ?>"><i class="fas fa-robot"></i>Recommended</a>
            <div style="height:1px;background:var(--border);margin:12px 0;"></div>
            <a href="<?php echo BASE_URL; ?>/courses/index.php" class="sidebar-link"><i class="fas fa-compass"></i> Browse Courses</a>
            <a href="<?php echo BASE_URL; ?>/quiz/index.php"    class="sidebar-link"><i class="fas fa-gamepad"></i> Take a Quiz</a>
            <a href="<?php echo BASE_URL; ?>/logout.php"        class="sidebar-link" style="color:#f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">

        <!-- Mobile tabs -->
        <div style="display:flex;gap:8px;overflow-x:auto;margin-bottom:24px;padding-bottom:4px;" class="d-lg-none">
            <?php foreach (['overview'=>'Overview','courses'=>'Courses','quizzes'=>'Quizzes','certificates'=>'Certs'] as $t=>$l): ?>
            <a href="?tab=<?php echo $t; ?>" style="padding:7px 16px;border-radius:50px;white-space:nowrap;font-size:13px;font-weight:600;background:<?php echo $tab===$t?'var(--gradient-primary)':'var(--bg-card)'; ?>;color:<?php echo $tab===$t?'#fff':'var(--text-muted)'; ?>;border:1px solid var(--border);"><?php echo $l; ?></a>
            <?php endforeach; ?>
        </div>

        <!-- ===== OVERVIEW TAB ===== -->
        <?php if ($tab === 'overview'): ?>
        <div class="animate-fade-up">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 style="font-size:1.6rem;font-weight:900;margin-bottom:4px;">Welcome back, <?php echo e(explode(' ',$user['name'])[0]); ?>! 👋</h1>
                    <p style="color:var(--text-muted);font-size:14px;">Keep up the great work. Your learning streak continues!</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/courses/index.php" class="btn-primary-custom" style="padding:10px 22px;font-size:14px;">
                    <i class="fas fa-plus"></i> Explore Courses
                </a>
            </div>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <?php
                $statCards = [
                    ['icon'=>'fas fa-book-open','val'=>$enrolledCount,'label'=>'Active Courses','color'=>'#22c55e','change'=>'+2 this month'],
                    ['icon'=>'fas fa-check-circle','val'=>$completedCount,'label'=>'Completed','color'=>'#3b82f6','change'=>'Keep going!'],
                    ['icon'=>'fas fa-certificate','val'=>$certCount,'label'=>'Certificates','color'=>'#8b5cf6','change'=>'Showcase on CV'],
                    ['icon'=>'fas fa-brain','val'=>$quizCount,'label'=>'Quizzes Taken','color'=>'#f59e0b','change'=>'Sharpen your mind'],
                ];
                foreach ($statCards as $sc): ?>
                <div class="col-6 col-xl-3">
                    <div class="stat-card" style="--stat-color:<?php echo $sc['color']; ?>;">
                        <div class="stat-icon" style="background:<?php echo $sc['color']; ?>1a;">
                            <i class="<?php echo $sc['icon']; ?>" style="color:<?php echo $sc['color']; ?>;font-size:20px;"></i>
                        </div>
                        <div class="stat-value" style="color:<?php echo $sc['color']; ?>;"><?php echo $sc['val']; ?></div>
                        <div class="stat-label"><?php echo $sc['label']; ?></div>
                        <div class="stat-change change-up" style="font-size:12px;"><?php echo $sc['change']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Performance Card Section -->
            <style>
                .performance-gauge-col {
                    border-right: 1px solid var(--border);
                    padding-right: 24px;
                }
                @media (max-width: 991.98px) {
                    .performance-gauge-col {
                        border-right: none !important;
                        border-bottom: 1px solid var(--border);
                        padding-right: 0 !important;
                        padding-bottom: 24px;
                        margin-bottom: 24px;
                    }
                }
            </style>
            
            <div style="background:var(--gradient-card); border: 1px solid var(--border); border-radius:var(--radius-lg); padding:28px; margin-bottom:24px; position:relative; overflow:hidden;" class="animate-fade-up">
                <div style="position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));"></div>
                <div class="row align-items-center">
                    <!-- Visual Score Gauge -->
                    <div class="col-lg-5 text-center performance-gauge-col">
                        <h3 style="font-size:16px; font-weight:700; margin-bottom:20px; text-transform:uppercase; letter-spacing:1px; color:var(--text-secondary);">Your Learning Performance</h3>
                        <div style="position:relative; width:160px; height:160px; margin:0 auto 16px auto; display:flex; align-items:center; justify-content:center;">
                            <!-- Circular Progress SVG -->
                            <svg width="160" height="160" viewBox="0 0 160 160" style="transform: rotate(-90deg);">
                                <circle cx="80" cy="80" r="70" fill="transparent" stroke="var(--bg-input)" stroke-width="10" />
                                <circle cx="80" cy="80" r="70" fill="transparent" stroke="url(#scoreGrad)" stroke-width="10" 
                                        stroke-dasharray="439.8" stroke-dashoffset="<?php echo 439.8 - (439.8 * min(100, max(0, $performanceScore)) / 100); ?>" 
                                        stroke-linecap="round" style="transition: stroke-dashoffset 1s ease-in-out;" />
                                <defs>
                                    <linearGradient id="scoreGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="<?php echo $gradeColor; ?>" />
                                        <stop offset="100%" stop-color="var(--secondary)" />
                                    </linearGradient>
                                </defs>
                            </svg>
                            <div style="position:absolute; text-align:center;">
                                <div style="font-size:36px; font-weight:900; color:#fff; line-height:1;"><?php echo number_format($performanceScore, 1); ?></div>
                                <div style="font-size:12px; color:var(--text-muted); font-weight:600; margin-top:4px;">Score</div>
                            </div>
                        </div>
                        <div style="font-size:16px; font-weight:800; color:<?php echo $gradeColor; ?>; background:rgba(255,255,255,0.03); display:inline-block; padding:6px 20px; border-radius:50px; border:1px solid rgba(255,255,255,0.05); margin-bottom:12px;">
                            <?php echo $gradeLabel; ?>
                        </div>
                        <p style="font-size:12px; color:var(--text-muted); max-width:280px; margin:0 auto;">Your performance is updated in real-time based on your activity across quizzes and courses.</p>
                    </div>
                    
                    <!-- Summary Table -->
                    <div class="col-lg-7 ps-lg-4">
                        <h3 style="font-size:16px; font-weight:700; margin-bottom:16px; color:var(--text-secondary);"><i class="fas fa-list-check" style="margin-right:8px; color:var(--primary);"></i>Score Breakdown</h3>
                        <div class="table-responsive">
                            <table class="table table-dark-custom mb-0" style="background:transparent; border:none;">
                                <thead>
                                    <tr style="border-bottom:1px solid var(--border); font-size:11px; color:var(--text-muted); text-transform:uppercase;">
                                        <th style="padding:10px 8px; border:none; color:var(--text-muted) !important;">Component</th>
                                        <th style="padding:10px 8px; text-align:center; border:none; color:var(--text-muted) !important;">Raw Metric</th>
                                        <th style="padding:10px 8px; text-align:center; border:none; color:var(--text-muted) !important;">Weight</th>
                                        <th style="padding:10px 8px; text-align:right; border:none; color:var(--text-muted) !important;">Contribution</th>
                                    </tr>
                                </thead>
                                <tbody style="font-size:13.5px; color:#fff;">
                                    <tr style="border-bottom:1px solid var(--border);">
                                        <td style="padding:12px 8px; font-weight:600; border:none; color:#fff !important;">
                                            <i class="fas fa-brain" style="color:#f59e0b; margin-right:8px; width:16px;"></i>Quiz Performance
                                        </td>
                                        <td style="padding:12px 8px; text-align:center; border:none; color:var(--text-secondary) !important;">
                                            <?php echo number_format($avgQuizPercentage, 1); ?>% Avg
                                        </td>
                                        <td style="padding:12px 8px; text-align:center; border:none; color:var(--text-muted) !important;">
                                            40%
                                        </td>
                                        <td style="padding:12px 8px; text-align:right; border:none; font-weight:700; color:#fff !important;">
                                            +<?php echo number_format($quizScore, 2); ?>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom:1px solid var(--border);">
                                        <td style="padding:12px 8px; font-weight:600; border:none; color:#fff !important;">
                                            <i class="fas fa-book-open" style="color:#22c55e; margin-right:8px; width:16px;"></i>Course Progress
                                        </td>
                                        <td style="padding:12px 8px; text-align:center; border:none; color:var(--text-secondary) !important;">
                                            <?php echo number_format($avgProgress, 1); ?>% Avg
                                        </td>
                                        <td style="padding:12px 8px; text-align:center; border:none; color:var(--text-muted) !important;">
                                            35%
                                        </td>
                                        <td style="padding:12px 8px; text-align:right; border:none; font-weight:700; color:#fff !important;">
                                            +<?php echo number_format($progressScore, 2); ?>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom:1px solid var(--border);">
                                        <td style="padding:12px 8px; font-weight:600; border:none; color:#fff !important;">
                                            <i class="fas fa-check-circle" style="color:#3b82f6; margin-right:8px; width:16px;"></i>Course Completion
                                        </td>
                                        <td style="padding:12px 8px; text-align:center; border:none; color:var(--text-secondary) !important;">
                                            <?php echo number_format($completionRate, 1); ?>% Rate
                                        </td>
                                        <td style="padding:12px 8px; text-align:center; border:none; color:var(--text-muted) !important;">
                                            25%
                                        </td>
                                        <td style="padding:12px 8px; text-align:right; border:none; font-weight:700; color:#fff !important;">
                                            +<?php echo number_format($completionScore, 2); ?>
                                        </td>
                                    </tr>
                                    <tr style="font-weight:700; font-size:14.5px;">
                                        <td style="padding:16px 8px 0 8px; border:none; color:#fff !important;">
                                            Overall Score
                                        </td>
                                        <td colspan="2" style="padding:16px 8px 0 8px; border:none;"></td>
                                        <td style="padding:16px 8px 0 8px; text-align:right; border:none; color:<?php echo $gradeColor; ?> !important; font-size:17px;">
                                            <?php echo number_format($performanceScore, 1); ?> / 100
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Continue Learning -->
                <div class="col-lg-7">
                    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;">
                        <h3 style="font-size:16px;font-weight:700;margin-bottom:20px;">📚 Continue Learning</h3>
                        <?php if (empty($enrolledCourses)): ?>
                            <div style="text-align:center;padding:32px;color:var(--text-muted);">
                                <i class="fas fa-book" style="font-size:36px;margin-bottom:12px;display:block;"></i>
                                No courses yet. <a href="<?php echo BASE_URL; ?>/courses/index.php" style="color:var(--primary);">Browse courses</a>
                            </div>
                        <?php else: ?>
                            <div style="display:flex;flex-direction:column;gap:16px;">
                            <?php foreach (array_slice($enrolledCourses,0,3) as $ec):
                                $icon = $catIcons[$ec['category_name']] ?? '📚'; ?>
                            <div style="display:flex;align-items:center;gap:14px;padding:14px;background:var(--bg-input);border-radius:var(--radius-md);border:1px solid var(--border);">
                                <div style="width:52px;height:52px;background:linear-gradient(135deg,#0d1a2e,#0a2010);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;"><?php echo $icon; ?></div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:14px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($ec['title']); ?></div>
                                    <div style="font-size:12px;color:var(--text-muted);margin:4px 0;">by <?php echo e($ec['instructor_name']); ?></div>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="progress-bar-custom" style="flex:1;margin:0;">
                                            <div class="progress-fill" data-width="<?php echo $ec['progress']; ?>%" style="width:<?php echo $ec['progress']; ?>%;"></div>
                                        </div>
                                        <span style="font-size:12px;font-weight:700;color:var(--primary);white-space:nowrap;"><?php echo $ec['progress']; ?>%</span>
                                    </div>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/courses/learn.php?course_id=<?php echo $ec['course_id']; ?>" class="btn-enroll-sm" style="flex-shrink:0;font-size:12px;padding:6px 14px;">Continue →</a>
                            </div>
                            <?php endforeach; ?>
                            </div>
                            <?php if (count($enrolledCourses) > 3): ?>
                                <a href="?tab=courses" style="display:block;text-align:center;margin-top:14px;font-size:14px;color:var(--primary);">View all <?php echo count($enrolledCourses); ?> courses →</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications + Quick Links -->
                <div class="col-lg-5">
                    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:16px;">
                        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;">🔔 Recent Activity</h3>
                        <?php foreach ($notifs as $n): ?>
                        <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);">
                            <div style="width:8px;height:8px;background:<?php echo $n['is_read']?'var(--text-muted)':'var(--primary)'; ?>;border-radius:50%;margin-top:5px;flex-shrink:0;"></div>
                            <div>
                                <div style="font-size:13px;color:var(--text-secondary);line-height:1.5;"><?php echo e($n['message']); ?></div>
                                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?php echo timeAgo($n['created_at']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <a href="<?php echo BASE_URL; ?>/quiz/index.php" style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:var(--radius-md);padding:16px;text-align:center;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                            <i class="fas fa-brain" style="font-size:24px;color:#60a5fa;margin-bottom:8px;display:block;"></i>
                            <div style="font-size:13px;font-weight:600;color:var(--text-primary);">Take a Quiz</div>
                        </a>
                        <a href="?tab=certificates" style="background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.2);border-radius:var(--radius-md);padding:16px;text-align:center;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                            <i class="fas fa-certificate" style="font-size:24px;color:#a78bfa;margin-bottom:8px;display:block;"></i>
                            <div style="font-size:13px;font-weight:600;color:var(--text-primary);">My Certs</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== COURSES TAB ===== -->
        <?php elseif ($tab === 'courses'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;">My Enrolled Courses</h1>
            <?php if (empty($enrolledCourses)): ?>
                <div style="text-align:center;padding:60px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);">
                    <div style="font-size:56px;margin-bottom:16px;">📚</div>
                    <h3>No courses yet</h3>
                    <p style="color:var(--text-muted);margin-bottom:24px;">Start learning today — explore our free and premium courses.</p>
                    <a href="<?php echo BASE_URL; ?>/courses/index.php" class="btn-primary-custom">Browse Courses</a>
                </div>
            <?php else: ?>
                <div class="courses-grid">
                <?php foreach ($enrolledCourses as $ec):
                    $icon = $catIcons[$ec['category_name']] ?? '📚'; ?>
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
                    <div style="aspect-ratio:16/9;background:linear-gradient(135deg,#0d1a2e,#0a2010);display:flex;align-items:center;justify-content:center;font-size:52px;position:relative;">
                        <?php echo $icon; ?>
                        <span class="course-badge badge-<?php echo $ec['type']; ?>" style="position:absolute;top:10px;right:10px;"><?php echo strtoupper($ec['type']); ?></span>
                    </div>
                    <div style="padding:18px;">
                        <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;"><?php echo e($ec['category_name']); ?></div>
                        <div style="font-size:15px;font-weight:700;margin-bottom:4px;"><?php echo e($ec['title']); ?></div>
                        <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">by <?php echo e($ec['instructor_name']); ?></div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px;">
                            <span style="color:var(--text-muted);">Progress</span>
                            <span style="color:var(--primary);font-weight:700;"><?php echo $ec['progress']; ?>%</span>
                        </div>
                        <div class="progress-bar-custom"><div class="progress-fill" data-width="<?php echo $ec['progress']; ?>%" style="width:<?php echo $ec['progress']; ?>%;"></div></div>
                        <div style="display:flex;gap:8px;margin-top:14px;">
                            <a href="<?php echo BASE_URL; ?>/courses/learn.php?course_id=<?php echo $ec['course_id']; ?>" class="btn-enroll-sm" style="flex:1;justify-content:center;">
                                <?php echo $ec['progress']>=100 ? '🎉 Review' : 'Continue'; ?> →
                            </a>
                            <?php if ($ec['progress'] >= 100): ?>
                                <a href="<?php echo BASE_URL; ?>/certificates/generate.php?course_id=<?php echo $ec['course_id']; ?>" class="btn-enroll-sm" style="background:rgba(139,92,246,0.1);color:#a78bfa;border:1px solid rgba(139,92,246,0.3);">
                                    <i class="fas fa-certificate"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== QUIZZES TAB ===== -->
        <?php elseif ($tab === 'quizzes'): ?>
        <div class="animate-fade-up">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                <h1 style="font-size:1.5rem;font-weight:900;">Quiz Results</h1>
                <a href="<?php echo BASE_URL; ?>/quiz/index.php" class="btn-primary-custom" style="padding:10px 20px;font-size:14px;"><i class="fas fa-play"></i> Take a Quiz</a>
            </div>
            <?php if (empty($quizResults)): ?>
                <div style="text-align:center;padding:60px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);">
                    <div style="font-size:56px;margin-bottom:16px;">🧠</div>
                    <h3>No quizzes taken yet</h3>
                    <p style="color:var(--text-muted);margin-bottom:24px;">Test your knowledge and track your progress.</p>
                    <a href="<?php echo BASE_URL; ?>/quiz/index.php" class="btn-primary-custom">Start a Quiz</a>
                </div>
            <?php else: ?>
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
                    <table class="table table-dark-custom mb-0">
                        <thead><tr style="border-color:var(--border);">
                            <th style="padding:14px 18px;font-size:13px;color:#fff;font-weight:600;">Quiz</th>
                            <th style="padding:14px 18px;font-size:13px;color:#fff;font-weight:600;">Score</th>
                            <th style="padding:14px 18px;font-size:13px;color:#fff;font-weight:600;">Result</th>
                            <th style="padding:14px 18px;font-size:13px;color:#fff;font-weight:600;">Date</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($quizResults as $qr): ?>
                        <tr style="border-color:var(--border);">
                            <td style="padding:14px 18px;font-size:14px;font-weight:600;color:#fff;"><?php echo e($qr['quiz_title']); ?></td>
                            <td style="padding:14px 18px;">
                                <span style="font-weight:700;color:<?php echo $qr['passed']?'var(--primary)':'#f87171'; ?>;"><?php echo $qr['score']; ?>/<?php echo $qr['total_questions']; ?></span>
                                <span style="font-size:12px;color:#fff;margin-left:6px;">(<?php echo number_format($qr['percentage'],0); ?>%)</span>
                            </td>
                            <td style="padding:14px 18px;">
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:50px;font-size:12px;font-weight:700;background:<?php echo $qr['passed']?'rgba(34,197,94,0.1)':'rgba(239,68,68,0.1)'; ?>;color:<?php echo $qr['passed']?'var(--primary)':'#f87171'; ?>;">
                                    <i class="fas fa-<?php echo $qr['passed']?'check':'times'; ?>"></i> <?php echo $qr['passed']?'Passed':'Failed'; ?>
                                </span>
                            </td>
                            <td style="padding:14px 18px;font-size:13px;color:#fff;"><?php echo date('M d, Y', strtotime($qr['taken_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== CERTIFICATES TAB ===== -->
        <?php elseif ($tab === 'certificates'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;">My Certificates</h1>
            <?php if (empty($certificates)): ?>
                <div style="text-align:center;padding:60px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);">
                    <div style="font-size:56px;margin-bottom:16px;">🏆</div>
                    <h3>No certificates yet</h3>
                    <p style="color:var(--text-muted);margin-bottom:4px;">Complete a course to earn your certificate.</p>
                    <p style="color:var(--text-muted);font-size:13px;margin-bottom:24px;">Tip: For demo purposes, you can generate a certificate for any enrolled course.</p>
                    <?php if (!empty($enrolledCourses)): ?>
                        <a href="<?php echo BASE_URL; ?>/certificates/generate.php?course_id=<?php echo $enrolledCourses[0]['course_id']; ?>" class="btn-primary-custom">Generate Demo Certificate</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row g-4">
                <?php foreach ($certificates as $cert): ?>
                <div class="col-md-6">
                    <div style="background:linear-gradient(135deg,#0a1628,#0d2210);border:2px solid rgba(34,197,94,0.25);border-radius:var(--radius-xl);padding:28px;text-align:center;position:relative;overflow:hidden;">
                        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:var(--gradient-primary);"></div>
                        <div style="font-size:40px;margin-bottom:10px;">🏆</div>
                        <div style="font-size:11px;letter-spacing:2px;color:var(--text-muted);text-transform:uppercase;margin-bottom:6px;">Certificate of Completion</div>
                        <div style="font-size:16px;font-weight:800;margin-bottom:4px;"><?php echo e($cert['course_title']); ?></div>
                        <div style="font-size:12px;color:var(--primary);margin-bottom:16px;font-weight:600;"><?php echo e($user['name']); ?></div>
                        <div style="font-size:11px;color:var(--text-muted);margin-bottom:16px;">Issued: <?php echo date('F d, Y', strtotime($cert['issued_at'])); ?></div>
                        <div style="font-size:10px;color:var(--text-muted);background:rgba(255,255,255,0.04);padding:6px 12px;border-radius:6px;margin-bottom:16px;font-family:monospace;">ID: <?php echo e($cert['certificate_code']); ?></div>
                        <a href="<?php echo BASE_URL; ?>/certificates/generate.php?course_id=<?php echo $cert['course_id']; ?>" class="btn-enroll-sm">
                            <i class="fas fa-eye"></i> View Certificate
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== RECOMMENDED TAB ===== -->
        <?php elseif ($tab === 'recommended'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:4px;">Recommended For You</h1>
            <p style="color:var(--text-muted);font-size:14px;margin-bottom:28px;">Personalized picks based on your courses, quiz results, and learning level.</p>

            <!-- AI signal badges -->
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px;">
                <?php
                $signals = [];
                if (!empty($enrolledCourses))  $signals[] = ['icon'=>'fas fa-book-open','color'=>'#22c55e','label'=>'Your enrolled courses'];
                if (!empty($quizResults))       $signals[] = ['icon'=>'fas fa-brain','color'=>'#3b82f6','label'=>'Quiz performance'];
                $signals[] = ['icon'=>'fas fa-chart-line','color'=>'#8b5cf6','label'=>'Popularity trends'];
                $signals[] = ['icon'=>'fas fa-signal','color'=>'#f59e0b','label'=>'Your learning level'];
                foreach ($signals as $sig): ?>
                <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:50px;padding:5px 14px;font-size:12px;color:var(--text-secondary);">
                    <i class="<?php echo $sig['icon']; ?>" style="color:<?php echo $sig['color']; ?>;"></i>
                    <?php echo $sig['label']; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Loading state -->
            <div id="dashRecsLoading" style="text-align:center;padding:60px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);">
                <i class="fas fa-robot fa-spin" style="font-size:36px;color:var(--primary);margin-bottom:12px;display:block;"></i>
                <p style="color:var(--text-muted);">AI is analyzing your profile...</p>
            </div>

            <div class="courses-grid" id="dashRecsGrid" style="display:none;"></div>

            <div id="dashRecsEmpty" style="display:none;text-align:center;padding:60px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);">
                <div style="font-size:48px;margin-bottom:12px;">🤖</div>
                <h3 style="margin-bottom:8px;">No recommendations yet</h3>
                <p style="color:var(--text-muted);margin-bottom:20px;"></p>
                <a href="<?php echo BASE_URL; ?>/courses/index.php" class="btn-primary-custom">Browse Courses</a>
            </div>
        </div>

        <script>
        (function loadDashRecs() {
            const userId  = <?php echo $uid; ?>;
            const baseUrl = '<?php echo BASE_URL; ?>';

            fetch(`${baseUrl}/api/recommendations.php?user_id=${userId}`)
                .then(r => r.json())
                .then(courses => {
                    document.getElementById('dashRecsLoading').style.display = 'none';
                    if (!courses.length) {
                        document.getElementById('dashRecsEmpty').style.display = 'block';
                        return;
                    }
                    const grid = document.getElementById('dashRecsGrid');
                    grid.style.display = 'grid';
                    grid.innerHTML = courses.map(c => {
                        const stars = Array.from({length:5}, (_,i) =>
                            `<i class="fas fa-star" style="font-size:11px;color:${i < Math.round(c.rating) ? '#fbbf24' : 'var(--text-muted)'}"></i>`
                        ).join('');
                        const price = c.price == 0
                            ? '<span class="course-price price-free">FREE</span>'
                            : `<span class="course-price price-paid">NPR ${Number(c.price).toLocaleString()}</span>`;
                        return `
                        <a href="${baseUrl}/courses/detail.php?id=${c.id}" class="course-card">
                            <div class="course-thumbnail">
                                <div class="course-thumbnail-placeholder">${c.icon}</div>
                                <span class="course-badge badge-${c.type}">${c.type.toUpperCase()}</span>
                                <div style="position:absolute;bottom:10px;left:10px;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);border-radius:50px;padding:3px 10px;font-size:11px;font-weight:600;color:#fff;border:1px solid rgba(255,255,255,0.12);">
                                    ${c.reason_label}
                                </div>
                            </div>
                            <div class="course-info">
                                <div class="course-category-tag">${c.category}</div>
                                <h3 class="course-title">${c.title}</h3>
                                <div class="course-instructor">
                                    <span class="instructor-dot">${c.instructor.charAt(0).toUpperCase()}</span>
                                    ${c.instructor}
                                </div>
                                <div class="course-rating">
                                    ${stars}
                                    <span class="rating-score">${c.rating.toFixed(1)}</span>
                                    <span class="rating-count">(${c.enrolls} students)</span>
                                </div>
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px;">
                                    <span style="font-size:11px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);color:var(--primary);padding:2px 8px;border-radius:50px;">
                                        ${c.level.charAt(0).toUpperCase() + c.level.slice(1)}
                                    </span>
                                </div>
                                <div class="course-footer">
                                    ${price}
                                    <span class="btn-enroll-sm">${c.price == 0 ? 'Enroll Free' : 'Enroll Now'} →</span>
                                </div>
                            </div>
                        </a>`;
                    }).join('');
                })
                .catch(() => {
                    document.getElementById('dashRecsLoading').style.display = 'none';
                    document.getElementById('dashRecsEmpty').style.display   = 'block';
                });
        })();
        </script>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
