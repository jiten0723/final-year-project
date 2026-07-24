<?php
// ============================================
// EDUCORE - Course Listing Page
// ============================================
require_once __DIR__ . '/../includes/auth.php';

$db = getDB();

// Filters
$search   = trim($_GET['search']   ?? '');
$category = trim($_GET['category'] ?? '');
$type     = trim($_GET['type']     ?? '');
$level    = trim($_GET['level']    ?? '');
$sort     = trim($_GET['sort']     ?? 'popular');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 9;
$offset   = ($page - 1) * $perPage;

// Build query
$where  = ["co.status = 'approved'"];
$params = [];

if ($search) {
    $where[]  = "(co.title LIKE ? OR co.description LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($category) {
    $where[]  = "cat.slug = ?";
    $params[] = $category;
}
if ($type && in_array($type, ['free','premium'])) {
    $where[]  = "co.type = ?";
    $params[] = $type;
}
if ($level && in_array($level, ['beginner','intermediate','advanced'])) {
    $where[]  = "co.level = ?";
    $params[] = $level;
}

$whereStr = implode(' AND ', $where);
$orderBy  = match($sort) {
    'newest'    => 'co.created_at DESC',
    'price-asc' => 'co.price ASC',
    'price-desc'=> 'co.price DESC',
    'rating'    => 'avg_rating DESC',
    default     => 'enrollment_count DESC'
};

$countSql = "SELECT COUNT(*) FROM courses co
             JOIN users u ON u.id = co.instructor_id
             LEFT JOIN categories cat ON cat.id = co.category_id
             WHERE $whereStr";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalCourses = $countStmt->fetchColumn();
$totalPages   = ceil($totalCourses / $perPage);

$sql = "SELECT co.*, u.name as instructor_name, cat.name as category_name, cat.slug as category_slug,
               AVG(r.rating) as avg_rating, COUNT(DISTINCT r.id) as review_count,
               COUNT(DISTINCT e.id) as enrollment_count
        FROM courses co
        JOIN users u ON u.id = co.instructor_id
        LEFT JOIN categories cat ON cat.id = co.category_id
        LEFT JOIN reviews r ON r.course_id = co.id
        LEFT JOIN enrollments e ON e.course_id = co.id
        WHERE $whereStr
        GROUP BY co.id
        ORDER BY $orderBy
        LIMIT $perPage OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// All categories for sidebar
$allCategories = $db->query("SELECT cat.*, COUNT(co.id) as count FROM categories cat LEFT JOIN courses co ON co.category_id = cat.id AND co.status='approved' GROUP BY cat.id ORDER BY count DESC")->fetchAll();

$catIcons = ['Programming'=>'💻','Design'=>'🎨','Business'=>'💼','Music'=>'🎵','Photography'=>'📷','Marketing'=>'📢','Data Science'=>'📊','Personal Dev'=>'🚀'];

