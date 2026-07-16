<?php
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = "Word Match Game";
include __DIR__ . '/../includes/header.php';
?>
<div style="margin-top:70px;min-height:100vh;padding:60px 0;background:var(--gradient-hero);">
<div class="container">
    <div style="text-align:center;margin-bottom:40px;">
        <div class="section-tag mx-auto" style="display:inline-flex;"><i class="fas fa-gamepad"></i> Game-Based Learning</div>
        <h1 style="font-size:2.2rem;font-weight:900;margin-top:16px;">🎮 Tech Term <span class="text-gradient">Word Match</span></h1>
        <p style="color:var(--text-muted);max-width:480px;margin:12px auto 0;">Match each term with its correct definition. All pairs must be matched. Fastest wins!</p>
    </div>

    <div style="max-width:860px;margin:0 auto;">
        <!-- Controls -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;gap:20px;">
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:10px 18px;text-align:center;">
                    <div style="font-size:22px;font-weight:800;color:var(--primary);" id="scoreDisplay">0</div>
                    <div style="font-size:11px;color:var(--text-muted);">Score</div>
                </div>
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:10px 18px;text-align:center;">
                    <div style="font-size:22px;font-weight:800;color:var(--secondary);" id="timerDisplay">60</div>
                    <div style="font-size:11px;color:var(--text-muted);">Seconds</div>
                </div>
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:10px 18px;text-align:center;">
                    <div style="font-size:22px;font-weight:800;color:#f59e0b;" id="pairsDisplay">0/0</div>
                    <div style="font-size:11px;color:var(--text-muted);">Matched</div>
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button onclick="startGame()" class="btn-primary-custom" style="padding:10px 22px;font-size:14px;" id="startBtn">
                    <i class="fas fa-play"></i> Start Game
                </button>
                <button onclick="resetGame()" class="btn-outline-custom" style="padding:10px 16px;font-size:14px;">
                    <i class="fas fa-redo"></i>
                </button>
            </div>
        </div>

        <!-- Game Board -->
        <div style="display:none;grid-template-columns:1fr 1fr;gap:16px;" id="gameBoard">
            <div>
                <h3 style="font-size:14px;font-weight:700;color:var(--text-muted);margin-bottom:12px;text-transform:uppercase;letter-spacing:1px;">📌 Terms</h3>
                <div id="termsList" style="display:flex;flex-direction:column;gap:8px;"></div>
            </div>
            <div>
                <h3 style="font-size:14px;font-weight:700;color:var(--text-muted);margin-bottom:12px;text-transform:uppercase;letter-spacing:1px;">💡 Definitions</h3>
                <div id="defsList" style="display:flex;flex-direction:column;gap:8px;"></div>
            </div>
        </div>

        <!-- Score Screen -->
        <div id="gameMessage" style="display:none;text-align:center;padding:48px 24px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-xl);margin-top:20px;">
            <div id="msgEmoji" style="font-size:64px;margin-bottom:16px;"></div>
            <h2 id="msgTitle" style="font-size:1.8rem;font-weight:900;margin-bottom:8px;color:#fff;"></h2>
            <div style="display:flex;justify-content:center;gap:24px;margin:20px 0;flex-wrap:wrap;">
                <div style="background:var(--bg-input);border:1px solid var(--border);border-radius:12px;padding:16px 24px;min-width:100px;">
                    <div id="finalScore" style="font-size:28px;font-weight:900;color:var(--primary);"></div>
                    <div style="font-size:12px;color:#9ca3af;margin-top:4px;">Points</div>
                </div>
                <div style="background:var(--bg-input);border:1px solid var(--border);border-radius:12px;padding:16px 24px;min-width:100px;">
                    <div id="finalMatched" style="font-size:28px;font-weight:900;color:#f59e0b;"></div>
                    <div style="font-size:12px;color:#9ca3af;margin-top:4px;">Matched</div>
                </div>
                <div style="background:var(--bg-input);border:1px solid var(--border);border-radius:12px;padding:16px 24px;min-width:100px;">
                    <div id="finalPct" style="font-size:28px;font-weight:900;color:var(--secondary);"></div>
                    <div style="font-size:12px;color:#9ca3af;margin-top:4px;">Accuracy</div>
                </div>
            </div>
            <p id="msgText" style="color:#9ca3af;margin-bottom:12px;font-size:14px;"></p>
            <div id="saveStatus" style="margin-bottom:20px;font-size:13px;color:#9ca3af;"></div>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <button onclick="startGame()" class="btn-primary-custom"><i class="fas fa-redo"></i> Play Again</button>
                <a href="<?php echo BASE_URL; ?>/dashboard/student.php?tab=quizzes" class="btn-outline-custom"><i class="fas fa-chart-bar"></i> My Results</a>
                <a href="<?php echo BASE_URL; ?>/quiz/index.php" class="btn-outline-custom"><i class="fas fa-brain"></i> Take a Quiz</a>
            </div>
        </div>
    </div>
