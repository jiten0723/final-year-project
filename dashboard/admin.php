<?php

// EDUCORE - Admin Dashboard

require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: " . BASE_URL . "/dashboard/" . $_SESSION['user_role'] . ".php"); exit();
}

$db  = getDB();
$tab = $_GET['tab'] ?? 'overview';

// Handle course approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['course_id'])) {
    $action   = $_POST['action'];
    $courseId = (int)$_POST['course_id'];
    if (in_array($action, ['approve','reject'])) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $db->prepare("UPDATE courses SET status=? WHERE id=?")->execute([$status, $courseId]);
        // Notify teacher
        $course = $db->prepare("SELECT title, instructor_id FROM courses WHERE id=?");
        $course->execute([$courseId]);
        $course = $course->fetch();
        if ($course) {
            $msg = $action === 'approve'
                ? "🎉 Your course \"{$course['title']}\" has been approved and is now live!"
                : "Your course \"{$course['title']}\" was not approved. Please review and resubmit.";
            $db->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,?)")
               ->execute([$course['instructor_id'], $msg, $action==='approve'?'success':'info']);
        }
    }
}

// Handle user action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user'])) {
    $userId = (int)$_POST['user_id'];
    $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id=? AND role != 'admin'")->execute([$userId]);
}

// Handle featured toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_featured'])) {
    $courseId = (int)$_POST['course_id'];
    $db->prepare("UPDATE courses SET is_featured = NOT is_featured WHERE id=?")->execute([$courseId]);
}

