<?php
// ============================================
// EDUCORE - AI Course Recommendations API
// Signals used:
//   1. Category affinity    (enrolled + quiz categories)
//   2. Level progression    (next level up from current)
//   3. Quiz performance     (recommend matching-topic courses)
//   4. Popularity           (enrollment count + rating)
//   5. Diversity            (avoid same instructor twice)
// ============================================
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$db     = getDB();
$userId = (int)($_GET['user_id'] ?? 0);

// ── 1. Courses already enrolled ──────────────────────────────────────────────
$enrolledIds = [];
$enrolledCategories = [];
$enrolledLevels = [];

if ($userId) {
    $stmt = $db->prepare("
        SELECT e.course_id, co.category_id, co.level
        FROM enrollments e
        JOIN courses co ON co.id = e.course_id
        WHERE e.user_id = ?
    ");
    $stmt->execute([$userId]);
    foreach ($stmt->fetchAll() as $row) {
        $enrolledIds[]        = $row['course_id'];
        $enrolledCategories[] = $row['category_id'];
        $enrolledLevels[]     = $row['level'];
    }
    $enrolledCategories = array_unique($enrolledCategories);
    $enrolledLevels     = array_unique($enrolledLevels);
}

// ── 2. Categories from quiz results ──────────────────────────────────────────
$quizCategories = [];
if ($userId) {
    $stmt = $db->prepare("
        SELECT DISTINCT co.category_id
        FROM quiz_results qr
        JOIN quizzes q ON q.id = qr.quiz_id
        JOIN courses co ON co.id = q.course_id
        WHERE qr.user_id = ? AND q.course_id IS NOT NULL
    ");
    $stmt->execute([$userId]);
    $quizCategories = array_column($stmt->fetchAll(), 'category_id');
}

// Combine all category signals
$preferredCategories = array_unique(array_merge($enrolledCategories, $quizCategories));

// ── 3. Level progression logic ────────────────────────────────────────────────
// If student has beginner courses → recommend intermediate too
// If student has intermediate courses → recommend advanced too
$recommendedLevels = ['beginner'];
if (in_array('beginner', $enrolledLevels))     $recommendedLevels[] = 'intermediate';
if (in_array('intermediate', $enrolledLevels)) $recommendedLevels[] = 'advanced';
if (empty($enrolledLevels))                    $recommendedLevels   = ['beginner','intermediate','advanced'];

// ── 4. Fetch all eligible courses with scoring data ──────────────────────────
$excludePlaceholders = $enrolledIds ? implode(',', array_fill(0, count($enrolledIds), '?')) : 'NULL';
$excludeClause       = $enrolledIds ? "AND co.id NOT IN ($excludePlaceholders)" : '';

$sql = "
    SELECT co.*,
           cat.name  AS category_name,
           cat.id    AS cat_id,
           u.name    AS instructor_name,
           AVG(r.rating)          AS avg_rating,
           COUNT(DISTINCT r.id)   AS review_count,
           COUNT(DISTINCT e.id)   AS enroll_count
    FROM courses co
    JOIN users u ON u.id = co.instructor_id
    LEFT JOIN categories cat ON cat.id = co.category_id
    LEFT JOIN reviews r ON r.course_id = co.id
    LEFT JOIN enrollments e ON e.course_id = co.id
    WHERE co.status = 'approved'
    $excludeClause
    GROUP BY co.id
";

$stmt = $db->prepare($sql);
$stmt->execute($enrolledIds ?: []);
$allCourses = $stmt->fetchAll();

// ── 5. Score each course ──────────────────────────────────────────────────────
$catIcons = [
    'Programming' => '💻', 'Design'     => '🎨', 'Business'    => '💼',
    'Music'       => '🎵', 'Photography'=> '📷', 'Marketing'   => '📢',
    'Data Science'=> '📊', 'Personal Dev'=> '🚀',
];

$reasonLabels = [
    'category_match'  => '📂 Matches your interests',
    'level_up'        => '📈 Next step in your path',
    'quiz_match'      => '🧠 Based on your quiz activity',
    'popular'         => '🔥 Trending on EDUCORE',
    'top_rated'       => '⭐ Top rated course',
    'free_pick'       => '🎁 Free course for you',
];

$scored = [];
foreach ($allCourses as $c) {
    $score  = 0;
    $reason = 'popular';

    $catMatch   = in_array($c['cat_id'], $preferredCategories);
    $quizMatch  = in_array($c['cat_id'], $quizCategories);
    $levelMatch = in_array($c['level'],  $recommendedLevels);
    $rating     = (float)($c['avg_rating'] ?? 0);
    $enrolls    = (int)($c['enroll_count'] ?? 0);

    // Category affinity — strongest signal
    if ($catMatch)  { $score += 50; $reason = 'category_match'; }
    if ($quizMatch) { $score += 20; $reason = 'quiz_match'; }

    // Level progression
    if ($levelMatch) { $score += 30; if ($reason === 'popular') $reason = 'level_up'; }

    // Popularity
    $score += min($enrolls * 2, 30);

    // Rating boost
    $score += $rating * 5;
    if ($rating >= 4.5) $reason = 'top_rated';

    // Free course bonus (encourage exploration)
    if ($c['price'] == 0) { $score += 10; if (!$userId) $reason = 'free_pick'; }

    $c['_score']  = $score;
    $c['_reason'] = $reason;
    $scored[]     = $c;
}

// ── 6. Sort by score descending ───────────────────────────────────────────────
usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);

// ── 7. Diversity filter — max 2 per instructor ────────────────────────────────
$instructorCount = [];
$final = [];
foreach ($scored as $c) {
    $iid = $c['instructor_id'];
    if (!isset($instructorCount[$iid])) $instructorCount[$iid] = 0;
    if ($instructorCount[$iid] >= 2) continue;
    $instructorCount[$iid]++;
    $final[] = $c;
    if (count($final) >= 6) break;
}

// ── 8. Format response ────────────────────────────────────────────────────────
$response = array_map(fn($c) => [
    'id'          => (int)$c['id'],
    'title'       => $c['title'],
    'slug'        => $c['slug'],
    'category'    => $c['category_name'] ?? 'General',
    'instructor'  => $c['instructor_name'],
    'price'       => (float)$c['price'],
    'type'        => $c['type'],
    'level'       => $c['level'],
    'rating'      => round((float)($c['avg_rating'] ?? 0), 1),
    'enrolls'     => (int)$c['enroll_count'],
    'icon'        => $catIcons[$c['category_name'] ?? ''] ?? '📚',
    'thumbnail'   => $c['thumbnail'] ?? '',
    'reason'      => $c['_reason'],
    'reason_label'=> $reasonLabels[$c['_reason']] ?? '🔥 Trending on EDUCORE',
    'score'       => round($c['_score'], 1),
], $final);

echo json_encode($response);