</div>
</div>

<script>
const IS_LOGGED    = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
const BASE_URL     = '<?php echo BASE_URL; ?>';
const GAME_QUIZ_ID = 3;

const PAIRS = [
    { term: 'HTML',          def: 'Standard markup language for creating web pages' },
    { term: 'CSS',           def: 'Language used for styling and layout of web pages' },
    { term: 'JavaScript',    def: 'Programming language that adds interactivity to web pages' },
    { term: 'API',           def: 'Set of protocols that allow different software apps to communicate' },
    { term: 'Database',      def: 'Organized collection of structured data stored electronically' },
    { term: 'Algorithm',     def: 'Step-by-step set of instructions to solve a problem' },
    { term: 'Framework',     def: 'Reusable set of libraries and tools for building software' },
    { term: 'Variable',      def: 'Named storage location that holds a value in programming' },
    { term: 'Function',      def: 'Reusable block of code that performs a specific task' },
    { term: 'Array',         def: 'Data structure that stores an ordered collection of elements' },
    { term: 'Loop',          def: 'Programming construct that repeats a block of code' },
    { term: 'Boolean',       def: 'Data type with only two values: true or false' },
    { term: 'Bug',           def: 'An error or flaw in a computer program' },
    { term: 'Git',           def: 'Distributed version control system for tracking code changes' },
    { term: 'Responsive',    def: 'Design that adapts layout to fit different screen sizes' },
];

let selectedTerm  = null;
let selectedDef   = null;
let matchedPairs  = 0;
let totalPairs    = 0;
let score         = 0;
let timeLeft      = 60;
let timerInt      = null;
let gameActive    = false;
let currentPairs  = [];

function shuffle(arr) { return [...arr].sort(() => Math.random() - 0.5); }

function startGame() {
    clearInterval(timerInt);
    document.getElementById('startBtn').style.display = 'none';
    document.getElementById('gameMessage').style.display = 'none';
    document.getElementById('gameBoard').style.display = 'grid';
    currentPairs  = shuffle(PAIRS).slice(0, 6);
    totalPairs    = currentPairs.length;
    matchedPairs  = 0;
    score         = 0;
    timeLeft      = 60;
    gameActive    = true;
    selectedTerm  = null;
    selectedDef   = null;

    document.getElementById('scoreDisplay').textContent  = '0';
    document.getElementById('pairsDisplay').textContent  = `0/${totalPairs}`;
    document.getElementById('timerDisplay').textContent  = '60';
    document.getElementById('timerDisplay').style.color  = 'var(--secondary)';

    renderCards();

    timerInt = setInterval(() => {
        timeLeft--;
        document.getElementById('timerDisplay').textContent = timeLeft;
        if (timeLeft <= 10) document.getElementById('timerDisplay').style.color = '#ef4444';
        if (timeLeft <= 0) { clearInterval(timerInt); endGame(false); }
    }, 1000);
}

function renderCards() {
    const terms = shuffle(currentPairs.map(p => p.term));
    const defs  = shuffle(currentPairs.map(p => p.def));

    document.getElementById('termsList').innerHTML = terms.map(t => `
        <div class="game-card" id="t-${t.replace(/\s/g,'_')}" data-type="term" data-value="${t.replace(/"/g,'&quot;')}">
            <i class="fas fa-code" style="color:var(--primary);margin-right:8px;font-size:13px;"></i>${t}
        </div>
    `).join('');

    document.getElementById('defsList').innerHTML = defs.map((d, i) => `
        <div class="game-card" id="d-${i}" data-type="def" data-value="${d.replace(/"/g,'&quot;')}">
            ${d}
        </div>
    `).join('');

    // Attach click listeners after rendering
    document.querySelectorAll('#termsList .game-card, #defsList .game-card').forEach(card => {
        card.addEventListener('click', function() {
            if (!gameActive || this.classList.contains('matched')) return;
            const type  = this.dataset.type;
            const value = this.dataset.value;

            if (type === 'term') {
                if (selectedTerm) selectedTerm.el.classList.remove('selected');
                selectedTerm = { value, el: this };
                this.classList.add('selected');
            } else {
                if (selectedDef) selectedDef.el.classList.remove('selected');
                selectedDef = { value, el: this };
                this.classList.add('selected');
            }

            if (selectedTerm && selectedDef) checkMatch();
        });
    });
}