// Global stats
$stats = $db->query("SELECT
    (SELECT COUNT(*) FROM users WHERE role='student') as students,
    (SELECT COUNT(*) FROM users WHERE role='teacher') as teachers,
    (SELECT COUNT(*) FROM courses WHERE status='approved') as courses,
    (SELECT COUNT(*) FROM courses WHERE status='pending') as pending,
    (SELECT COUNT(*) FROM enrollments) as enrollments,
    (SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed') as revenue,
    (SELECT COUNT(*) FROM reviews) as reviews,
    (SELECT COUNT(*) FROM certificates) as certificates
")->fetch();

// Pending courses
$pending = $db->query("
    SELECT co.*, u.name as instructor_name, cat.name as category_name
    FROM courses co JOIN users u ON u.id=co.instructor_id
    LEFT JOIN categories cat ON cat.id=co.category_id
    WHERE co.status='pending' ORDER BY co.created_at DESC
")->fetchAll();

// All courses
$allCourses = $db->query("
    SELECT co.*, u.name as instructor_name, cat.name as category_name,
           COUNT(DISTINCT e.id) as enrollment_count, AVG(r.rating) as avg_rating
    FROM courses co JOIN users u ON u.id=co.instructor_id
    LEFT JOIN categories cat ON cat.id=co.category_id
    LEFT JOIN enrollments e ON e.course_id=co.id
    LEFT JOIN reviews r ON r.course_id=co.id
    GROUP BY co.id ORDER BY co.created_at DESC
")->fetchAll();

// All users
$allUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Recent payments
$recentPayments = $db->query("
    SELECT p.*, u.name as student_name, co.title as course_title
    FROM payments p JOIN users u ON u.id=p.user_id JOIN courses co ON co.id=p.course_id
    WHERE p.status='completed' ORDER BY p.paid_at DESC LIMIT 15
")->fetchAll();

// All reviews
$allReviews = $db->query("
    SELECT r.*, u.name as user_name, co.title as course_title
    FROM reviews r JOIN users u ON u.id=r.user_id JOIN courses co ON co.id=r.course_id
    ORDER BY r.created_at DESC LIMIT 20
")->fetchAll();

$pageTitle = "Admin Panel";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-user" style="padding:20px 16px;">
            <div class="sidebar-avatar" style="background:linear-gradient(135deg,#ef4444,#dc2626);">A</div>
            <div class="sidebar-name">Admin Panel</div>
            <div class="sidebar-email">EDUCORE</div>
            <span class="role-badge role-admin mt-2">Administrator</span>
        </div>
        <nav class="sidebar-nav">
            <a href="?tab=overview"   class="sidebar-link <?php echo $tab==='overview'?'active':''; ?>"><i class="fas fa-chart-bar"></i> Overview</a>
            <a href="?tab=pending"    class="sidebar-link <?php echo $tab==='pending'?'active':''; ?>"><i class="fas fa-clock"></i> Pending Review <?php if($stats['pending']>0): ?><span class="sidebar-link-badge" style="background:#ef4444;"><?php echo $stats['pending']; ?></span><?php endif; ?></a>
            <a href="?tab=courses"    class="sidebar-link <?php echo $tab==='courses'?'active':''; ?>"><i class="fas fa-book"></i> All Courses</a>
            <a href="?tab=users"      class="sidebar-link <?php echo $tab==='users'?'active':''; ?>"><i class="fas fa-users"></i> Users</a>
            <a href="?tab=payments"   class="sidebar-link <?php echo $tab==='payments'?'active':''; ?>"><i class="fas fa-rupee-sign"></i> Payments</a>
            <a href="?tab=reviews"    class="sidebar-link <?php echo $tab==='reviews'?'active':''; ?>"><i class="fas fa-star"></i> Reviews</a>
            <div style="height:1px;background:var(--border);margin:12px 0;"></div>
            <a href="<?php echo BASE_URL; ?>/index.php"   class="sidebar-link"><i class="fas fa-home"></i> View Site</a>
            <a href="<?php echo BASE_URL; ?>/logout.php"  class="sidebar-link" style="color:#f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="dashboard-content">

        <!--OVERVIEW -->
        <?php if ($tab === 'overview'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.6rem;font-weight:900;margin-bottom:6px;">Admin Overview</h1>
            <p style="color:var(--text-muted);font-size:14px;margin-bottom:28px;">Platform-wide statistics and management.</p>

            <?php if ($stats['pending'] > 0): ?>
            <div class="alert-custom alert-info mb-4">
                <i class="fas fa-exclamation-circle"></i>
                <strong><?php echo $stats['pending']; ?> course<?php echo $stats['pending']>1?'s':''; ?> pending review.</strong>
                <a href="?tab=pending" style="color:var(--secondary);margin-left:8px;">Review now →</a>
            </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <?php foreach ([
                    ['fas fa-user-graduate','val'=>$stats['students'],'label'=>'Students','color'=>'#22c55e'],
                    ['fas fa-chalkboard-teacher','val'=>$stats['teachers'],'label'=>'Teachers','color'=>'#3b82f6'],
                    ['fas fa-book-open','val'=>$stats['courses'],'label'=>'Live Courses','color'=>'#8b5cf6'],
                    ['fas fa-graduation-cap','val'=>$stats['enrollments'],'label'=>'Enrollments','color'=>'#f59e0b'],
                    ['fas fa-rupee-sign','val'=>'NPR '.number_format($stats['revenue'],0),'label'=>'Revenue','color'=>'#10b981'],
                    ['fas fa-star','val'=>$stats['reviews'],'label'=>'Reviews','color'=>'#ec4899'],
                    ['fas fa-certificate','val'=>$stats['certificates'],'label'=>'Certificates','color'=>'#06b6d4'],
                    ['fas fa-clock','val'=>$stats['pending'],'label'=>'Pending','color'=>'#ef4444'],
                ] as $sc): ?>
                <div class="col-6 col-md-3">
                    <div class="stat-card" style="--stat-color:<?php echo $sc['color']; ?>;">
                        <div class="stat-icon" style="background:<?php echo $sc['color']; ?>1a;"><i class="<?php echo $sc[0]; ?>" style="color:<?php echo $sc['color']; ?>;font-size:18px;"></i></div>
                        <div class="stat-value" style="font-size:22px;color:<?php echo $sc['color']; ?>;"><?php echo $sc['val']; ?></div>
                        <div class="stat-label" style="font-size:12px;"><?php echo $sc['label']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Users -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:24px;">
                <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;">Recently Joined Users</h3>
                <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach (array_slice($allUsers,0,6) as $u): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px;background:var(--bg-input);border-radius:8px;">
                    <div style="width:36px;height:36px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;flex-shrink:0;"><?php echo strtoupper(substr($u['name'],0,1)); ?></div>
                    <div style="flex:1;">
                        <div style="font-size:14px;font-weight:600;color:#fff;"><?php echo e($u['name']); ?></div>
                        <div style="font-size:12px;color:var(--text-muted);"><?php echo e($u['email']); ?></div>
                    </div>
                    <span class="role-badge role-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span>
                    <div style="font-size:11px;color:var(--text-muted);"><?php echo timeAgo($u['created_at']); ?></div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Reviews -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;gap:12px;flex-wrap:wrap;">
                    <h3 style="font-size:16px;font-weight:700;margin:0;">Recent Student Reviews</h3>
                    <a href="?tab=reviews" style="font-size:13px;color:var(--secondary);">View all reviews →</a>
                </div>
                <?php if (empty($allReviews)): ?>
                    <div style="text-align:center;padding:40px;color:var(--text-muted);">No reviews available yet.</div>
                <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <?php foreach (array_slice($allReviews, 0, 5) as $rev): ?>
                    <div style="background:var(--bg-input);border:1px solid var(--border);border-radius:12px;padding:18px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                            <div>
                                <div style="font-size:14px;font-weight:700;color:var(--text-primary);"><?php echo e($rev['user_name']); ?></div>
                                <div style="font-size:12px;color:var(--text-muted);">Course: <?php echo e(truncate($rev['course_title'], 40)); ?></div>
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <?php for ($i=1; $i<=5; $i++): ?>
                                    <i class="fas fa-star" style="font-size:12px;color:<?php echo $i <= $rev['rating'] ? '#fbbf24' : 'var(--text-muted)'; ?>;"></i>
                                <?php endfor; ?>
                                <span style="font-size:12px;color:var(--text-muted);"><?php echo timeAgo($rev['created_at']); ?></span>
                            </div>
                        </div>
                        <p style="margin:0;font-size:14px;color:var(--text-secondary);line-height:1.7;"><?php echo e($rev['review']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!--PENDING COURSES-->
        <?php elseif ($tab === 'pending'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;">📋 Courses Pending Review</h1>
            <?php if (empty($pending)): ?>
                <div style="text-align:center;padding:60px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);color:var(--text-muted);">
                    <i class="fas fa-check-circle" style="font-size:48px;color:var(--primary);margin-bottom:16px;display:block;"></i>
                    <h3>All caught up!</h3><p>No courses pending review.</p>
                </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:16px;">
            <?php foreach ($pending as $c): ?>
            <div style="background:var(--bg-card);border:1px solid rgba(245,158,11,0.3);border-radius:var(--radius-lg);padding:24px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;">
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                            <span class="course-badge badge-<?php echo $c['type']; ?>" style="position:static;"><?php echo strtoupper($c['type']); ?></span>
                            <span style="font-size:12px;color:var(--text-muted);"><?php echo e($c['category_name']); ?></span>
                        </div>
                        <h3 style="font-size:17px;font-weight:800;margin-bottom:6px;"><?php echo e($c['title']); ?></h3>
                        <p style="color:var(--text-muted);font-size:13px;line-height:1.6;margin-bottom:12px;max-width:600px;"><?php echo e(truncate($c['description'],200)); ?></p>
                        <div style="display:flex;gap:16px;font-size:13px;color:var(--text-muted);flex-wrap:wrap;">
                            <span><i class="fas fa-user me-1"></i>by <?php echo e($c['instructor_name']); ?></span>
                            <span><i class="fas fa-signal me-1"></i><?php echo ucfirst($c['level']); ?></span>
                            <span><i class="fas fa-clock me-1"></i><?php echo e($c['duration']); ?></span>
                            <span><i class="fas fa-rupee-sign me-1"></i><?php echo formatPrice($c['price']); ?></span>
                            <span><i class="fas fa-calendar me-1"></i>Submitted <?php echo timeAgo($c['created_at']); ?></span>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;min-width:160px;">
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="padding:10px 16px;font-size:14px;" onclick="return confirm('Approve this course?')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" style="width:100%;padding:10px 16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;border-radius:50px;font-size:14px;font-weight:600;cursor:pointer;" onclick="return confirm('Reject this course?')">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                        <a href="<?php echo BASE_URL; ?>/courses/<?php echo e($c['slug']); ?>" style="text-align:center;font-size:13px;color:var(--primary);">Preview →</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!--ALL COURSES -->
        <?php elseif ($tab === 'courses'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;">All Courses (<?php echo count($allCourses); ?>)</h1>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
                <table class="table table-dark-custom mb-0">
                    <thead><tr><th style="padding:14px 20px;font-size:13px;color:#fff;">Course</th><th style="padding:14px;color:#fff;">Instructor</th><th style="padding:14px;color:#fff;">Enrolls</th><th style="padding:14px;color:#fff;">Rating</th><th style="padding:14px;color:#fff;">Status</th><th style="padding:14px;color:#fff;">Featured</th></tr></thead>
                    <tbody>
                    <?php foreach ($allCourses as $c): ?>
                    <tr style="border-color:var(--border);">
                        <td style="padding:14px 20px;">
                            <div style="font-weight:700;font-size:13px;color:#fff;"><?php echo e(truncate($c['title'],36)); ?></div>
                            <div style="font-size:11px;color:var(--text-muted);"><?php echo e($c['category_name']); ?> · <?php echo formatPrice($c['price']); ?></div>
                        </td>
                        <td style="padding:14px;font-size:13px;color:#fff;"><?php echo e($c['instructor_name']); ?></td>
                        <td style="padding:14px;font-size:14px;font-weight:700;color:#fff;"><?php echo $c['enrollment_count']; ?></td>
                        <td style="padding:14px;font-size:13px;color:#fbbf24;font-weight:700;"><?php echo number_format($c['avg_rating']??0,1); ?> ★</td>
                        <td style="padding:14px;">
                            <span style="padding:3px 10px;border-radius:50px;font-size:11px;font-weight:700;background:<?php echo match($c['status']){'approved'=>'rgba(34,197,94,0.1)','pending'=>'rgba(245,158,11,0.1)','rejected'=>'rgba(239,68,68,0.1)',default=>'rgba(107,114,128,0.1)'};?>;color:<?php echo match($c['status']){'approved'=>'#4ade80','pending'=>'#fbbf24','rejected'=>'#f87171',default=>'#9ca3af'};?>;">
                                <?php echo ucfirst($c['status']); ?>
                            </span>
                        </td>
                        <td style="padding:14px;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="toggle_featured" value="1">
                                <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                                <button type="submit" style="background:none;border:none;cursor:pointer;font-size:18px;" title="Toggle Featured">
                                    <?php echo $c['is_featured'] ? '⭐' : '☆'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!--USERS-->
        <?php elseif ($tab === 'users'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;">User Management (<?php echo count($allUsers); ?>)</h1>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
                <table class="table table-dark-custom mb-0">
                    <thead><tr><th style="padding:14px 20px;font-size:13px;color:#fff;">User</th><th style="padding:14px;color:#fff;">Role</th><th style="padding:14px;color:#fff;">Status</th><th style="padding:14px;color:#fff;">Joined</th><th style="padding:14px;color:#fff;">Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($allUsers as $u): ?>
                    <tr style="border-color:var(--border);">
                        <td style="padding:14px 20px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;"><?php echo strtoupper(substr($u['name'],0,1)); ?></div>
                                <div>
                                    <div style="font-size:14px;font-weight:600;color:#fff;"><?php echo e($u['name']); ?></div>
                                    <div style="font-size:12px;color:var(--text-muted);"><?php echo e($u['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:14px;"><span class="role-badge role-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                        <td style="padding:14px;">
                            <span style="padding:3px 10px;border-radius:50px;font-size:11px;font-weight:700;background:<?php echo $u['is_active']?'rgba(34,197,94,0.1)':'rgba(239,68,68,0.1)'; ?>;color:<?php echo $u['is_active']?'#4ade80':'#f87171'; ?>;">
                                <?php echo $u['is_active']?'Active':'Banned'; ?>
                            </span>
                        </td>
                        <td style="padding:14px;font-size:12px;color:var(--text-muted);"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td style="padding:14px;">
                            <?php if ($u['role'] !== 'admin'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="toggle_user" value="1">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" onclick="return confirm('<?php echo $u['is_active']?'Ban':'Unban'; ?> this user?')"
                                    style="padding:5px 14px;border-radius:50px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid <?php echo $u['is_active']?'rgba(239,68,68,0.4)':'rgba(34,197,94,0.4)'; ?>;background:<?php echo $u['is_active']?'rgba(239,68,68,0.08)':'rgba(34,197,94,0.08)'; ?>;color:<?php echo $u['is_active']?'#f87171':'#4ade80'; ?>;">
                                    <?php echo $u['is_active']?'Ban':'Unban'; ?>
                                </button>
                            </form>
                            <?php else: echo '<span style="font-size:12px;color:var(--text-muted);">—</span>'; endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!--PAYMENTS-->
        <?php elseif ($tab === 'payments'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;">Payment Transactions</h1>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
                <table class="table table-dark-custom mb-0">
                    <thead><tr><th style="padding:14px 20px;color:#fff;">Student</th><th style="color:#fff;">Course</th><th style="color:#fff;">Amount</th><th style="color:#fff;">Method</th><th style="color:#fff;">Transaction ID</th><th style="color:#fff;">Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentPayments as $p): ?>
                    <tr style="border-color:var(--border);">
                        <td style="padding:14px 20px;font-size:14px;font-weight:600;color:#fff;"><?php echo e($p['student_name']); ?></td>
                        <td style="padding:14px;font-size:13px;color:#fff;"><?php echo e(truncate($p['course_title'],28)); ?></td>
                        <td style="padding:14px;font-weight:700;color:#22c55e;">NPR <?php echo number_format($p['amount'],0); ?></td>
                        <td style="padding:14px;font-size:12px;text-transform:uppercase;"><span style="padding:3px 10px;border-radius:50px;background:rgba(59,130,246,0.1);color:#60a5fa;"><?php echo e($p['method']); ?></span></td>
                        <td style="padding:14px;font-size:11px;color:#fff;font-family:monospace;"><?php echo e($p['transaction_id']??'—'); ?></td>
                        <td style="padding:14px;font-size:12px;color:#fff;"><?php echo date('M d, Y', strtotime($p['paid_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!--REVIEWS-->
        <?php elseif ($tab === 'reviews'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;">Student Reviews</h1>
            <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($allReviews as $rev): ?>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:18px 20px;display:flex;gap:16px;align-items:flex-start;">
                <div style="width:38px;height:38px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;flex-shrink:0;"><?php echo strtoupper(substr($rev['user_name'],0,1)); ?></div>
                <div style="flex:1;">
                    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:6px;">
                        <div>
                            <span style="font-weight:700;font-size:14px;"><?php echo e($rev['user_name']); ?></span>
                            <span style="font-size:12px;color:var(--text-muted);margin-left:8px;">on <em><?php echo e(truncate($rev['course_title'],30)); ?></em></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:4px;">
                            <?php for($i=1;$i<=5;$i++) echo '<i class="fas fa-star" style="font-size:12px;color:'.($i<=$rev['rating']?'#fbbf24':'var(--text-muted)').'"></i>'; ?>
                            <span style="font-size:12px;color:var(--text-muted);margin-left:4px;"><?php echo timeAgo($rev['created_at']); ?></span>
                        </div>
                    </div>
                    <p style="font-size:14px;color:var(--text-secondary);margin:0;line-height:1.6;"><?php echo e($rev['review']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php
$adminFooter = true;
$adminFooterStats = [
    'total_users'   => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_courses' => $db->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
];
include __DIR__ . '/../includes/footer.php';
?>