$pageTitle = "Browse Courses";
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:70px;">
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 style="font-size:2.2rem;font-weight:900;margin-bottom:8px;">
                        <?php if ($search): ?>
                            Results for "<span class="text-gradient"><?php echo e($search); ?></span>"
                        <?php elseif ($category): ?>
                            <span class="text-gradient"><?php echo ucwords(str_replace('-',' ',$category)); ?></span> Courses
                        <?php elseif ($type === 'free'): ?>
                            🎁 <span class="text-gradient">Free</span> Courses
                        <?php else: ?>
                            Browse All <span class="text-gradient">Courses</span>
                        <?php endif; ?>
                    </h1>
                    <p style="color:var(--text-muted);">
                        <?php echo number_format($totalCourses); ?> course<?php echo $totalCourses != 1 ? 's' : ''; ?> found
                        <?php if ($search) echo ' for "' . e($search) . '"'; ?>
                    </p>
                </div>
                <div class="col-md-5">
                    <form method="GET" class="nav-search-form" style="max-width:100%;">
                        <i class="fas fa-search nav-search-icon"></i>
                        <input type="text" name="search" class="nav-search-input"
                            placeholder="Search courses..."
                            value="<?php echo e($search); ?>"
                            style="font-size:15px;">
                        <?php if ($category): ?><input type="hidden" name="category" value="<?php echo e($category); ?>"><?php endif; ?>
                        <?php if ($type): ?><input type="hidden" name="type" value="<?php echo e($type); ?>"><?php endif; ?>
                        <button type="submit" class="nav-search-btn">Search</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="row g-4">
            <!-- ===== SIDEBAR FILTERS ===== -->
            <div class="col-lg-3">
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;position:sticky;top:90px;">
                    <h3 style="font-size:16px;font-weight:700;margin-bottom:20px;">Filter Courses</h3>

                    <!-- Course Type -->
                    <div style="margin-bottom:24px;">
                        <h4 style="font-size:13px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">Type</h4>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <?php foreach ([''=>'All Types','free'=>'Free Courses','premium'=>'Premium Courses'] as $val => $label): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['type'=>$val,'page'=>1])); ?>"
                               style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:14px;transition:all 0.2s;background:<?php echo $type === $val ? 'rgba(34,197,94,0.1)' : 'transparent'; ?>;color:<?php echo $type === $val ? 'var(--primary)' : 'var(--text-secondary)'; ?>;border:1px solid <?php echo $type === $val ? 'rgba(34,197,94,0.3)' : 'transparent'; ?>;">
                                <i class="fas fa-<?php echo $val==='' ? 'th-large' : ($val==='free'?'gift':'crown'); ?>"></i> <?php echo $label; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Level -->
                    <div style="margin-bottom:24px;">
                        <h4 style="font-size:13px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">Level</h4>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <?php foreach ([''=>'All Levels','beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced'] as $val => $label): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['level'=>$val,'page'=>1])); ?>"
                               style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:14px;transition:all 0.2s;background:<?php echo $level === $val ? 'rgba(34,197,94,0.1)' : 'transparent'; ?>;color:<?php echo $level === $val ? 'var(--primary)' : 'var(--text-secondary)'; ?>;border:1px solid <?php echo $level === $val ? 'rgba(34,197,94,0.3)' : 'transparent'; ?>;">
                                <i class="fas fa-signal"></i> <?php echo $label; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div>
                        <h4 style="font-size:13px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">Category</h4>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['category'=>'','page'=>1])); ?>"
                               style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border-radius:8px;font-size:14px;transition:all 0.2s;background:<?php echo !$category ? 'rgba(34,197,94,0.1)' : 'transparent'; ?>;color:<?php echo !$category ? 'var(--primary)' : 'var(--text-secondary)'; ?>;">
                                <span>All Categories</span>
                                <span style="background:rgba(255,255,255,0.06);padding:1px 7px;border-radius:10px;font-size:11px;"><?php echo array_sum(array_column($allCategories,'count')); ?></span>
                            </a>
                            <?php foreach ($allCategories as $cat): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['category'=>$cat['slug'],'page'=>1])); ?>"
                               style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border-radius:8px;font-size:14px;transition:all 0.2s;background:<?php echo $category === $cat['slug'] ? 'rgba(34,197,94,0.1)' : 'transparent'; ?>;color:<?php echo $category === $cat['slug'] ? 'var(--primary)' : 'var(--text-secondary)'; ?>;">
                                <span><?php echo $catIcons[$cat['name']] ?? '📚'; ?> <?php echo e($cat['name']); ?></span>
                                <span style="background:rgba(255,255,255,0.06);padding:1px 7px;border-radius:10px;font-size:11px;"><?php echo $cat['count']; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($search || $category || $type || $level): ?>
                    <a href="<?php echo BASE_URL; ?>/courses/index.php" style="display:block;text-align:center;margin-top:20px;padding:10px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:8px;color:#f87171;font-size:14px;text-decoration:none;transition:all 0.2s;">
                        <i class="fas fa-times"></i> Clear All Filters
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== COURSE GRID ===== -->
            <div class="col-lg-9">
                <!-- Sort bar -->
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
                    <span style="color:var(--text-muted);font-size:14px;">
                        Showing <?php echo min($offset + 1, $totalCourses); ?>–<?php echo min($offset + $perPage, $totalCourses); ?> of <?php echo $totalCourses; ?> courses
                    </span>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:13px;color:var(--text-muted);">Sort:</span>
                        <select onchange="window.location='?<?php echo http_build_query(array_merge($_GET, ['sort'=>''])); ?>'+this.value"
                            style="background:var(--bg-card);border:1px solid var(--border);color:var(--text-primary);padding:7px 12px;border-radius:8px;font-size:13px;outline:none;cursor:pointer;">
                            <option value="popular" <?php echo $sort==='popular'?'selected':''; ?>>Most Popular</option>
                            <option value="newest" <?php echo $sort==='newest'?'selected':''; ?>>Newest First</option>
                            <option value="rating" <?php echo $sort==='rating'?'selected':''; ?>>Highest Rated</option>
                            <option value="price-asc" <?php echo $sort==='price-asc'?'selected':''; ?>>Price: Low–High</option>
                            <option value="price-desc" <?php echo $sort==='price-desc'?'selected':''; ?>>Price: High–Low</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($courses)): ?>
                <div style="text-align:center;padding:80px 20px;">
                    <div style="font-size:64px;margin-bottom:16px;">🔍</div>
                    <h3 style="font-size:20px;margin-bottom:8px;">No courses found</h3>
                    <p style="color:var(--text-muted);margin-bottom:24px;">Try adjusting your filters or search for something else.</p>
                    <a href="index.php" class="btn-outline-custom">Browse All Courses</a>
                </div>
                <?php else: ?>

                <div class="courses-grid" id="coursesGrid">
                    <?php foreach ($courses as $course):
                        $rating  = getCourseRating($course['id']);
                        $enrolled = isLoggedIn() ? isEnrolled($_SESSION['user_id'], $course['id']) : false;
                        $icon = $catIcons[$course['category_name']] ?? '📚';
                    ?>
                    
                    <a href="<?php echo BASE_URL; ?>/courses/<?php echo e($course['slug']); ?>"
                       class="course-card"
                       data-title="<?php echo e(strtolower($course['title'])); ?>"
                       data-category="<?php echo e($course['type']); ?>"
                       data-price="<?php echo $course['price']; ?>"
                       data-rating="<?php echo $rating['avg']; ?>"
                       data-animate>
                        <div class="course-thumbnail">
                            <?php if (!empty($course['thumbnail'])): ?>
                                <img src="<?php echo e($course['thumbnail']); ?>" alt="<?php echo e($course['title']); ?>" style="width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;">
                            <?php else: ?>
                                <div class="course-thumbnail-placeholder"><?php echo $icon; ?></div>
                            <?php endif; ?>
                            <span class="course-badge badge-<?php echo $course['type']; ?>"><?php echo strtoupper($course['type']); ?></span>
                            <div class="course-play-btn"><div class="play-icon"><i class="fas fa-play"></i></div></div>
                        </div>
                        <div class="course-info">
                            <div class="course-category-tag"><?php echo e($course['category_name']); ?></div>
                            <h3 class="course-title"><?php echo e($course['title']); ?></h3>
                            <div class="course-instructor">
                                <span class="instructor-dot"><?php echo strtoupper(substr($course['instructor_name'],0,1)); ?></span>
                                <?php echo e($course['instructor_name']); ?>
                            </div>
                            <div class="course-rating">
                                <?php echo renderStars($rating['avg']); ?>
                                <span class="rating-score"><?php echo number_format($rating['avg'],1); ?></span>
                                <span class="rating-count">(<?php echo $rating['total']; ?>)</span>
                            </div>
                            <div class="course-meta">
                                <div class="course-meta-item"><i class="fas fa-book-open"></i> <?php echo $course['total_lessons']; ?> lessons</div>
                                <div class="course-meta-item"><i class="fas fa-clock"></i> <?php echo e($course['duration']); ?></div>
                                <div class="course-meta-item"><i class="fas fa-users"></i> <?php echo $course['enrollment_count']; ?></div>
                            </div>
                            <div class="course-footer">
                                <div>
                                    <span class="course-price <?php echo $course['price']==0?'price-free':'price-paid'; ?>">
                                        <?php echo formatPrice($course['price']); ?>
                                    </span>
                                </div>
                                <?php if ($enrolled): ?>
                                    <span class="btn-enroll-sm" style="background:rgba(34,197,94,0.1);color:var(--primary);border:1px solid rgba(34,197,94,0.3);">
                                        <i class="fas fa-check"></i> Enrolled
                                    </span>
                                <?php else: ?>
                                    <span class="btn-enroll-sm"><?php echo $course['price']==0?'Enroll Free':'Enroll Now'; ?> →</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div style="display:flex;justify-content:center;gap:8px;margin-top:40px;flex-wrap:wrap;">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page-1])); ?>" class="btn-outline-custom" style="padding:9px 18px;">← Prev</a>
                    <?php endif; ?>
                    <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$p])); ?>"
                           style="padding:9px 16px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;background:<?php echo $p==$page?'var(--gradient-primary)':'var(--bg-card)'; ?>;color:<?php echo $p==$page?'#fff':'var(--text-secondary)'; ?>;border:1px solid <?php echo $p==$page?'transparent':'var(--border)'; ?>;">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page+1])); ?>" class="btn-outline-custom" style="padding:9px 18px;">Next →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