function checkMatch() {
    const pair = currentPairs.find(p => p.term === selectedTerm.value && p.def === selectedDef.value);

    if (pair) {
        // Correct match
        selectedTerm.el.classList.remove('selected');
        selectedDef.el.classList.remove('selected');
        selectedTerm.el.classList.add('matched');
        selectedDef.el.classList.add('matched');
        selectedTerm.el.innerHTML += ' <i class="fas fa-check" style="color:#fff;margin-left:6px;"></i>';
        selectedDef.el.innerHTML += ' <i class="fas fa-check" style="color:#fff;margin-left:6px;"></i>';

        matchedPairs++;
        score += 10 + Math.floor(timeLeft / 6);
        document.getElementById('scoreDisplay').textContent  = score;
        document.getElementById('pairsDisplay').textContent  = `${matchedPairs}/${totalPairs}`;

        if (matchedPairs === totalPairs) {
            clearInterval(timerInt);
            setTimeout(() => endGame(true), 600);
        }
    } else {
        // Wrong match
        selectedTerm.el.classList.add('wrong');
        selectedDef.el.classList.add('wrong');
        score = Math.max(0, score - 2);
        document.getElementById('scoreDisplay').textContent = score;
        const wrongTerm = selectedTerm;
        const wrongDef  = selectedDef;
        selectedTerm = null;
        selectedDef  = null;
        setTimeout(() => {
            wrongTerm.el.classList.remove('wrong', 'selected');
            wrongDef.el.classList.remove('wrong', 'selected');
        }, 700);
    }

    selectedTerm = null;
    selectedDef  = null;
}

function endGame(won) {
    gameActive = false;
    clearInterval(timerInt);

    const pct    = Math.round((matchedPairs / totalPairs) * 100);
    const passed = won ? 1 : 0;

    document.getElementById('gameBoard').style.display   = 'none';
    document.getElementById('gameMessage').style.display = 'block';
    document.getElementById('startBtn').style.display    = 'inline-flex';

    document.getElementById('finalScore').textContent   = score;
    document.getElementById('finalMatched').textContent = `${matchedPairs}/${totalPairs}`;
    document.getElementById('finalPct').textContent     = pct + '%';

    if (won) {
        document.getElementById('msgEmoji').textContent = '🏆';
        document.getElementById('msgTitle').textContent = 'All Matched! Well done!';
        document.getElementById('msgText').textContent  = `You matched all ${totalPairs} pairs with ${timeLeft} seconds left!`;
    } else {
        document.getElementById('msgEmoji').textContent = '⏰';
        document.getElementById('msgTitle').textContent = "Time's Up!";
        document.getElementById('msgText').textContent  = `You matched ${matchedPairs} out of ${totalPairs} pairs. Keep practicing!`;
    }

    saveResult(pct, passed);
}

function saveResult(pct, passed) {
    if (!IS_LOGGED) {
        document.getElementById('saveStatus').innerHTML =
            '<i class="fas fa-info-circle"></i> <a href="' + BASE_URL + '/login.php" style="color:var(--primary);">Log in</a> to save your results.';
        return;
    }

    document.getElementById('saveStatus').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving result...';

    fetch(BASE_URL + '/api/save_quiz_result.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            quiz_id:         GAME_QUIZ_ID,
            score:           matchedPairs,
            total_questions: totalPairs,
            percentage:      pct,
            passed:          passed
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('saveStatus').innerHTML =
                '<i class="fas fa-check-circle" style="color:var(--primary);"></i> Result saved to your dashboard!';
        } else {
            document.getElementById('saveStatus').innerHTML =
                '<i class="fas fa-exclamation-circle" style="color:#f87171;"></i> Could not save result.';
        }
    })
    .catch(() => {
        document.getElementById('saveStatus').innerHTML =
            '<i class="fas fa-exclamation-circle" style="color:#f87171;"></i> Could not save result.';
    });
}

function resetGame() { startGame(); }
</script>

<style>
.game-card {
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-md);
    padding: 14px 16px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
    font-weight: 600;
    user-select: none;
    display: flex;
    align-items: center;
    color: #fff;
}
.game-card:hover { border-color: rgba(34,197,94,0.4); background: rgba(34,197,94,0.04); transform: translateY(-1px); }
.game-card.selected { border-color: var(--primary); background: rgba(34,197,94,0.08); box-shadow: 0 0 0 2px rgba(34,197,94,0.2); }
.game-card.matched { border-color: var(--primary); background: rgba(34,197,94,0.15); color: var(--primary); cursor: default; pointer-events: none; }
.game-card.wrong { border-color: #ef4444; background: rgba(239,68,68,0.08); animation: shake 0.4s ease; }
@keyframes shake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-6px)} 75%{transform:translateX(6px)} }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
