<?php
// ============================================
// EDUCORE - Course Detail Page
// ============================================
require_once __DIR__ . '/../includes/auth.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: " . BASE_URL . "/courses/index.php"); exit(); }

$course = $db->prepare("
    SELECT co.*, u.name as instructor_name, u.bio as instructor_bio,
           cat.name as category_name, cat.slug as category_slug
    FROM courses co
    JOIN users u ON u.id = co.instructor_id
    LEFT JOIN categories cat ON cat.id = co.category_id
    WHERE co.id = ? AND co.status = 'approved'
");
$course->execute([$id]);
$course = $course->fetch();
if (!$course) { header("Location: " . BASE_URL . "/courses/index.php"); exit(); }

$rating   = getCourseRating($id);
$enrolled = isLoggedIn() ? isEnrolled($_SESSION['user_id'], $id) : false;

// Lessons
$lessons = $db->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_num");
$lessons->execute([$id]);
$lessons = $lessons->fetchAll();

// Reviews
$reviews = $db->prepare("
    SELECT r.*, u.name as user_name FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.course_id = ? ORDER BY r.created_at DESC LIMIT 10
");
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

// Check if user left review
$userReview = null;
if (isLoggedIn()) {
    $ur = $db->prepare("SELECT * FROM reviews WHERE user_id=? AND course_id=?");
    $ur->execute([$_SESSION['user_id'], $id]);
    $userReview = $ur->fetch();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    requireLogin();
    if (!$enrolled) { $reviewError = 'You must be enrolled to review this course.'; }
    else {
        $rating_val = (int)$_POST['rating'];
        $review_txt = trim($_POST['review'] ?? '');
        if ($rating_val < 1 || $rating_val > 5) { $reviewError = 'Please select a rating.'; }
        else {
            $db->prepare("INSERT INTO reviews (user_id,course_id,rating,review) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),review=VALUES(review)")
               ->execute([$_SESSION['user_id'], $id, $rating_val, $review_txt]);
            header("Location: detail.php?id=$id&reviewed=1"); exit();
        }
    }
}

// Instructor course count
$instrCourseCount = $db->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id=? AND status='approved'");
$instrCourseCount->execute([$course['instructor_id']]);
$instrCourseCount = $instrCourseCount->fetchColumn();

// Related courses
$related = $db->prepare("
    SELECT co.*, u.name as instructor_name, AVG(r.rating) as avg_rating
    FROM courses co JOIN users u ON u.id=co.instructor_id
    LEFT JOIN reviews r ON r.course_id=co.id
    WHERE co.category_id=? AND co.id!=? AND co.status='approved'
    GROUP BY co.id LIMIT 3
");
$related->execute([$course['category_id'], $id]);
$related = $related->fetchAll();


$catIcons = ['Programming'=>'💻','Design'=>'🎨','Business'=>'💼','Music'=>'🎵','Photography'=>'📷','Marketing'=>'📢','Data Science'=>'📊','Personal Dev'=>'🚀'];
$icon = $catIcons[$course['category_name']] ?? '📚';
$pageTitle = $course['title'];
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:70px;">
<!-- Course Hero -->
<div style="background:linear-gradient(135deg,#060b15,#0d1a2e);border-bottom:1px solid var(--border);padding:52px 0 0;">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7">
                <!-- Breadcrumb -->
                <nav style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">
                    <a href="<?php echo BASE_URL; ?>/index.php" style="color:var(--text-muted);">Home</a>
                    <span style="margin:0 8px;">›</span>
                    <a href="<?php echo BASE_URL; ?>/courses/index.php?category=<?php echo e($course['category_slug']); ?>" style="color:var(--text-muted);"><?php echo e($course['category_name']); ?></a>
                    <span style="margin:0 8px;">›</span>
                    <span style="color:var(--text-primary);"><?php echo truncate($course['title'],40); ?></span>
                </nav>

                <span class="course-badge badge-<?php echo $course['type']; ?>" style="position:static;font-size:12px;padding:5px 14px;margin-bottom:16px;display:inline-block;"><?php echo strtoupper($course['type']); ?></span>

                <h1 style="font-size:2rem;font-weight:900;line-height:1.25;margin-bottom:16px;"><?php echo e($course['title']); ?></h1>
                <p style="color:var(--text-secondary);font-size:16px;line-height:1.6;margin-bottom:20px;"><?php echo e($course['short_description']); ?></p>

                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <?php echo renderStars($rating['avg']); ?>
                        <span style="font-weight:700;color:#fbbf24;"><?php echo number_format($rating['avg'],1); ?></span>
                        <span style="color:var(--text-muted);font-size:13px;">(<?php echo $rating['total']; ?> ratings)</span>
                    </div>
                    <span style="color:var(--text-muted);font-size:13px;">|</span>
                    <span style="color:var(--text-muted);font-size:13px;"><i class="fas fa-users me-1"></i><?php
                        $ec=$db->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id=?");
                        $ec->execute([$id]); echo number_format($ec->fetchColumn());
                    ?> students</span>
                    <span style="color:var(--text-muted);font-size:13px;">|</span>
                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;padding:3px 10px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);border-radius:50px;color:var(--primary);">
                        <i class="fas fa-signal"></i> <?php echo ucfirst($course['level']); ?>
                    </span>
                </div>

                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:40px;height:40px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;color:#fff;">
                        <?php echo strtoupper(substr($course['instructor_name'],0,1)); ?>
                    </div>
                    <div>
                        <div style="font-size:13px;color:var(--text-muted);">Created by</div>
                        <div style="font-weight:600;color:var(--primary);"><?php echo e($course['instructor_name']); ?></div>
                    </div>
                </div>

                <div style="display:flex;gap:20px;margin-top:24px;flex-wrap:wrap;font-size:13px;color:var(--text-muted);">
                    <span><i class="fas fa-book-open me-1"></i><?php echo $course['total_lessons']; ?> lessons</span>
                    <span><i class="fas fa-clock me-1"></i><?php echo e($course['duration']); ?></span>
                    <span><i class="fas fa-globe me-1"></i>English</span>
                    <span><i class="fas fa-sync me-1"></i>Last updated <?php echo date('M Y', strtotime($course['updated_at'])); ?></span>
                </div>
            </div>

            <!-- Sticky Enroll Card -->
            <div class="col-lg-5">
                <div style="position:sticky;top:90px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-xl);overflow:hidden;box-shadow:var(--shadow-lg);">
                    <!-- Preview thumbnail -->
                    <div style="aspect-ratio:16/9;background:linear-gradient(135deg,#0d1a2e,#0a2010);display:flex;align-items:center;justify-content:center;font-size:80px;position:relative;overflow:hidden;">
                        <?php if (!empty($course['thumbnail'])): ?>
                            <img src="<?php echo e($course['thumbnail']); ?>" alt="<?php echo e($course['title']); ?>" style="width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;">
                        <?php else: ?>
                            <?php echo $icon; ?>
                        <?php endif; ?>
                        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;">
                            <a href="#previewSection" onclick="scrollTo('previewSection')" style="width:64px;height:64px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;box-shadow:0 4px 20px rgba(34,197,94,0.4);">
                                <i class="fas fa-play"></i>
                            </a>
                        </div>
                    </div>

                    <div style="padding:24px;">
                        <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:20px;">
                            <span style="font-size:32px;font-weight:900;<?php echo $course['price']==0?'color:var(--primary)':''; ?>">
                                <?php echo formatPrice($course['price']); ?>
                            </span>
                            <?php if ($course['price'] > 0): ?>
                                <span style="font-size:18px;color:var(--text-muted);text-decoration:line-through;">NPR <?php echo number_format($course['price']*1.3,0); ?></span>
                                <span style="font-size:14px;font-weight:700;color:#ef4444;background:rgba(239,68,68,0.1);padding:3px 8px;border-radius:4px;">30% OFF</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($enrolled): ?>
                            <a href="<?php echo BASE_URL; ?>/dashboard/student.php?tab=courses" class="btn-primary-custom w-100 justify-content-center mb-3" style="font-size:16px;padding:14px;background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.3);box-shadow:none;color:var(--primary);">
                                <i class="fas fa-play-circle"></i> Continue Learning
                            </a>
                            <div style="text-align:center;font-size:13px;color:var(--primary);"><i class="fas fa-check-circle me-1"></i>You're enrolled in this course</div>
                        <?php elseif ($course['type'] === 'free'): ?>
                            <a href="<?php echo BASE_URL; ?>/courses/enroll.php?id=<?php echo $id; ?>" class="btn-primary-custom w-100 justify-content-center mb-3" style="font-size:16px;padding:14px;">
                                <i class="fas fa-rocket"></i> Enroll For Free
                            </a>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>/payment/checkout.php?course_id=<?php echo $id; ?>" class="btn-primary-custom w-100 justify-content-center mb-3" style="font-size:16px;padding:14px;">
                                <i class="fas fa-lock-open"></i> Enroll Now — <?php echo formatPrice($course['price']); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>/courses/enroll.php?id=<?php echo $id; ?>&preview=1" class="btn-outline-custom w-100 justify-content-center" style="font-size:14px;padding:11px;">
                                <i class="fas fa-eye"></i> Preview Free Lessons
                            </a>
                        <?php endif; ?>

                        <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px;">
                            <?php
                            $perks = [
                                ['fas fa-infinity','Full lifetime access'],
                                ['fas fa-mobile-alt','Access on mobile & desktop'],
                                ['fas fa-certificate','Certificate of completion'],
                                ['fas fa-download','Downloadable resources'],
                                [$course['type']==='free'?'fas fa-gift':'fas fa-undo', $course['type']==='free'?'Completely free':'30-day money-back guarantee'],
                            ];
                            foreach ($perks as [$ic,$label]): ?>
                            <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--text-secondary);">
                                <i class="<?php echo $ic; ?>" style="color:var(--primary);width:16px;"></i> <?php echo $label; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="display:flex;gap:10px;margin-top:20px;">
                            <button onclick="copyToClipboard(window.location.href)" style="flex:1;padding:9px;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;color:var(--text-secondary);font-size:13px;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                                <i class="fas fa-share-alt me-1"></i> Share
                            </button>
                            <button style="flex:1;padding:9px;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;color:var(--text-secondary);font-size:13px;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                                <i class="far fa-heart me-1"></i> Wishlist
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tabs -->
        <ul class="nav mt-5" id="courseTabs" style="border-bottom:1px solid var(--border);gap:4px;">
            <?php foreach (['overview'=>'Overview','curriculum'=>'Curriculum','reviews'=>'Reviews','instructor'=>'Instructor'] as $tab=>$label): ?>
            <li><a href="#<?php echo $tab; ?>" onclick="switchTab('<?php echo $tab; ?>')" class="course-tab-link <?php echo $tab==='overview'?'active':''; ?>" id="tab-<?php echo $tab; ?>"
                   style="display:block;padding:14px 20px;font-size:14px;font-weight:600;color:var(--text-muted);border-bottom:2px solid transparent;margin-bottom:-1px;transition:all 0.2s;cursor:pointer;">
                <?php echo $label; ?>
            </a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Tab Content -->
<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-7">

            <!-- OVERVIEW -->
            <div id="overview" class="tab-pane-custom">
                <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:20px;">What you'll learn</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:36px;">
                    <?php
                    $learns = ['Build real-world projects from scratch','Master modern development tools','Understand core principles deeply','Write clean, professional code','Debug and troubleshoot confidently','Deploy applications to production'];
                    foreach ($learns as $item): ?>
                    <div style="display:flex;align-items:flex-start;gap:10px;font-size:14px;color:var(--text-secondary);">
                        <i class="fas fa-check-circle" style="color:var(--primary);margin-top:2px;flex-shrink:0;"></i> <?php echo $item; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:16px;">Course Description</h2>
                <div style="color:var(--text-secondary);line-height:1.8;font-size:15px;" id="descContent">
                    <?php echo nl2br(e($course['description'])); ?>
                </div>

                <h2 style="font-size:1.3rem;font-weight:800;margin:32px 0 16px;">Requirements</h2>
                <ul style="color:var(--text-secondary);font-size:14px;line-height:2;padding-left:20px;">
                    <li>Basic computer skills and internet access</li>
                    <li>No prior experience required — we start from the very beginning</li>
                    <li>A desire to learn and practice regularly</li>
                </ul>
            </div>

            <!-- CURRICULUM -->
            <div id="curriculum" class="tab-pane-custom" style="display:none;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <h2 style="font-size:1.3rem;font-weight:800;"><?php echo count($lessons); ?> Lessons · <?php echo e($course['duration']); ?></h2>
                    <?php if (!$enrolled && $course['type']==='premium'): ?>
                        <span style="font-size:13px;color:var(--text-muted);"><i class="fas fa-lock me-1"></i>Preview available</span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($lessons as $i => $lesson):
                        $canAccess = $enrolled || $lesson['is_free_preview'] || $course['type']==='free';
                    ?>
                    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px 20px;display:flex;align-items:center;gap:14px;cursor:pointer;transition:all 0.2s;" onclick="<?php echo $canAccess ? "openLesson($lesson[id],$course[id])" : "showPaywall()"; ?>" onmouseover="this.style.borderColor='rgba(34,197,94,0.3)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div style="width:32px;height:32px;background:<?php echo $canAccess?'rgba(34,197,94,0.12)':'rgba(255,255,255,0.04)'; ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-<?php echo $canAccess?'play':'lock'; ?>" style="font-size:12px;color:<?php echo $canAccess?'var(--primary)':'var(--text-muted)'; ?>;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:14px;font-weight:600;color:var(--text-primary);"><?php echo $i+1; ?>. <?php echo e($lesson['title']); ?></div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                                <?php echo $lesson['video_url'] ? '<i class="fas fa-video me-1"></i>Video' : '<i class="fas fa-file-alt me-1"></i>Text'; ?>
                                · <?php echo $lesson['duration_minutes']; ?> min
                                <?php if ($lesson['is_free_preview']): ?><span style="color:var(--primary);font-size:10px;font-weight:700;background:rgba(34,197,94,0.1);padding:1px 7px;border-radius:10px;margin-left:6px;">FREE</span><?php endif; ?>
                            </div>
                        </div>
                        <?php if ($canAccess): ?>
                            <i class="fas fa-chevron-right" style="color:var(--text-muted);font-size:12px;"></i>
                        <?php else: ?>
                            <i class="fas fa-lock" style="color:var(--text-muted);font-size:12px;"></i>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- REVIEWS -->
            <div id="reviews" class="tab-pane-custom" style="display:none;">
                <!-- Rating summary -->
                <div style="display:flex;align-items:center;gap:32px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:28px;">
                    <div style="text-align:center;">
                        <div style="font-size:56px;font-weight:900;color:#fbbf24;line-height:1;"><?php echo number_format($rating['avg'],1); ?></div>
                        <div style="margin:6px 0;"><?php echo renderStars($rating['avg']); ?></div>
                        <div style="font-size:13px;color:var(--text-muted);"><?php echo $rating['total']; ?> ratings</div>
                    </div>
                    <div style="flex:1;">
                        <?php for ($s=5;$s>=1;$s--):
                            $cnt=$db->prepare("SELECT COUNT(*) FROM reviews WHERE course_id=? AND rating=?");
                            $cnt->execute([$id,$s]); $cnt=$cnt->fetchColumn();
                            $pct=$rating['total']>0?round($cnt/$rating['total']*100):0;
                        ?>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                            <span style="font-size:12px;color:var(--text-muted);width:12px;"><?php echo $s; ?></span>
                            <i class="fas fa-star" style="color:#fbbf24;font-size:11px;"></i>
                            <div style="flex:1;height:6px;background:var(--border);border-radius:3px;overflow:hidden;">
                                <div style="height:100%;width:<?php echo $pct; ?>%;background:#fbbf24;border-radius:3px;"></div>
                            </div>
                            <span style="font-size:12px;color:var(--text-muted);width:28px;"><?php echo $pct; ?>%</span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Write review -->
                <?php if ($enrolled && !$userReview): ?>
                <div style="background:var(--bg-card);border:1px solid rgba(34,197,94,0.2);border-radius:var(--radius-lg);padding:24px;margin-bottom:24px;">
                    <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;">Leave a Review</h3>
                    <?php if (isset($reviewError)): ?><div class="alert-custom alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $reviewError; ?></div><?php endif; ?>
                    <form method="POST">
                        <div style="margin-bottom:16px;">
                            <div style="font-size:13px;color:var(--text-muted);margin-bottom:8px;">Your rating:</div>
                            <div class="stars-input">
                                <?php for($s=1;$s<=5;$s++): ?>
                                    <button type="button" class="star-input-btn"><i class="fas fa-star"></i></button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="ratingInput" value="0">
                        </div>
                        <textarea name="review" rows="4" class="form-input-custom" placeholder="Share your experience with this course..." style="padding:13px 16px;"></textarea>
                        <button type="submit" name="submit_review" class="btn-primary-custom mt-3" style="padding:10px 24px;">
                            <i class="fas fa-paper-plane"></i> Submit Review
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Review list -->
                <?php if (empty($reviews)): ?>
                    <div style="text-align:center;padding:40px;color:var(--text-muted);">
                        <i class="fas fa-star" style="font-size:36px;margin-bottom:12px;display:block;"></i>
                        No reviews yet. Be the first to review!
                    </div>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:16px;">
                    <?php foreach ($reviews as $rev): ?>
                    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:20px;">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                            <div style="width:40px;height:40px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;"><?php echo strtoupper(substr($rev['user_name'],0,1)); ?></div>
                            <div>
                                <div style="font-weight:600;font-size:14px;"><?php echo e($rev['user_name']); ?></div>
                                <div style="display:flex;gap:2px;margin-top:2px;"><?php echo renderStars($rev['rating']); ?></div>
                            </div>
                            <div style="margin-left:auto;font-size:12px;color:var(--text-muted);"><?php echo timeAgo($rev['created_at']); ?></div>
                        </div>
                        <p style="color:var(--text-secondary);font-size:14px;line-height:1.7;margin:0;"><?php echo e($rev['review']); ?></p>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- INSTRUCTOR -->
            <div id="instructor" class="tab-pane-custom" style="display:none;">
                <div style="display:flex;align-items:flex-start;gap:20px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;margin-bottom:24px;">
                    <div style="width:80px;height:80px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;color:#fff;flex-shrink:0;">
                        <?php echo strtoupper(substr($course['instructor_name'],0,1)); ?>
                    </div>
                    <div>
                        <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:4px;"><?php echo e($course['instructor_name']); ?></h2>
                        <div style="color:var(--primary);font-size:14px;margin-bottom:12px;font-weight:500;">Expert Instructor</div>
                        <div style="display:flex;gap:20px;margin-bottom:16px;flex-wrap:wrap;">
                            <div style="font-size:13px;color:var(--text-muted);display:flex;align-items:center;gap:6px;"><i class="fas fa-star" style="color:#fbbf24;"></i> 4.9 Instructor Rating</div>
                            <div style="font-size:13px;color:var(--text-muted);"><i class="fas fa-book me-1;"></i><?php echo $instrCourseCount; ?> Courses</div>
                        </div>
                        <p style="color:var(--text-secondary);font-size:14px;line-height:1.7;margin:0;"><?php echo e($course['instructor_bio'] ?: 'Experienced instructor passionate about sharing knowledge and helping students achieve their goals.'); ?></p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Related Sidebar -->
        <div class="col-lg-5" id="previewSection">
            <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;">Related Courses</h3>
            <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($related as $rel):
                $icon2 = $catIcons[$course['category_name']] ?? '📚';
                $rr = getCourseRating($rel['id']);
            ?>
            <a href="<?php echo BASE_URL; ?>/courses/detail.php?id=<?php echo $rel['id']; ?>" class="course-card" style="flex-direction:row;padding:12px;gap:12px;">
                <div style="width:80px;height:70px;background:linear-gradient(135deg,#0d1a2e,#0a2010);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;overflow:hidden;position:relative;">
                    <?php if (!empty($rel['thumbnail'])): ?>
                        <img src="<?php echo e($rel['thumbnail']); ?>" alt="<?php echo e($rel['title']); ?>" style="width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;">
                    <?php else: ?>
                        <?php echo $icon2; ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:700;margin-bottom:4px;color:var(--text-primary);"><?php echo truncate($rel['title'],45); ?></div>
                    <div style="font-size:12px;color:var(--text-muted);"><?php echo e($rel['instructor_name']); ?></div>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                        <?php echo renderStars($rr['avg']); ?>
                        <span style="font-size:12px;color:#fbbf24;font-weight:700;"><?php echo number_format($rr['avg'],1); ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Lesson Modal -->
<div class="modal fade" id="lessonModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-xl);">
            <div class="modal-header" style="border-color:var(--border);">
                <h5 class="modal-title" id="lessonTitle" style="font-size:16px;font-weight:700;"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="lessonContent" style="padding:24px;color:var(--text-secondary);line-height:1.8;font-size:15px;max-height:65vh;overflow-y:auto;"></div>
        </div>
    </div>
</div>

<style>
.course-tab-link { color: var(--text-muted) !important; border-bottom: 2px solid transparent !important; }
.course-tab-link.active { color: var(--primary) !important; border-bottom-color: var(--primary) !important; }
.course-tab-link:hover { color: var(--text-primary) !important; }
</style>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-pane-custom').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.course-tab-link').forEach(l => l.classList.remove('active'));
    document.getElementById(tab).style.display = 'block';
    document.getElementById('tab-' + tab).classList.add('active');
}

