<?php
// ============================================
// EDUCORE - Teacher Dashboard
// ============================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: " . BASE_URL . "/dashboard/" . $_SESSION['user_role'] . ".php"); exit();
}

$db   = getDB();
$uid  = $_SESSION['user_id'];
$tab  = $_GET['tab'] ?? 'overview';
$user = getCurrentUser();

// Handle new course submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_course'])) {
    $title     = trim($_POST['title'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $shortDesc = trim($_POST['short_description'] ?? '');
    $catId     = (int)($_POST['category_id'] ?? 0);
    $price     = (float)($_POST['price'] ?? 0);
    $type      = $price > 0 ? 'premium' : 'free';
    $level     = $_POST['level'] ?? 'beginner';
    $duration  = trim($_POST['duration'] ?? '');

    if ($title && $desc) {
        // Generate slug
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i','-',trim($title))) . '-' . time();
        $thumbnail = trim($_POST['thumbnail'] ?? '');
        $db->prepare("INSERT INTO courses (title,slug,description,short_description,category_id,instructor_id,price,type,level,duration,thumbnail,status)
                      VALUES (?,?,?,?,?,?,?,?,?,?,?,'pending')")
           ->execute([$title,$slug,$desc,$shortDesc,$catId,$uid,$price,$type,$level,$duration,$thumbnail]);
        $newCourseId = $db->lastInsertId();
        $courseMsg = ['type'=>'success','text'=>'Course submitted for admin approval! Course ID: #'.$newCourseId];
    } else {
        $courseMsg = ['type'=>'error','text'=>'Title and description are required.'];
    }
}

// Handle course edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_course'])) {
    $editId    = (int)$_POST['course_id'];
    $title     = trim($_POST['title'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $shortDesc = trim($_POST['short_description'] ?? '');
    $catId     = (int)($_POST['category_id'] ?? 0);
    $price     = (float)($_POST['price'] ?? 0);
    $type      = $price > 0 ? 'premium' : 'free';
    $level     = $_POST['level'] ?? 'beginner';
    $duration  = trim($_POST['duration'] ?? '');
    $thumbnail = trim($_POST['thumbnail'] ?? '');

    // Verify teacher owns this course
    $owns = $db->prepare("SELECT id FROM courses WHERE id=? AND instructor_id=?");
    $owns->execute([$editId, $uid]);
    if ($owns->fetch() && $title && $desc) {
        $thumbSql = $thumbnail ? ', thumbnail=?' : '';
        $params   = [$title, $desc, $shortDesc, $catId, $price, $type, $level, $duration];
        if ($thumbnail) $params[] = $thumbnail;
        $params[] = $editId;
        $db->prepare("UPDATE courses SET title=?, description=?, short_description=?, category_id=?, price=?, type=?, level=?, duration=? $thumbSql WHERE id=?")
           ->execute($params);
        $editMsg = ['type'=>'success','text'=>'Course updated successfully!'];
        // Re-fetch courses
        $courses = $db->prepare("SELECT co.*, cat.name as category_name, COUNT(DISTINCT e.id) as enrollment_count, COUNT(DISTINCT r.id) as review_count, AVG(r.rating) as avg_rating, COUNT(DISTINCT l.id) as lesson_count FROM courses co LEFT JOIN categories cat ON cat.id=co.category_id LEFT JOIN enrollments e ON e.course_id=co.id LEFT JOIN reviews r ON r.course_id=co.id LEFT JOIN lessons l ON l.course_id=co.id WHERE co.instructor_id=? GROUP BY co.id ORDER BY co.created_at DESC");
        $courses->execute([$uid]);
        $courses = $courses->fetchAll();
    } else {
        $editMsg = ['type'=>'error','text'=>'Could not update. Check required fields.'];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lesson'])) {
    $courseId    = (int)$_POST['lesson_course_id'];
    $lesTitle    = trim($_POST['lesson_title'] ?? '');
    $lesContent  = trim($_POST['lesson_content'] ?? '');
    $lesDuration = (int)($_POST['lesson_duration'] ?? 10);
    $lesPreview  = isset($_POST['is_free_preview']) ? 1 : 0;
    $lesVideoUrl = trim($_POST['video_url'] ?? '');

    // Verify teacher owns this course
    $owns = $db->prepare("SELECT id FROM courses WHERE id=? AND instructor_id=?");
    $owns->execute([$courseId, $uid]);
    if ($owns->fetch()) {
        $orderNum = $db->prepare("SELECT MAX(order_num)+1 FROM lessons WHERE course_id=?");
        $orderNum->execute([$courseId]);
        $orderNum = $orderNum->fetchColumn() ?: 1;
        $db->prepare("INSERT INTO lessons (course_id,title,content,duration_minutes,order_num,is_free_preview,video_url) VALUES (?,?,?,?,?,?,?)")
           ->execute([$courseId,$lesTitle,$lesContent,$lesDuration,$orderNum,$lesPreview,$lesVideoUrl ?: null]);
        // Update lesson count
        $db->prepare("UPDATE courses SET total_lessons=(SELECT COUNT(*) FROM lessons WHERE course_id=?) WHERE id=?")
           ->execute([$courseId,$courseId]);
        $lessonMsg = ['type'=>'success','text'=>'Lesson added successfully!'];
    }
}

// Handle signature upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_signature'])) {
    $sigUrl = trim($_POST['signature_url'] ?? '');
    if ($sigUrl) {
        $db->prepare("UPDATE users SET signature_image=? WHERE id=?")
           ->execute([$sigUrl, $uid]);
        $sigMsg = ['type'=>'success','text'=>'Signature saved successfully!'];
        // Refresh user
        $user = getCurrentUser();
    } else {
        $sigMsg = ['type'=>'error','text'=>'No signature image provided.'];
    }
}

// Teacher's courses
$courses = $db->prepare("
    SELECT co.*, cat.name as category_name,
           COUNT(DISTINCT e.id) as enrollment_count,
           COUNT(DISTINCT r.id) as review_count,
           AVG(r.rating) as avg_rating,
           COUNT(DISTINCT l.id) as lesson_count
    FROM courses co
    LEFT JOIN categories cat ON cat.id=co.category_id
    LEFT JOIN enrollments e ON e.course_id=co.id
    LEFT JOIN reviews r ON r.course_id=co.id
    LEFT JOIN lessons l ON l.course_id=co.id
    WHERE co.instructor_id=?
    GROUP BY co.id ORDER BY co.created_at DESC
");
$courses->execute([$uid]);
$courses = $courses->fetchAll();

// Stats
$totalStudents  = $db->prepare("SELECT COUNT(DISTINCT e.user_id) FROM enrollments e JOIN courses co ON co.id=e.course_id WHERE co.instructor_id=?"); $totalStudents->execute([$uid]); $totalStudents=$totalStudents->fetchColumn();
$totalCourses   = count($courses);
$approvedCourses= count(array_filter($courses, fn($c)=>$c['status']==='approved'));
$totalRevenue   = $db->prepare("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN courses co ON co.id=p.course_id WHERE co.instructor_id=? AND p.status='completed'"); $totalRevenue->execute([$uid]); $totalRevenue=$totalRevenue->fetchColumn();

// Available categories
$allCats = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$pageTitle = "Teacher Dashboard";
$teacherFooter = true;

// Stats for teacher footer
$footerAvgRating   = $db->prepare("SELECT ROUND(AVG(r.rating),1) FROM reviews r JOIN courses co ON co.id=r.course_id WHERE co.instructor_id=?");
$footerAvgRating->execute([$uid]);
$footerAvgRating = $footerAvgRating->fetchColumn() ?: '—';

$footerCertCount   = $db->prepare("SELECT COUNT(*) FROM certificates cert JOIN courses co ON co.id=cert.course_id WHERE co.instructor_id=?");
$footerCertCount->execute([$uid]);
$footerCertCount = (int)$footerCertCount->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?php echo strtoupper(substr($user['name'],0,1)); ?></div>
            <div class="sidebar-name"><?php echo e($user['name']); ?></div>
            <div class="sidebar-email"><?php echo e($user['email']); ?></div>
            <span class="role-badge role-teacher mt-2">Teacher</span>
        </div>
        <nav class="sidebar-nav">
            <a href="?tab=overview"    class="sidebar-link <?php echo $tab==='overview'?'active':''; ?>"><i class="fas fa-chart-pie"></i> Overview</a>
            <a href="?tab=courses"     class="sidebar-link <?php echo $tab==='courses'?'active':''; ?>"><i class="fas fa-book"></i> My Courses <span class="sidebar-link-badge"><?php echo $totalCourses; ?></span></a>
            <a href="?tab=create"      class="sidebar-link <?php echo $tab==='create'?'active':''; ?>"><i class="fas fa-plus-circle"></i> Create Course</a>
            <a href="?tab=lessons"     class="sidebar-link <?php echo $tab==='lessons'?'active':''; ?>"><i class="fas fa-film"></i> Add Lessons</a>
            <a href="?tab=students"    class="sidebar-link <?php echo $tab==='students'?'active':''; ?>"><i class="fas fa-users"></i> Students</a>
            <a href="?tab=earnings"    class="sidebar-link <?php echo $tab==='earnings'?'active':''; ?>"><i class="fas fa-rupee-sign"></i> Earnings</a>
            <a href="?tab=profile"    class="sidebar-link <?php echo $tab==='profile'?'active':''; ?>"><i class="fas fa-signature"></i> Signature</a>
            <div style="height:1px;background:var(--border);margin:12px 0;"></div>
            <a href="<?php echo BASE_URL; ?>/courses/index.php"    class="sidebar-link"><i class="fas fa-compass"></i> Browse Courses</a>
            <a href="<?php echo BASE_URL; ?>/logout.php"           class="sidebar-link" style="color:#f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="dashboard-content">

        <!-- OVERVIEW -->
        <?php if ($tab === 'overview'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.6rem;font-weight:900;margin-bottom:6px;">Teacher Dashboard</h1>
            <p style="color:var(--text-muted);font-size:14px;margin-bottom:28px;">Track your courses, students, and earnings.</p>

            <div class="row g-3 mb-4">
                <?php foreach ([
                    ['fas fa-book','val'=>$totalCourses,'label'=>'Total Courses','color'=>'#22c55e'],
                    ['fas fa-check-circle','val'=>$approvedCourses,'label'=>'Approved','color'=>'#3b82f6'],
                    ['fas fa-users','val'=>$totalStudents,'label'=>'Total Students','color'=>'#8b5cf6'],
                    ['fas fa-rupee-sign','val'=>'NPR '.number_format($totalRevenue,0),'label'=>'Total Revenue','color'=>'#f59e0b'],
                ] as $sc): ?>
                <div class="col-6 col-xl-3">
                    <div class="stat-card" style="--stat-color:<?php echo $sc['color']; ?>;">
                        <div class="stat-icon" style="background:<?php echo $sc['color']; ?>1a;"><i class="<?php echo $sc[0]; ?>" style="color:<?php echo $sc['color']; ?>;font-size:20px;"></i></div>
                        <div class="stat-value" style="font-size:26px;color:<?php echo $sc['color']; ?>;"><?php echo $sc['val']; ?></div>
                        <div class="stat-label"><?php echo $sc['label']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Course list overview -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
                <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <h3 style="font-size:16px;font-weight:700;margin:0;">Your Courses</h3>
                    <a href="?tab=create" class="btn-enroll-sm"><i class="fas fa-plus"></i> New Course</a>
                </div>
                <?php if (empty($courses)): ?>
                <div style="text-align:center;padding:48px;color:var(--text-muted);">
                    <i class="fas fa-book-open" style="font-size:36px;margin-bottom:12px;display:block;"></i>
                    No courses yet. <a href="?tab=create" style="color:var(--primary);">Create your first course</a>
                </div>
                <?php else: ?>
                <table class="table table-dark-custom mb-0">
                    <thead><tr><th style="padding:12px 20px;font-size:13px;color:white;">Course</th><th style="padding:12px;font-size:13px;color:white;">Students</th><th style="padding:12px;font-size:13px;color:white;">Rating</th><th style="padding:12px;font-size:13px;color:white;">Status</th><th style="padding:12px;font-size:13px;color:white;">Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($courses as $c): ?>
                    <tr style="border-color:var(--border);">
                        <td style="padding:14px 20px;">
                            <div style="font-weight:700;font-size:14px;color:#fff;"><?php echo e(truncate($c['title'],40)); ?></div>
                            <div style="font-size:12px;color:var(--text-muted);"><?php echo e($c['category_name']); ?> · <?php echo $c['lesson_count']; ?> lessons</div>
                        </td>
                        <td style="padding:14px 12px;font-size:14px;font-weight:600;color:#fff;"><?php echo $c['enrollment_count']; ?></td>
                        <td style="padding:14px 12px;font-size:14px;color:#fbbf24;font-weight:700;"><?php echo number_format($c['avg_rating']??0,1); ?> ★</td>
                        <td style="padding:14px 12px;">
                            <span style="padding:3px 10px;border-radius:50px;font-size:11px;font-weight:700;background:<?php echo match($c['status']){
                                'approved'=>'rgba(34,197,94,0.1)','pending'=>'rgba(245,158,11,0.1)','rejected'=>'rgba(239,68,68,0.1)',default=>'rgba(107,114,128,0.1)'};?>;color:<?php echo match($c['status']){'approved'=>'#4ade80','pending'=>'#fbbf24','rejected'=>'#f87171',default=>'#9ca3af'};?>;">
                                <?php echo ucfirst($c['status']); ?>
                            </span>
                        </td>
                        <td style="padding:14px 12px;">
                            <a href="<?php echo BASE_URL; ?>/courses/<?php echo e($c['slug']); ?>" style="font-size:13px;color:var(--primary);">View →</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- MY COURSES TAB -->
        <?php elseif ($tab === 'courses'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:6px;">My Courses</h1>
            <p style="color:var(--text-muted);font-size:14px;margin-bottom:24px;">All courses you have created — <?php echo count($courses); ?> total.</p>

            <?php if (empty($courses)): ?>
            <div style="text-align:center;padding:60px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);color:var(--text-muted);">
                <i class="fas fa-book-open" style="font-size:48px;margin-bottom:16px;display:block;color:var(--primary);"></i>
                <h3 style="margin-bottom:8px;">No courses yet</h3>
                <p style="margin-bottom:20px;">Create your first course and share your knowledge.</p>
                <a href="?tab=create" class="btn-primary-custom">+ Create Course</a>
            </div>
            <?php else: ?>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
                <table class="table table-dark-custom mb-0">
                    <thead>
                        <tr>
                            <th style="padding:14px 20px;color:#fff;font-size:13px;">Course</th>
                            <th style="padding:14px;color:#fff;font-size:13px;">Category</th>
                            <th style="padding:14px;color:#fff;font-size:13px;">Price</th>
                            <th style="padding:14px;color:#fff;font-size:13px;">Students</th>
                            <th style="padding:14px;color:#fff;font-size:13px;">Rating</th>
                            <th style="padding:14px;color:#fff;font-size:13px;">Status</th>
                            <th style="padding:14px;color:#fff;font-size:13px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($courses as $c): ?>
                    <tr style="border-color:var(--border);">
                        <td style="padding:14px 20px;">
                            <div style="font-weight:700;font-size:14px;color:#fff;"><?php echo e(truncate($c['title'],38)); ?></div>
                            <div style="font-size:11px;color:var(--text-muted);"><?php echo ucfirst($c['level']); ?> · <?php echo $c['lesson_count']; ?> lessons · <?php echo e($c['duration']); ?></div>
                        </td>
                        <td style="padding:14px;font-size:13px;color:#fff;"><?php echo e($c['category_name']); ?></td>
                        <td style="padding:14px;">
                            <span style="font-weight:700;color:<?php echo $c['price']==0?'#4ade80':'#a78bfa'; ?>;">
                                <?php echo $c['price']==0 ? 'FREE' : 'NPR '.number_format($c['price'],0); ?>
                            </span>
                        </td>
                        <td style="padding:14px;font-size:14px;font-weight:700;color:#fff;"><?php echo $c['enrollment_count']; ?></td>
                        <td style="padding:14px;font-size:13px;color:#fbbf24;font-weight:700;"><?php echo number_format($c['avg_rating']??0,1); ?> ★</td>
                        <td style="padding:14px;">
                            <span style="padding:3px 10px;border-radius:50px;font-size:11px;font-weight:700;
                                background:<?php echo match($c['status']){'approved'=>'rgba(34,197,94,0.1)','pending'=>'rgba(245,158,11,0.1)','rejected'=>'rgba(239,68,68,0.1)',default=>'rgba(107,114,128,0.1)'}; ?>;
                                color:<?php echo match($c['status']){'approved'=>'#4ade80','pending'=>'#fbbf24','rejected'=>'#f87171',default=>'#9ca3af'}; ?>;">
                                <?php echo ucfirst($c['status']); ?>
                            </span>
                        </td>
                        <td style="padding:14px;">
                            <div style="display:flex;gap:8px;align-items:center;">
                                <a href="<?php echo BASE_URL; ?>/courses/<?php echo e($c['slug']); ?>" style="font-size:12px;color:var(--primary);padding:4px 10px;border:1px solid rgba(34,197,94,0.3);border-radius:6px;">View</a>
                                <a href="?tab=edit&id=<?php echo $c['id']; ?>" style="font-size:12px;color:#fbbf24;padding:4px 10px;border:1px solid rgba(251,191,36,0.3);border-radius:6px;"><i class="fas fa-edit"></i> Edit</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <!-- EDIT COURSE TAB -->
        <?php elseif ($tab === 'edit'):
            $editId     = (int)($_GET['id'] ?? 0);
            $editCourse = null;
            if ($editId) {
                $ec = $db->prepare("SELECT * FROM courses WHERE id=? AND instructor_id=?");
                $ec->execute([$editId, $uid]);
                $editCourse = $ec->fetch();
            }
            if (!$editCourse): ?>
            <div style="text-align:center;padding:60px;color:var(--text-muted);">
                <i class="fas fa-exclamation-circle" style="font-size:36px;display:block;margin-bottom:12px;color:#f87171;"></i>
                Course not found or you don't have permission.
                <br><a href="?tab=courses" style="color:var(--primary);margin-top:12px;display:inline-block;">← Back to My Courses</a>
            </div>
            <?php else: ?>
        <div class="animate-fade-up">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
                <a href="?tab=courses" style="color:var(--text-muted);font-size:13px;display:flex;align-items:center;gap:6px;"><i class="fas fa-arrow-left"></i> My Courses</a>
                <span style="color:var(--border);">›</span>
                <h1 style="font-size:1.4rem;font-weight:900;margin:0;">Edit Course</h1>
            </div>

            <?php if (isset($editMsg)): ?>
                <div class="alert-custom alert-<?php echo $editMsg['type']; ?> mb-4">
                    <i class="fas fa-<?php echo $editMsg['type']==='success'?'check':'times'; ?>-circle"></i> <?php echo $editMsg['text']; ?>
                </div>
            <?php endif; ?>

            <div class="form-card" style="max-width:780px;">
                <form method="POST">
                    <input type="hidden" name="edit_course" value="1">
                    <input type="hidden" name="course_id" value="<?php echo $editCourse['id']; ?>">
                    <div class="row g-3">

                        <!-- Thumbnail preview + upload -->
                        <div class="col-12">
                            <label class="form-label-custom">Course Thumbnail</label>
                            <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
                                <!-- Current thumbnail -->
                                <div style="flex-shrink:0;">
                                    <div id="editThumbPreviewWrap" style="width:180px;height:110px;background:linear-gradient(135deg,#0d1a2e,#0a2010);border-radius:10px;overflow:hidden;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:40px;">
                                        <?php if (!empty($editCourse['thumbnail'])): ?>
                                            <img id="editThumbImg" src="<?php echo e($editCourse['thumbnail']); ?>" style="width:100%;height:100%;object-fit:cover;">
                                        <?php else: ?>
                                            <span id="editThumbPlaceholderIcon">📚</span>
                                            <img id="editThumbImg" src="" style="display:none;width:100%;height:100%;object-fit:cover;">
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:6px;text-align:center;">Current thumbnail</div>
                                </div>
                                <!-- Upload new -->
                                <div style="flex:1;min-width:200px;">
                                    <div id="editThumbBox" onclick="document.getElementById('editThumbFile').click()"
                                        style="border:2px dashed rgba(34,197,94,0.3);border-radius:10px;padding:20px;text-align:center;cursor:pointer;background:rgba(34,197,94,0.03);"
                                        onmouseover="this.style.borderColor='rgba(34,197,94,0.6)'" onmouseout="this.style.borderColor='rgba(34,197,94,0.3)'">
                                        <div id="editThumbBoxContent">
                                            <i class="fas fa-cloud-upload-alt" style="font-size:24px;color:var(--primary);margin-bottom:8px;display:block;"></i>
                                            <div style="font-size:13px;font-weight:600;color:#fff;">Click to upload new thumbnail</div>
                                            <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">JPG, PNG, WEBP — Max 5MB</div>
                                        </div>
                                        <div id="editThumbUploading" style="display:none;">
                                            <i class="fas fa-spinner fa-spin" style="font-size:20px;color:var(--primary);display:block;margin-bottom:6px;"></i>
                                            <div style="font-size:12px;color:var(--text-muted);">Uploading...</div>
                                        </div>
                                    </div>
                                    <input type="file" id="editThumbFile" accept="image/*" style="display:none;" onchange="uploadEditThumbnail(this)">
                                    <input type="hidden" name="thumbnail" id="editThumbUrl" value="">
                                    <div id="editThumbStatus" style="font-size:12px;margin-top:6px;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Title -->
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label-custom">Course Title *</label>
                                <div class="input-wrapper"><i class="fas fa-heading input-icon"></i>
                                    <input type="text" name="title" class="form-input-custom" value="<?php echo e($editCourse['title']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Category + Level -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Category *</label>
                                <div class="input-wrapper"><i class="fas fa-tag input-icon"></i>
                                    <select name="category_id" class="form-input-custom" required>
                                        <?php foreach ($allCats as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" style="background:#1a2332;color:#fff;"
                                                <?php echo $cat['id'] == $editCourse['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo e($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Level</label>
                                <div class="input-wrapper"><i class="fas fa-signal input-icon"></i>
                                    <select name="level" class="form-input-custom">
                                        <?php foreach (['beginner','intermediate','advanced'] as $lv): ?>
                                            <option value="<?php echo $lv; ?>" style="background:#1a2332;color:#fff;"
                                                <?php echo $editCourse['level'] === $lv ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($lv); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Price + Duration -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Price (NPR) — 0 for Free</label>
                                <div class="input-wrapper"><i class="fas fa-rupee-sign input-icon"></i>
                                    <input type="number" name="price" class="form-input-custom" value="<?php echo $editCourse['price']; ?>" min="0" step="100">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Total Duration</label>
                                <div class="input-wrapper"><i class="fas fa-clock input-icon"></i>
                                    <input type="text" name="duration" class="form-input-custom" value="<?php echo e($editCourse['duration']); ?>" placeholder="e.g. 12 hours">
                                </div>
                            </div>
                        </div>

                        <!-- Short Description -->
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label-custom">Short Description</label>
                                <textarea name="short_description" class="form-input-custom" rows="2" style="padding:13px 16px;resize:vertical;"><?php echo e($editCourse['short_description']); ?></textarea>
                            </div>
                        </div>

                        <!-- Full Description -->
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label-custom">Full Description *</label>
                                <textarea name="description" class="form-input-custom" rows="7" style="padding:13px 16px;resize:vertical;" required><?php echo e($editCourse['description']); ?></textarea>
                            </div>
                        </div>

                        <!-- Status notice -->
                        <div class="col-12">
                            <?php if ($editCourse['status'] === 'approved'): ?>
                            <div style="background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.2);border-radius:10px;padding:14px;font-size:13px;color:var(--text-secondary);margin-bottom:8px;">
                                <i class="fas fa-info-circle me-1" style="color:#f59e0b;"></i>
                                This course is <strong>approved and live</strong>. Saving changes will keep it live without requiring re-approval.
                            </div>
                            <?php else: ?>
                            <div style="background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.2);border-radius:10px;padding:14px;font-size:13px;color:var(--text-secondary);margin-bottom:8px;">
                                <i class="fas fa-info-circle me-1" style="color:#3b82f6;"></i>
                                Status: <strong style="color:#fbbf24;"><?php echo ucfirst($editCourse['status']); ?></strong>. Changes will be saved without changing the current status.
                            </div>
                            <?php endif; ?>
                            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                                <button type="submit" class="btn-primary-custom" style="flex:1;justify-content:center;padding:14px;font-size:15px;">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <a href="?tab=courses" class="btn-outline-custom" style="padding:14px 20px;">
                                    Cancel
                                </a>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <script>
        function uploadEditThumbnail(input) {
            if (!input.files || !input.files[0]) return;
            const formData = new FormData();
            formData.append('image', input.files[0]);

            document.getElementById('editThumbBoxContent').style.display = 'none';
            document.getElementById('editThumbUploading').style.display  = 'block';
            document.getElementById('editThumbStatus').innerHTML          = '';

            fetch('<?php echo BASE_URL; ?>/api/upload_image.php', { method:'POST', body:formData })
            .then(r => r.json())
            .then(data => {
                document.getElementById('editThumbUploading').style.display  = 'none';
                document.getElementById('editThumbBoxContent').style.display = 'block';
                if (data.success) {
                    document.getElementById('editThumbUrl').value         = data.url;
                    document.getElementById('editThumbImg').src           = data.url;
                    document.getElementById('editThumbImg').style.display = 'block';
                    if (document.getElementById('editThumbPlaceholderIcon'))
                        document.getElementById('editThumbPlaceholderIcon').style.display = 'none';
                    document.getElementById('editThumbStatus').innerHTML  = '<span style="color:#4ade80;"><i class="fas fa-check-circle"></i> New thumbnail uploaded!</span>';
                } else {
                    document.getElementById('editThumbStatus').innerHTML  = '<span style="color:#f87171;">❌ ' + data.error + '</span>';
                }
            })
            .catch(() => {
                document.getElementById('editThumbUploading').style.display  = 'none';
                document.getElementById('editThumbBoxContent').style.display = 'block';
                document.getElementById('editThumbStatus').innerHTML         = '<span style="color:#f87171;">❌ Upload failed.</span>';
            });
        }
        </script>
        <?php endif; ?>

        <?php elseif ($tab === 'create'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;"><i class="fas fa-plus-circle text-gradient me-2"></i>Create New Course</h1>
            <?php if (isset($courseMsg)): ?>
                <div class="alert-custom alert-<?php echo $courseMsg['type']; ?> mb-4"><i class="fas fa-<?php echo $courseMsg['type']==='success'?'check':'times'; ?>-circle"></i> <?php echo $courseMsg['text']; ?></div>
            <?php endif; ?>
            <div class="form-card" style="max-width:700px;">
                <form method="POST">
                    <input type="hidden" name="create_course" value="1">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label-custom">Course Title *</label>
                                <div class="input-wrapper"><i class="fas fa-heading input-icon"></i>
                                    <input type="text" name="title" class="form-input-custom" placeholder="e.g. Complete React.js Guide 2024" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Category *</label>
                                <div class="input-wrapper"><i class="fas fa-tag input-icon"></i>
                                    <select name="category_id" class="form-input-custom" required>
                                        <option value="" style="background:#fff;color:#111;">Select category</option>
                                        <?php foreach ($allCats as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" style="background:#fff;color:#111;"><?php echo e($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Level</label>
                                <div class="input-wrapper"><i class="fas fa-signal input-icon"></i>
                                    <select name="level" class="form-input-custom">
                                        <option value="beginner" style="background:#fff;color:#111;">Beginner</option>
                                        <option value="intermediate" style="background:#fff;color:#111;">Intermediate</option>
                                        <option value="advanced" style="background:#fff;color:#111;">Advanced</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Price (NPR) — Leave 0 for Free</label>
                                <div class="input-wrapper"><i class="fas fa-rupee-sign input-icon"></i>
                                    <input type="number" name="price" class="form-input-custom" placeholder="0" min="0" step="100" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Total Duration</label>
                                <div class="input-wrapper"><i class="fas fa-clock input-icon"></i>
                                    <input type="text" name="duration" class="form-input-custom" placeholder="e.g. 12 hours">
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label-custom">Short Description (for cards)</label>
                                <textarea name="short_description" class="form-input-custom" rows="2" placeholder="A compelling one-liner for the course card..." style="padding:13px 16px;resize:vertical;"></textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label-custom">Full Description *</label>
                                <textarea name="description" class="form-input-custom" rows="6" placeholder="Detailed description of what students will learn, prerequisites, and course content..." style="padding:13px 16px;resize:vertical;" required></textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label-custom">Course Thumbnail Image</label>
                                <!-- Image Upload Box -->
                                <div id="thumbUploadBox" onclick="document.getElementById('thumbFile').click()"
                                    style="border:2px dashed rgba(34,197,94,0.3);border-radius:12px;padding:28px;text-align:center;cursor:pointer;background:rgba(34,197,94,0.03);transition:all 0.2s;position:relative;"
                                    onmouseover="this.style.borderColor='rgba(34,197,94,0.6)'" onmouseout="this.style.borderColor='rgba(34,197,94,0.3)'">
                                    <div id="thumbPreview" style="display:none;margin-bottom:12px;">
                                        <img id="thumbImg" src="" style="max-height:160px;border-radius:8px;max-width:100%;">
                                    </div>
                                    <div id="thumbPlaceholder">
                                        <i class="fas fa-image" style="font-size:32px;color:var(--primary);margin-bottom:10px;display:block;"></i>
                                        <div style="font-size:14px;font-weight:600;color:#fff;margin-bottom:4px;">Click to upload thumbnail</div>
                                        <div style="font-size:12px;color:var(--text-muted);">JPG, PNG, WEBP — Max 5MB</div>
                                    </div>
                                    <div id="thumbUploading" style="display:none;">
                                        <i class="fas fa-spinner fa-spin" style="font-size:24px;color:var(--primary);margin-bottom:8px;display:block;"></i>
                                        <div style="font-size:13px;color:var(--text-muted);">Uploading to ImgBB...</div>
                                    </div>
                                </div>
                                <input type="file" id="thumbFile" accept="image/*" style="display:none;" onchange="uploadThumbnail(this)">
                                <input type="hidden" name="thumbnail" id="thumbUrl">
                                <div id="thumbError" style="color:#f87171;font-size:12px;margin-top:6px;display:none;"></div>
                                <div id="thumbSuccess" style="color:#4ade80;font-size:12px;margin-top:6px;display:none;"><i class="fas fa-check-circle"></i> Image uploaded successfully!</div>
                            </div>
                        </div>
                            <div style="background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.2);border-radius:10px;padding:14px;font-size:13px;color:var(--text-secondary);margin-bottom:8px;">
                                <i class="fas fa-info-circle me-1" style="color:#f59e0b;"></i>
                                Your course will be submitted for <strong>admin review</strong>. Once approved, it will go live on the platform.
                            </div>
                            <button type="submit" class="btn-primary-custom" style="width:100%;justify-content:center;padding:14px;font-size:16px;">
                                <i class="fas fa-paper-plane"></i> Submit Course for Review
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function uploadThumbnail(input) {
            if (!input.files || !input.files[0]) return;
            const formData = new FormData();
            formData.append('image', input.files[0]);

            document.getElementById('thumbPlaceholder').style.display  = 'none';
            document.getElementById('thumbPreview').style.display       = 'none';
            document.getElementById('thumbUploading').style.display     = 'block';
            document.getElementById('thumbError').style.display         = 'none';
            document.getElementById('thumbSuccess').style.display       = 'none';

            fetch('<?php echo BASE_URL; ?>/api/upload_image.php', { method:'POST', body:formData })
            .then(r => r.json())
            .then(data => {
                document.getElementById('thumbUploading').style.display = 'none';
                if (data.success) {
                    document.getElementById('thumbUrl').value                  = data.url;
                    document.getElementById('thumbImg').src                    = data.url;
                    document.getElementById('thumbPreview').style.display      = 'block';
                    document.getElementById('thumbPlaceholder').style.display  = 'none';
                    document.getElementById('thumbSuccess').style.display      = 'block';
                } else {
                    document.getElementById('thumbPlaceholder').style.display  = 'block';
                    document.getElementById('thumbError').textContent          = '❌ ' + data.error;
                    document.getElementById('thumbError').style.display        = 'block';
                }
            })
            .catch(() => {
                document.getElementById('thumbUploading').style.display        = 'none';
                document.getElementById('thumbPlaceholder').style.display      = 'block';
                document.getElementById('thumbError').textContent              = '❌ Upload failed. Try again.';
                document.getElementById('thumbError').style.display            = 'block';
            });
        }
        </script>

        <!-- ADD LESSONS -->
        <?php elseif ($tab === 'lessons'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;"><i class="fas fa-film me-2 text-gradient"></i>Add Lessons</h1>
            <?php if (isset($lessonMsg)): ?>
                <div class="alert-custom alert-<?php echo $lessonMsg['type']; ?> mb-4"><i class="fas fa-check-circle"></i> <?php echo $lessonMsg['text']; ?></div>
            <?php endif; ?>
            <?php $myCourses = array_filter($courses, fn($c)=>$c['status']==='approved'); ?>
            <?php if (empty($myCourses)): ?>
            <div class="alert-custom alert-info"><i class="fas fa-info-circle"></i> You need at least one <strong>approved</strong> course to add lessons. Create a course first and wait for admin approval.</div>
            <?php else: ?>
            <div class="form-card" style="max-width:700px;">
                <form method="POST">
                    <input type="hidden" name="add_lesson" value="1">
                    <div class="form-group">
                        <label class="form-label-custom">Select Course *</label>
                        <div class="input-wrapper"><i class="fas fa-book input-icon"></i>
                            <select name="lesson_course_id" class="form-input-custom" required>
                                <option value="" style="background:#fff;color:#111;">Choose course</option>
                                <?php foreach ($myCourses as $mc): ?>
                                    <option value="<?php echo $mc['id']; ?>" style="background:#fff;color:#111;"><?php echo e($mc['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label-custom">Lesson Title *</label>
                        <div class="input-wrapper"><i class="fas fa-heading input-icon"></i>
                            <input type="text" name="lesson_title" class="form-input-custom" placeholder="e.g. Introduction to Variables" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Duration (minutes)</label>
                                <div class="input-wrapper"><i class="fas fa-clock input-icon"></i>
                                    <input type="number" name="lesson_duration" class="form-input-custom" value="20" min="1" max="300">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px;color:var(--text-secondary);margin-top:8px;">
                                <input type="checkbox" name="is_free_preview" style="accent-color:var(--primary);width:18px;height:18px;">
                                Mark as free preview
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label-custom">Lesson Content *</label>
                        <textarea name="lesson_content" class="form-input-custom" rows="10" placeholder="Write the lesson content here. Supports plain text. Use line breaks to separate paragraphs..." style="padding:14px;resize:vertical;" required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label-custom">Video URL (optional)</label>
                        <div class="input-wrapper"><i class="fas fa-video input-icon"></i>
                            <input type="url" name="video_url" class="form-input-custom" placeholder="https://youtube.com/embed/...">
                        </div>
                    </div>
                    <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="padding:14px;font-size:15px;">
                        <i class="fas fa-plus"></i> Add Lesson
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- STUDENTS -->
        <?php elseif ($tab === 'students'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;">My Students</h1>
            <?php
            $students = $db->prepare("
                SELECT u.name, u.email, co.title as course_title, e.progress, e.enrolled_at, e.status
                FROM enrollments e
                JOIN users u ON u.id=e.user_id
                JOIN courses co ON co.id=e.course_id
                WHERE co.instructor_id=?
                ORDER BY e.enrolled_at DESC
            ");
            $students->execute([$uid]);
            $students = $students->fetchAll();
            ?>
            <?php if (empty($students)): ?>
                <div style="text-align:center;padding:60px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);color:var(--text-muted);">
                    <i class="fas fa-users" style="font-size:36px;margin-bottom:12px;display:block;"></i>
                    No students enrolled in your courses yet.
                </div>
            <?php else: ?>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
                <table class="table table-dark-custom mb-0">
                    <thead><tr>
                        <th style="padding:14px 20px;font-size:13px;color:white;">Student</th>
                        <th style="padding:14px;font-size:13px;color:white;">Course</th>
                        <th style="padding:14px;font-size:13px;color:white;">Progress</th>
                        <th style="padding:14px;font-size:13px;color:white;">Enrolled</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($students as $s): ?>
                    <tr style="border-color:var(--border);">
                        <td style="padding:14px 20px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0;"><?php echo strtoupper(substr($s['name'],0,1)); ?></div>
                                <div>
                                    <div style="font-size:14px;font-weight:600;color:#fff;"><?php echo e($s['name']); ?></div>
                                    <div style="font-size:12px;color:#fff;"><?php echo e($s['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:14px;font-size:13px;color:#fff;"><?php echo e(truncate($s['course_title'],30)); ?></td>
                        <td style="padding:14px;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="progress-bar-custom" style="width:80px;margin:0;"><div class="progress-fill" style="width:<?php echo $s['progress']; ?>%;"></div></div>
                                <span style="font-size:12px;color:var(--primary);font-weight:700;"><?php echo $s['progress']; ?>%</span>
                            </div>
                        </td>
                        <td style="padding:14px;font-size:12px;color:#fff;"><?php echo date('M d, Y', strtotime($s['enrolled_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- EARNINGS -->
        <?php elseif ($tab === 'earnings'): ?>
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;">Earnings Overview</h1>
            <?php
            $payments = $db->prepare("
                SELECT p.*, u.name as student_name, co.title as course_title
                FROM payments p JOIN users u ON u.id=p.user_id JOIN courses co ON co.id=p.course_id
                WHERE co.instructor_id=? AND p.status='completed' ORDER BY p.paid_at DESC
            ");
            $payments->execute([$uid]);
            $payments = $payments->fetchAll();
            ?>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card" style="--stat-color:#22c55e;">
                        <div class="stat-icon" style="background:rgba(34,197,94,0.1);"><i class="fas fa-rupee-sign" style="color:#22c55e;font-size:20px;"></i></div>
                        <div class="stat-value" style="font-size:26px;color:#22c55e;">NPR <?php echo number_format($totalRevenue,0); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="--stat-color:#3b82f6;">
                        <div class="stat-icon" style="background:rgba(59,130,246,0.1);"><i class="fas fa-shopping-cart" style="color:#3b82f6;font-size:20px;"></i></div>
                        <div class="stat-value" style="font-size:26px;color:#3b82f6;"><?php echo count($payments); ?></div>
                        <div class="stat-label">Successful Payments</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="--stat-color:#f59e0b;">
                        <div class="stat-icon" style="background:rgba(245,158,11,0.1);"><i class="fas fa-chart-line" style="color:#f59e0b;font-size:20px;"></i></div>
                        <div class="stat-value" style="font-size:26px;color:#f59e0b;">NPR <?php echo count($payments)>0?number_format($totalRevenue/count($payments),0):0; ?></div>
                        <div class="stat-label">Avg per Sale</div>
                    </div>
                </div>
            </div>
            <?php if (!empty($payments)): ?>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
                <table class="table table-dark-custom mb-0">
                    <thead><tr><th style="padding:14px 20px;font-size:13px;color:white;">Student</th><th style="padding:14px;font-size:13px;color:white;">Course</th><th style="padding:14px;font-size:13px;color:white;">Amount</th><th style="padding:14px;font-size:13px;color:white;">Method</th><th style="padding:14px;font-size:13px;color:white;">Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr style="border-color:var(--border);">
                        <td style="padding:14px 20px;font-size:14px;font-weight:600;color:#fff;"><?php echo e($p['student_name']); ?></td>
                        <td style="padding:14px;font-size:13px;color:#fff;"><?php echo e(truncate($p['course_title'],28)); ?></td>
                        <td style="padding:14px;font-weight:700;color:#22c55e;">NPR <?php echo number_format($p['amount'],0); ?></td>
                        <td style="padding:14px;font-size:12px;text-transform:uppercase;color:#fff;"><?php echo e($p['method']); ?></td>
                        <td style="padding:14px;font-size:12px;color:#fff;"><?php echo date('M d, Y', strtotime($p['paid_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div style="text-align:center;padding:48px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);color:var(--text-muted);">No revenue transactions yet.</div>
            <?php endif; ?>
        </div>
        <?php elseif ($tab === 'profile'): ?>
        <!-- SIGNATURE TAB -->
        <div class="animate-fade-up">
            <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:6px;"><i class="fas fa-signature me-2 text-gradient"></i>My Signature</h1>
            <p style="color:var(--text-muted);font-size:14px;margin-bottom:28px;">Upload your signature image. It will appear on all certificates issued for your courses.</p>

            <?php if (isset($sigMsg)): ?>
            <div class="alert-custom alert-<?php echo $sigMsg['type']; ?> mb-4">
                <i class="fas fa-<?php echo $sigMsg['type']==='success'?'check':'times'; ?>-circle"></i> <?php echo $sigMsg['text']; ?>
            </div>
            <?php endif; ?>

            <div class="form-card" style="max-width:600px;">

                <!-- Current signature preview -->
                <?php $currentSig = $user['signature_image'] ?? ''; ?>
                <div style="margin-bottom:28px;">
                    <label class="form-label-custom">Current Signature</label>
                    <div id="currentSigWrap" style="min-height:90px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:12px;padding:20px;display:flex;align-items:center;justify-content:center;">
                        <?php if ($currentSig): ?>
                            <img id="currentSigImg" src="<?php echo e($currentSig); ?>" alt="Signature"
                                 style="max-height:80px;max-width:100%;object-fit:contain;filter:drop-shadow(0 0 6px rgba(34,197,94,0.2));">
                        <?php else: ?>
                            <div id="noSigMsg" style="color:var(--text-muted);font-size:13px;text-align:center;">
                                <i class="fas fa-signature" style="font-size:28px;margin-bottom:8px;display:block;opacity:0.4;"></i>
                                No signature uploaded yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upload new signature -->
                <form method="POST">
                    <input type="hidden" name="save_signature" value="1">
                    <input type="hidden" name="signature_url" id="sigUrlInput" value="">

                    <label class="form-label-custom">Upload New Signature</label>
                    <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">
                        <i class="fas fa-lightbulb" style="color:#fbbf24;"></i>
                        Sign on white paper, photograph or scan it, then upload. PNG with transparent background works best.
                    </p>

                    <!-- Drop zone -->
                    <div id="sigDropZone" onclick="document.getElementById('sigFileInput').click()"
                         style="border:2px dashed rgba(34,197,94,0.3);border-radius:12px;padding:32px;text-align:center;cursor:pointer;background:rgba(34,197,94,0.03);transition:all 0.2s;margin-bottom:16px;"
                         onmouseover="this.style.borderColor='rgba(34,197,94,0.6)';this.style.background='rgba(34,197,94,0.06)'"
                         onmouseout="this.style.borderColor='rgba(34,197,94,0.3)';this.style.background='rgba(34,197,94,0.03)'">

                        <div id="sigDropContent">
                            <i class="fas fa-signature" style="font-size:32px;color:var(--primary);margin-bottom:10px;display:block;"></i>
                            <div style="font-size:14px;font-weight:600;color:#fff;margin-bottom:4px;">Click to upload signature image</div>
                            <div style="font-size:12px;color:var(--text-muted);">PNG (transparent), JPG, WEBP — Max 5MB</div>
                        </div>

                        <div id="sigUploading" style="display:none;">
                            <i class="fas fa-spinner fa-spin" style="font-size:28px;color:var(--primary);margin-bottom:8px;display:block;"></i>
                            <div style="font-size:13px;color:var(--text-muted);">Uploading...</div>
                        </div>

                        <div id="sigPreviewWrap" style="display:none;">
                            <img id="sigPreviewImg" src="" alt="Preview"
                                 style="max-height:80px;max-width:280px;object-fit:contain;border-radius:8px;margin-bottom:8px;">
                            <div style="font-size:12px;color:#4ade80;"><i class="fas fa-check-circle"></i> Signature ready — click Save below</div>
                        </div>
                    </div>

                    <input type="file" id="sigFileInput" accept="image/*" style="display:none;" onchange="uploadSignature(this)">
                    <div id="sigError" style="color:#f87171;font-size:12px;margin-bottom:12px;display:none;"></div>

                    <button type="submit" id="sigSaveBtn" class="btn-primary-custom w-100 justify-content-center" style="padding:13px;font-size:15px;" disabled>
                        <i class="fas fa-save"></i> Save Signature
                    </button>
                </form>

                <!-- Remove signature -->
                <?php if ($currentSig): ?>
                <form method="POST" style="margin-top:12px;" onsubmit="return confirm('Remove your current signature?')">
                    <input type="hidden" name="save_signature" value="1">
                    <input type="hidden" name="signature_url" value="">
                    <button type="submit" style="width:100%;padding:11px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.3);color:#f87171;border-radius:50px;font-size:13px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-trash-alt"></i> Remove Current Signature
                    </button>
                </form>
                <?php endif; ?>

            </div><!-- end form-card -->
        </div>

        <script>
        function uploadSignature(input) {
            if (!input.files || !input.files[0]) return;
            const formData = new FormData();
            formData.append('image', input.files[0]);

            document.getElementById('sigDropContent').style.display  = 'none';
            document.getElementById('sigPreviewWrap').style.display  = 'none';
            document.getElementById('sigUploading').style.display    = 'block';
            document.getElementById('sigError').style.display        = 'none';

            fetch('<?php echo BASE_URL; ?>/api/upload_image.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                document.getElementById('sigUploading').style.display = 'none';
                if (data.success) {
                    document.getElementById('sigUrlInput').value           = data.url;
                    document.getElementById('sigPreviewImg').src           = data.url;
                    document.getElementById('sigPreviewWrap').style.display = 'block';
                    document.getElementById('sigSaveBtn').disabled          = false;
                    // Update current preview too
                    const cur = document.getElementById('currentSigImg');
                    const noMsg = document.getElementById('noSigMsg');
                    if (cur) { cur.src = data.url; }
                    else if (noMsg) {
                        noMsg.outerHTML = `<img id="currentSigImg" src="${data.url}" style="max-height:80px;max-width:100%;object-fit:contain;">`;
                    }
                } else {
                    document.getElementById('sigDropContent').style.display = 'block';
                    document.getElementById('sigError').textContent = '❌ ' + data.error;
                    document.getElementById('sigError').style.display = 'block';
                }
            })
            .catch(() => {
                document.getElementById('sigUploading').style.display    = 'none';
                document.getElementById('sigDropContent').style.display  = 'block';
                document.getElementById('sigError').textContent          = '❌ Upload failed. Try again.';
                document.getElementById('sigError').style.display        = 'block';
            });
        }
        </script>

        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>