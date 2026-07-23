<?php
// ============================================
// EDUCORE - Homepage
// ============================================
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$pageTitle = "Learn Without Limits";

// Fetch categories with course count
$categories = $db->query("
    SELECT c.*, COUNT(co.id) as course_count 
    FROM categories c 
    LEFT JOIN courses co ON co.category_id = c.id AND co.status = 'approved'
    GROUP BY c.id ORDER BY course_count DESC LIMIT 8
")->fetchAll();

// Fetch featured courses
$featured = $db->query("
    SELECT co.*, u.name as instructor_name, cat.name as category_name,
           AVG(r.rating) as avg_rating, COUNT(DISTINCT r.id) as review_count,
           COUNT(DISTINCT e.id) as enrollment_count
    FROM courses co
    JOIN users u ON u.id = co.instructor_id
    LEFT JOIN categories cat ON cat.id = co.category_id
    LEFT JOIN reviews r ON r.course_id = co.id
    LEFT JOIN enrollments e ON e.course_id = co.id
    WHERE co.status = 'approved' AND co.is_featured = 1
    GROUP BY co.id ORDER BY enrollment_count DESC LIMIT 6
")->fetchAll();

// Free courses
$freeCourses = $db->query("
    SELECT co.*, u.name as instructor_name, cat.name as category_name,
           AVG(r.rating) as avg_rating, COUNT(DISTINCT r.id) as review_count
    FROM courses co
    JOIN users u ON u.id = co.instructor_id
    LEFT JOIN categories cat ON cat.id = co.category_id
    LEFT JOIN reviews r ON r.course_id = co.id
    WHERE co.status = 'approved' AND co.type = 'free'
    GROUP BY co.id LIMIT 4
")->fetchAll();

// Stats
$stats = $db->query("SELECT 
    (SELECT COUNT(*) FROM users WHERE role='student') as students,
    (SELECT COUNT(*) FROM courses WHERE status='approved') as courses,
    (SELECT COUNT(*) FROM users WHERE role='teacher') as teachers,
    (SELECT COUNT(*) FROM enrollments) as enrollments
")->fetch();

// Course icon map
$catIcons = ['Programming'=>'💻','Design'=>'🎨','Business'=>'💼','Music'=>'🎵','Photography'=>'📷','Marketing'=>'📢','Data Science'=>'📊','Personal Dev'=>'🚀'];

include __DIR__ . '/includes/header.php';
?>

<!-- ============ HERO SECTION ============ -->
<section class="hero-section">
    <div class="hero-bg-grid"></div>
    <div class="hero-orb hero-orb1"></div>
    <div class="hero-orb hero-orb2"></div>

    <!-- <div class="container position-relative" style="z-index:2;">
        <div class="row align-items-center g-5">
            <div class="col-lg-6"> -->
    <div class="container position-relative" style="z-index:2;">
        
        <div class="row align-items-center justify-content-center">
        <div class="col-lg-10 col-xl-9 mx-auto text-center">
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    #1 Online Learning Platform in Nepal
                </div>

                <h1 class="hero-title">
                    Learn Skills That<br>
                    <span class="hero-gradient-text">Actually Matter</span>
                </h1>

                <p class="hero-subtitle" style="margin-left:auto;margin-right:auto;">
                    Join 50,000+ learners mastering programming, design, business and more.
                    Expert-led courses at your own pace — free and premium.
                </p>

                <div class="hero-actions" style="justify-content:center;">
                    <a href="<?php echo BASE_URL; ?>/courses/index.php" class="btn-hero-primary">
                        <i class="fas fa-rocket"></i> Start Learning Free
                    </a>
                    <a href="<?php echo BASE_URL; ?>/courses/type=premium" class="btn-hero-secondary">
                        <i class="fas fa-play-circle"></i> Browse Courses
                    </a>
                </div>

                <div class="hero-stats" style="justify-content:center;">
                    <div class="hero-stat-item" style="align-items:center;">
                        <span class="hero-stat-num" data-count="<?php echo $stats['students']; ?>"><?php echo number_format($stats['students']); ?>+</span>
                        <span class="hero-stat-label">Students</span>
                    </div>
                    <div class="hero-stat-item" style="align-items:center;">
                        <span class="hero-stat-num" data-count="<?php echo $stats['courses']; ?>"><?php echo $stats['courses']; ?>+</span>
                        <span class="hero-stat-label">Courses</span>
                    </div>
                    <div class="hero-stat-item" style="align-items:center;">
                        <span class="hero-stat-num" data-count="<?php echo $stats['teachers']; ?>"><?php echo $stats['teachers']; ?>+</span>
                        <span class="hero-stat-label">Expert Teachers</span>
                    </div>
                </div>
            </div>

            <!-- <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-visual">
                    <div class="hero-card-float">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div style="width:48px;height:48px;background:linear-gradient(135deg,#22c55e,#3b82f6);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;">💻</div>
                            <div>
                                <div style="font-weight:700;font-size:15px;">JavaScript Mastery</div>
                                <div style="font-size:12px;color:var(--text-muted);">by John Smith</div>
                            </div>
                            <span class="ms-auto badge-premium" style="padding:4px 12px;border-radius:50px;font-size:11px;font-weight:700;background:rgba(139,92,246,0.9);color:#fff;">PREMIUM</span>
                        </div>
                        <div style="background:rgba(255,255,255,0.04);border-radius:12px;padding:16px;margin-bottom:16px;">
                            <div style="font-size:13px;color:var(--text-muted);margin-bottom:8px;">Your Progress</div>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                <span style="font-size:14px;font-weight:600;">Module 3: DOM Manipulation</span>
                                <span style="color:var(--primary);font-weight:700;font-size:14px;">65%</span>
                            </div>
                            <div class="progress-bar-custom"><div class="progress-fill" style="width:65%" data-width="65%"></div></div>
                        </div>
                        <div style="display:flex;gap:10px;">
                            <div style="flex:1;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);border-radius:10px;padding:12px;text-align:center;">
                                <div style="font-size:18px;font-weight:800;color:var(--primary);">28</div>
                                <div style="font-size:11px;color:var(--text-muted);">Lessons Left</div>
                            </div>
                            <div style="flex:1;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:10px;padding:12px;text-align:center;">
                                <div style="font-size:18px;font-weight:800;color:var(--secondary);">4.9</div>
                                <div style="font-size:11px;color:var(--text-muted);">Rating</div>
                            </div>
                            <div style="flex:1;background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.2);border-radius:10px;padding:12px;text-align:center;">
                                <div style="font-size:18px;font-weight:800;color:#a78bfa;">3</div>
                                <div style="font-size:11px;color:var(--text-muted);">Certs</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div> -->
        </div>
    </div>

    <!-- Scroll indicator -->
    <!-- <div style="position:absolute;bottom:30px;left:50%;transform:translateX(-50%);text-align:center;cursor:pointer;" onclick="scrollTo('categories')">
        <div style="color:var(--text-muted);font-size:12px;margin-bottom:6px;">Scroll to explore</div>
        <div style="width:24px;height:40px;border:2px solid var(--border);border-radius:20px;margin:0 auto;display:flex;align-items:flex-start;justify-content:center;padding-top:6px;">
            <div style="width:4px;height:8px;background:var(--primary);border-radius:2px;animation:scrollDot 1.5s infinite;"></div>
        </div>
    </div> -->
</section>

<style>
@keyframes scrollDot { 0%,100%{transform:translateY(0);opacity:1;} 50%{transform:translateY(12px);opacity:0.3;} }
</style>

<!-- ============ CATEGORIES ============ -->
<section class="section-padding" id="categories">
    <div class="container">
        <div class="row mb-5">
            <div class="col-md-8">
                <div class="section-tag"><i class="fas fa-th-large"></i> Browse by Category</div>
                <h2 class="section-title">Explore <span class="text-gradient">Top Categories</span></h2>
                <p class="section-desc">Find courses in your area of interest and start building real skills today.</p>
            </div>
            <div class="col-md-4 d-flex align-items-end justify-content-md-end mt-3 mt-md-0">
                <a href="<?php echo BASE_URL; ?>/courses/index.php" class="btn-outline-custom">
                    All Categories <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="category-grid">
            <?php foreach ($categories as $cat):
                $icon = $catIcons[$cat['name']] ?? '📚';
            ?>
            <a href="<?php echo BASE_URL; ?>/courses/index.php?category=<?php echo e($cat['slug']); ?>"
               class="category-card" data-animate>
                <div class="cat-icon" style="background:<?php echo e($cat['color']); ?>22;">
                    <span style="font-size:26px;"><?php echo $icon; ?></span>
                </div>
                <div class="cat-info">
                    <h4><?php echo e($cat['name']); ?></h4>
                    <span><?php echo $cat['course_count']; ?> course<?php echo $cat['course_count'] != 1 ? 's' : ''; ?></span>
                </div>
                <i class="fas fa-chevron-right cat-arrow"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ FEATURED COURSES ============ -->
<section class="section-padding" style="background:linear-gradient(180deg, transparent, rgba(34,197,94,0.03), transparent);">
    <div class="container">
        <div class="row mb-5 align-items-end">
            <div class="col-md-8">
                <div class="section-tag"><i class="fas fa-star"></i> Featured</div>
                <h2 class="section-title">🔥 Most Popular <span class="text-gradient">Courses</span></h2>
                <p class="section-desc">Handpicked by our team — highly rated and loved by thousands of students.</p>
            </div>
            <div class="col-md-4 d-flex justify-content-md-end mt-3 mt-md-0">
                <div class="d-flex gap-2">
                    <button class="tag-filter active" data-tag="all" onclick="filterByTag('all','#featuredGrid')">All</button>
                    <button class="tag-filter" data-tag="free" onclick="filterByTag('free','#featuredGrid')">Free</button>
                    <button class="tag-filter" data-tag="premium" onclick="filterByTag('premium','#featuredGrid')">Premium</button>
                </div>
            </div>
        </div>

        <div class="courses-grid" id="featuredGrid">
            <?php foreach ($featured as $course):
                $rating = getCourseRating($course['id']);
                $enrolled = isLoggedIn() ? isEnrolled($_SESSION['user_id'], $course['id']) : false;
                $icon = $catIcons[$course['category_name']] ?? '📚';
            ?>
            <a href="<?php echo BASE_URL; ?>/courses/<?php echo e($course['slug']); ?>"
               class="course-card"
               data-title="<?php echo e(strtolower($course['title'])); ?>"
               data-category="<?php echo e(strtolower($course['type'])); ?>"
               data-price="<?php echo $course['price']; ?>"
               data-rating="<?php echo $rating['avg']; ?>"
               data-animate>
                <div class="course-thumbnail">
                    <?php if (!empty($course['thumbnail'])): ?>
                        <img src="<?php echo e($course['thumbnail']); ?>" alt="<?php echo e($course['title']); ?>"  style="width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;" loading = "lazy">
                    <?php else: ?>
                        <div class="course-thumbnail-placeholder"><?php echo $icon; ?></div>
                    <?php endif; ?>
                    <?php if ($course['is_featured']): ?>
                        <span class="course-badge badge-featured">⭐ Featured</span>
                    <?php endif; ?>
                    <span class="course-badge badge-<?php echo $course['type']; ?>" style="top:12px;right:12px;left:auto;">
                        <?php echo strtoupper($course['type']); ?>
                    </span>
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
                        <div class="course-meta-item"><i class="fas fa-signal"></i> <?php echo ucfirst($course['level']); ?></div>
                    </div>
                    <div class="course-footer">
                        <div>
                            <span class="course-price <?php echo $course['price'] == 0 ? 'price-free' : 'price-paid'; ?>">
                                <?php echo formatPrice($course['price']); ?>
                            </span>
                            <?php if ($course['price'] > 0): ?>
                                <span class="price-original">NPR <?php echo number_format($course['price'] * 1.3, 0); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($enrolled): ?>
                            <span class="btn-enroll-sm" style="background:rgba(34,197,94,0.1);color:var(--primary);border:1px solid rgba(34,197,94,0.3);">
                                <i class="fas fa-check"></i> Enrolled
                            </span>
                        <?php else: ?>
                            <span class="btn-enroll-sm"><?php echo $course['price'] == 0 ? 'Enroll Free' : 'Enroll Now'; ?> <i class="fas fa-arrow-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?php echo BASE_URL; ?>/courses/index.php" class="btn-primary-custom">
                View All Courses <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- ============ WHY EDUCORE ============ -->
<section class="section-padding">
    <div class="container">
        <div class="text-center mb-5" data-animate>
            <div class="section-tag mx-auto" style="display:inline-flex;"><i class="fas fa-bolt"></i> Why EDUCORE?</div>
            <h2 class="section-title">Everything You Need to <span class="text-gradient">Succeed</span></h2>
        </div>
        <div class="row g-4">
            <?php
            $features = [
                ['icon'=>'fas fa-play-circle','color'=>'#22c55e','title'=>'Video + Text Courses','desc'=>'Learn via HD video lessons or rich text content — whatever suits your style.'],
                ['icon'=>'fas fa-brain','color'=>'#3b82f6','title'=>'AI Adaptive Quizzes','desc'=>'Our smart quiz system adjusts difficulty based on your performance in real-time.'],
                ['icon'=>'fas fa-certificate','color'=>'#8b5cf6','title'=>'Verifiable Certificates','desc'=>'Earn certificates on course completion you can share on LinkedIn or CV.'],
                ['icon'=>'fas fa-gamepad','color'=>'#f59e0b','title'=>'Game-Based Learning','desc'=>'Interactive puzzles and mini-games that make learning engaging and fun.'],
                ['icon'=>'fas fa-route','color'=>'#ec4899','title'=>'Personalized Paths','desc'=>'Get course recommendations tailored to your interests and learning history.'],
                ['icon'=>'fas fa-shield-alt','color'=>'#06b6d4','title'=>'Secure Payments','desc'=>'Pay safely with eSewa or PayPal with instant course access on confirmation.'],
            ];
            foreach ($features as $i => $f): ?>
            <div class="col-md-6 col-lg-4" data-animate>
                <div class="stat-card h-100" style="--stat-color:<?php echo $f['color']; ?>">
                    <div class="stat-icon" style="background:<?php echo $f['color']; ?>1a;">
                        <i class="<?php echo $f['icon']; ?>" style="color:<?php echo $f['color']; ?>;font-size:22px;"></i>
                    </div>
                    <h4 style="font-size:17px;font-weight:700;margin-bottom:8px;"><?php echo $f['title']; ?></h4>
                    <p style="color:var(--text-muted);font-size:14px;margin:0;line-height:1.6;"><?php echo $f['desc']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ AI RECOMMENDATIONS (logged-in only) ============ -->
<?php if (isLoggedIn() && $_SESSION['user_role'] === 'student'): ?>
<section class="section-padding" id="ai-recommendations" style="background:linear-gradient(180deg,rgba(59,130,246,0.03),rgba(34,197,94,0.03));">
    <div class="container">
        <div class="row mb-5 align-items-end">
            <div class="col-md-8">
                <div class="section-tag"><i class="fas fa-robot"></i></div>
                <h2 class="section-title">Recommended <span class="text-gradient">For You</span></h2>
                <p class="section-desc">Courses picked  based on your learning history, quiz performance, and interests.</p>
            </div>
            <div class="col-md-4 d-flex justify-content-md-end align-items-end mt-3 mt-md-0">
                <a href="<?php echo BASE_URL; ?>/dashboard/student?tab=recommended" class="btn-outline-custom">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Loading state -->
        <div id="aiRecsLoading" style="text-align:center;padding:40px;">
            <div style="display:inline-flex;align-items:center;gap:12px;color:var(--text-muted);font-size:14px;">
                <i class="fas fa-robot fa-spin" style="color:var(--primary);font-size:20px;"></i>
                finding the best courses for you...
            </div>
        </div>

        <!-- Course grid rendered by JS -->
        <div class="courses-grid" id="aiRecsGrid" style="display:none;"></div>

        <!-- Empty state -->
        <div id="aiRecsEmpty" style="display:none;text-align:center;padding:40px;color:var(--text-muted);">
            <i class="fas fa-graduation-cap" style="font-size:36px;margin-bottom:12px;display:block;color:var(--primary);"></i>
            <p>Enroll in a course first and our AI will start personalizing recommendations for you!</p>
            <a href="<?php echo BASE_URL; ?>/courses/index.php" class="btn-primary-custom" style="margin-top:12px;">Browse Courses</a>
        </div>
    </div>
</section>

<script>
(function loadAIRecs() {
    const userId  = <?php echo $_SESSION['user_id']; ?>;
    const baseUrl = '<?php echo BASE_URL; ?>';
    const catColors = {
        'Programming':'#3b82f6','Design':'#8b5cf6','Business':'#10b981',
        'Marketing':'#ec4899','Data Science':'#06b6d4','Personal Dev':'#84cc16',
        'Music':'#f59e0b','Photography':'#ef4444'
    };

    fetch(`${baseUrl}/api/recommendations.php?user_id=${userId}`)
        .then(r => r.json())
        .then(courses => {
            document.getElementById('aiRecsLoading').style.display = 'none';

            if (!courses.length) {
                document.getElementById('aiRecsEmpty').style.display = 'block';
                return;
            }

            const grid = document.getElementById('aiRecsGrid');
            grid.style.display = 'grid';
            grid.innerHTML = courses.map(c => {
                const color  = catColors[c.category] || '#22c55e';
                const price  = c.price == 0 ? '<span class="course-price price-free">FREE</span>'
                                            : `<span class="course-price price-paid">NPR ${Number(c.price).toLocaleString()}</span>`;
                const stars  = Array.from({length:5}, (_,i) =>
                    `<i class="fas fa-star" style="font-size:11px;color:${i < Math.round(c.rating) ? '#fbbf24' : 'var(--text-muted)'}"></i>`
                ).join('');

                return `
                <a href="${baseUrl}/courses/${c.slug}" class="course-card" data-animate>
                    <div class="course-thumbnail">
                        <div class="course-thumbnail-placeholder">${c.icon}</div>
                        <span class="course-badge badge-${c.type}">${c.type.toUpperCase()}</span>
                        <div style="position:absolute;bottom:10px;left:10px;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);border-radius:50px;padding:3px 10px;font-size:11px;font-weight:600;color:#fff;border:1px solid rgba(255,255,255,0.15);">
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
                        <div class="course-footer">
                            ${price}
                            <span class="btn-enroll-sm">${c.price == 0 ? 'Enroll Free' : 'Enroll Now'} <i class="fas fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>`;
            }).join('');
        })
        .catch(() => {
            document.getElementById('aiRecsLoading').style.display = 'none';
            document.getElementById('aiRecsEmpty').style.display   = 'block';
        });
})();
</script>
<?php endif; ?>

<!-- ============ FREE COURSES ============ -->
<section class="section-padding" style="background:linear-gradient(180deg, rgba(34,197,94,0.03), transparent);">
    <div class="container">
        <div class="row mb-5 align-items-end">
            <div class="col-md-8">
                <div class="section-tag"><i class="fas fa-gift"></i> No Credit Card Needed</div>
                <h2 class="section-title">🎁 Start with <span class="text-gradient">Free Courses</span></h2>
                <p class="section-desc">Jump in with zero cost and build your foundation before going premium.</p>
            </div>
        </div>
        <div class="courses-grid">
            <?php foreach ($freeCourses as $course):
                $rating = getCourseRating($course['id']);
                $icon = $catIcons[$course['category_name']] ?? '📚';
            ?>
            <a href="<?php echo BASE_URL; ?>/courses/<?php echo e($course['slug']); ?>" class="course-card" data-animate>
                <div class="course-thumbnail">
                    <?php if (!empty($course['thumbnail'])): ?>
                        <img src="<?php echo e($course['thumbnail']); ?>" alt="<?php echo e($course['title']); ?>" style="width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;" loading = "lazy">
                    <?php else: ?>
                        <div class="course-thumbnail-placeholder"><?php echo $icon; ?></div>
                    <?php endif; ?>
                    <span class="course-badge badge-free">FREE</span>
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
                    <div class="course-footer">
                        <span class="course-price price-free">FREE</span>
                        <span class="btn-enroll-sm">Enroll Free <i class="fas fa-arrow-right"></i></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ TESTIMONIALS ============ -->
<section class="section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-tag mx-auto" style="display:inline-flex;"><i class="fas fa-quote-left"></i> Student Stories</div>
            <h2 class="section-title">Loved by <span class="text-gradient">Thousands</span></h2>
        </div>
        <div class="row g-4">
            <?php
            $testimonials = [
                ['name'=>'Priya Sharma','role'=>'Frontend Developer','text'=>'EDUCORE completely changed my career. The JavaScript course was world-class — I landed my first dev job 3 months after completing it!','rating'=>5,'avatar'=>'P'],
                ['name'=>'Bikash Thapa','role'=>'UI/UX Designer','text'=>'The Figma design course is incredibly detailed. Sarah explains everything step by step. Worth every rupee — totally recommend!','rating'=>5,'avatar'=>'B'],
                ['name'=>'Anisha Rai','role'=>'Data Analyst','text'=>'The adaptive quiz system is brilliant. It really helped me identify weak spots and improve. The Python course is top-notch.','rating'=>5,'avatar'=>'A'],
            ];
            foreach ($testimonials as $t): ?>
            <div class="col-md-4" data-animate>
                <div class="stat-card h-100" style="border-color:rgba(34,197,94,0.15);">
                    <div style="display:flex;gap:2px;margin-bottom:16px;">
                        <?php for($i=0;$i<$t['rating'];$i++) echo '<i class="fas fa-star" style="color:#fbbf24;font-size:14px;"></i>'; ?>
                    </div>
                    <p style="color:var(--text-secondary);font-size:15px;line-height:1.7;margin-bottom:20px;font-style:italic;">"<?php echo $t['text']; ?>"</p>
                    <div style="display:flex;align-items:center;gap:12px;margin-top:auto;">
                        <div style="width:42px;height:42px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;color:#fff;"><?php echo $t['avatar']; ?></div>
                        <div>
                            <div style="font-weight:700;font-size:15px;"><?php echo $t['name']; ?></div>
                            <div style="font-size:12px;color:var(--text-muted);"><?php echo $t['role']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ CTA SECTION ============ -->
<section class="section-padding">
    <div class="container">
        <div style="background:linear-gradient(135deg,#0d2210,#0d1a2e);border:1px solid rgba(34,197,94,0.2);border-radius:28px;padding:60px;text-align:center;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:radial-gradient(circle,rgba(34,197,94,0.15),transparent);border-radius:50%;"></div>
            <div style="position:absolute;bottom:-60px;left:-60px;width:200px;height:200px;background:radial-gradient(circle,rgba(59,130,246,0.15),transparent);border-radius:50%;"></div>
            <div style="position:relative;z-index:1;">
                <div style="font-size:48px;margin-bottom:16px;">🎓</div>
                <h2 style="font-size:2.5rem;font-weight:900;margin-bottom:16px;">Ready to Start Your<br><span class="text-gradient">Learning Journey?</span></h2>
                <p style="color:var(--text-secondary);font-size:17px;max-width:480px;margin:0 auto 32px;">Join thousands of students already learning on EDUCORE. Free forever, upgrade anytime.</p>
                <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
                    <?php if (!isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>/register.php" class="btn-hero-primary">
                            <i class="fas fa-user-plus"></i> Create Free Account
                        </a>
                        <a href="<?php echo BASE_URL; ?>/courses/index.php" class="btn-hero-secondary">
                            <i class="fas fa-graduation-cap"></i> Browse Courses
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/courses/index.php" class="btn-hero-primary">
                            <i class="fas fa-rocket"></i> Continue Learning
                        </a>
                        <a href="<?php echo BASE_URL; ?>/dashboard/student" class="btn-hero-secondary">
                            <i class="fas fa-tachometer-alt"></i> My Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Use role-specific footer, fall back to public footer
if (isLoggedIn() && $_SESSION['user_role'] === 'teacher') {
    // Load teacher stats for the footer stats panel
    $uid = $_SESSION['user_id'];
    $totalCourses    = $db->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id=?"); $totalCourses->execute([$uid]); $totalCourses = (int)$totalCourses->fetchColumn();
    $totalStudents   = $db->prepare("SELECT COUNT(DISTINCT e.user_id) FROM enrollments e JOIN courses co ON co.id=e.course_id WHERE co.instructor_id=?"); $totalStudents->execute([$uid]); $totalStudents = (int)$totalStudents->fetchColumn();
    $footerAvgRating = $db->prepare("SELECT ROUND(AVG(r.rating),1) FROM reviews r JOIN courses co ON co.id=r.course_id WHERE co.instructor_id=?"); $footerAvgRating->execute([$uid]); $footerAvgRating = $footerAvgRating->fetchColumn() ?: '—';
    $footerCertCount = $db->prepare("SELECT COUNT(*) FROM certificates cert JOIN courses co ON co.id=cert.course_id WHERE co.instructor_id=?"); $footerCertCount->execute([$uid]); $footerCertCount = (int)$footerCertCount->fetchColumn();
    $teacherFooter = true;
} elseif (isLoggedIn() && $_SESSION['user_role'] === 'admin') {
    $adminFooter = true;
    $adminFooterStats = [
        'total_users'   => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_courses' => $db->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
    ];
}
include __DIR__ . '/includes/footer.php';
?>