// Set rating input
document.querySelectorAll('.star-input-btn').forEach((btn, idx) => {
    btn.addEventListener('click', () => {
        document.getElementById('ratingInput').value = idx + 1;
        document.querySelectorAll('.star-input-btn').forEach((s,i) => {
            s.querySelector('i').style.color = i <= idx ? '#fbbf24' : 'var(--text-muted)';
        });
    });
});

function openLesson(lessonId, courseId) {
    fetch(`<?php echo BASE_URL; ?>/api/get_lesson.php?id=${lessonId}&course_id=${courseId}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) { showToast('error', data.error); return; }
            document.getElementById('lessonTitle').textContent = data.title;
            let html = '';
            // Render embedded video if available
            if (data.video_url) {
                // Convert youtube watch URLs to embed
                let embedUrl = data.video_url
                    .replace('watch?v=', 'embed/')
                    .replace('youtu.be/', 'www.youtube.com/embed/');
                html += `<div style="position:relative;padding-bottom:56.25%;height:0;margin-bottom:20px;border-radius:10px;overflow:hidden;background:#000;">
                    <iframe src="${embedUrl}" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                </div>`;
            }
            html += data.content;
            document.getElementById('lessonContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('lessonModal')).show();
        });
}

function showPaywall() {
    showToast('info', '🔒 Enroll to access this lesson. <?php echo $course['type']==='premium' ? "Purchase the course to unlock all content." : ""; ?>');
    document.getElementById('tab-curriculum').scrollIntoView({behavior:'smooth'});
}
</script>
