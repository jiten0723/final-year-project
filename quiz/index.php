<?php
// ============================================
// EDUCORE - Quiz System (Adaptive MCQ)
// ============================================
require_once __DIR__ . '/../includes/auth.php';

$db      = getDB();
$quizId  = (int)($_GET['id'] ?? 0);
$pageTitle = "Quiz";

// Fetch all quizzes if no ID
$allQuizzes = $db->query("
    SELECT q.*, co.title as course_title, COUNT(qq.id) as question_count
    FROM quizzes q LEFT JOIN courses co ON co.id=q.course_id
    LEFT JOIN quiz_questions qq ON qq.quiz_id=q.id
    GROUP BY q.id ORDER BY q.id
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:70px;min-height:100vh;padding:60px 0;background:var(--gradient-hero);">
<div class="container">

<?php if (!$quizId): ?>
<!-- Quiz Selection Page -->
<div style="text-align:center;margin-bottom:48px;">
    <div class="section-tag mx-auto" style="display:inline-flex;"><i class="fas fa-brain"></i> Knowledge Check</div>
    <h1 style="font-size:2.5rem;font-weight:900;margin-top:16px;">Test Your <span class="text-gradient">Knowledge</span></h1>
    <p style="color:var(--text-muted);font-size:16px;max-width:500px;margin:0 auto;">Challenge yourself with our MCQ quizzes. Our AI adaptive system adjusts difficulty based on your performance.</p>
</div>

<div class="row g-4 justify-content-center">
<?php foreach ($allQuizzes as $q): ?>
<div class="col-md-6 col-lg-4">
    <div class="stat-card h-100" style="text-align:center;cursor:pointer;transition:all 0.3s;" onclick="window.location='?id=<?php echo $q['id']; ?>'" onmouseover="this.style.transform='translateY(-6px)';this.style.borderColor='rgba(34,197,94,0.4)'" onmouseout="this.style.transform='';this.style.borderColor='var(--border)'">
        <div style="font-size:52px;margin-bottom:16px;"><?php echo $q['is_adaptive']?'🧠':'📝'; ?></div>
        <h3 style="font-size:17px;font-weight:800;margin-bottom:8px;"><?php echo e($q['title']); ?></h3>
        <p style="color:var(--text-muted);font-size:13px;line-height:1.6;margin-bottom:20px;"><?php echo e($q['description']); ?></p>
        <div style="display:flex;justify-content:center;gap:20px;margin-bottom:20px;">
            <div style="text-align:center;"><div style="font-size:20px;font-weight:800;color:var(--primary);"><?php echo $q['question_count']; ?></div><div style="font-size:11px;color:var(--text-muted);">Questions</div></div>
            <div style="text-align:center;"><div style="font-size:20px;font-weight:800;color:var(--secondary);"><?php echo $q['pass_percentage']; ?>%</div><div style="font-size:11px;color:var(--text-muted);">Pass Mark</div></div>
        </div>
        <?php if ($q['is_adaptive']): ?>
            <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.3);border-radius:50px;padding:4px 12px;font-size:11px;color:#a78bfa;margin-bottom:16px;">
                <i class="fas fa-robot"></i> AI Adaptive
            </div><br>
        <?php endif; ?>
        <span class="btn-enroll-sm">Start Quiz <i class="fas fa-arrow-right"></i></span>
    </div>
</div>
<?php endforeach; ?>

<!-- General Practice Quiz Card -->
<div class="col-md-6 col-lg-4">
    <div class="stat-card h-100" style="text-align:center;cursor:pointer;border-style:dashed;" onclick="window.location='?id=3'" onmouseover="this.style.transform='translateY(-6px)'" onmouseout="this.style.transform=''">
        <div style="font-size:52px;margin-bottom:16px;">🎮</div>
        <h3 style="font-size:17px;font-weight:800;margin-bottom:8px;">Word Match Game</h3>
        <p style="color:var(--text-muted);font-size:13px;margin-bottom:20px;">Match tech terms with definitions in this fun interactive game.</p>
        <span class="btn-enroll-sm" style="background:rgba(139,92,246,0.1);color:#a78bfa;border:1px solid rgba(139,92,246,0.3);" onclick="event.stopPropagation();window.location='<?php echo BASE_URL; ?>/quiz/game.php'">Play Game 🎮</span>
    </div>
</div>
</div>

<?php else:
// Load specific quiz
$quiz = $db->prepare("SELECT * FROM quizzes WHERE id=?");
$quiz->execute([$quizId]);
$quiz = $quiz->fetch();

if (!$quiz) { echo '<div class="alert-custom alert-error">Quiz not found.</div>'; include __DIR__.'/../includes/footer.php'; exit(); }

$questions = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY RAND()");
$questions->execute([$quizId]);
$questions = $questions->fetchAll();
?>

<div class="quiz-container animate-fade-up">
    <div class="quiz-header">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <a href="?" style="font-size:13px;color:var(--text-muted);display:flex;align-items:center;gap:6px;"><i class="fas fa-arrow-left"></i> All Quizzes</a>
            <div style="display:flex;align-items:center;gap:8px;font-size:14px;color:var(--text-muted);">
                <div id="timerDisplay" style="display:flex;align-items:center;gap:6px;background:rgba(255,255,255,0.05);padding:5px 12px;border-radius:50px;">
                    <i class="fas fa-clock" style="color:var(--primary);"></i>
                    <span id="timer">00:00</span>
                </div>
            </div>
        </div>
        <h2 style="font-size:1.4rem;font-weight:900;margin-bottom:4px;"><?php echo e($quiz['title']); ?></h2>
        <?php if ($quiz['is_adaptive']): ?>
        <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.3);border-radius:50px;padding:4px 12px;font-size:11px;color:#a78bfa;margin-bottom:12px;">
            <i class="fas fa-robot"></i> AI Adaptive — difficulty adjusts with your answers
        </div>
        <?php endif; ?>
        <div style="font-size:13px;color:var(--text-muted);">Pass mark: <?php echo $quiz['pass_percentage']; ?>% · <?php echo count($questions); ?> questions</div>
    </div>

    <div class="quiz-progress-bar">
        <div class="quiz-progress-fill" id="quizProgress" style="width:0%;"></div>
    </div>

    <!-- Quiz Questions (rendered by JS) -->
    <div id="quizBody"></div>

    <!-- Score Screen (hidden initially) -->
    <div id="scoreScreen" style="display:none;" class="quiz-score-display">
        <div class="score-circle">
            <div class="score-number" id="scoreNum">0</div>
            <div class="score-label">Score</div>
        </div>
        <h2 id="scoreHeading" style="font-size:1.5rem;font-weight:900;margin-bottom:8px;"></h2>
        <p id="scoreSubtext" style="color:var(--text-muted);margin-bottom:24px;"></p>
        <div id="scoreActions" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;"></div>
    </div>
</div>

<!-- Questions Data -->
<script>
const QUESTIONS = <?php echo json_encode($questions); ?>;
const QUIZ_ID   = <?php echo $quiz['id']; ?>;
const PASS_PCT  = <?php echo $quiz['pass_percentage']; ?>;
const IS_LOGGED = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
const IS_ADAPTIVE = <?php echo $quiz['is_adaptive'] ? 'true' : 'false'; ?>;
const BASE_URL  = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/quiz.js"></script>
<?php endif; ?>

</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
