// ============================================
// EDUCORE - Quiz Engine (Adaptive MCQ)
// ============================================
'use strict';

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

let currentIndex = 0;
let score        = 0;
let answered     = false;
let timerSec     = 0;
let timerInterval;
let difficulty   = 'easy'; // for adaptive
let correctStreak = 0;
let wrongStreak   = 0;

// Adaptive: filter questions by difficulty
let easyQ   = QUESTIONS.filter(q => q.difficulty === 'easy');
let mediumQ = QUESTIONS.filter(q => q.difficulty === 'medium');
let hardQ   = QUESTIONS.filter(q => q.difficulty === 'hard');

// Build adaptive queue starting with easy
let quizQueue = IS_ADAPTIVE
    ? [...easyQ.slice(0,2), ...mediumQ.slice(0,2), ...hardQ.slice(0,2)]
    : QUESTIONS;

// Shuffle
quizQueue = quizQueue.sort(() => Math.random() - 0.5);

function startTimer() {
    timerInterval = setInterval(() => {
        timerSec++;
        const m = String(Math.floor(timerSec / 60)).padStart(2,'0');
        const s = String(timerSec % 60).padStart(2,'0');
        const el = document.getElementById('timer');
        if (el) el.textContent = `${m}:${s}`;
    }, 1000);
}

function renderQuestion() {
    if (currentIndex >= quizQueue.length) {
        showScore();
        return;
    }

    answered = false;
    const q   = quizQueue[currentIndex];
    const pct = Math.round((currentIndex / quizQueue.length) * 100);
    document.getElementById('quizProgress').style.width = pct + '%';

    const opts = [
        { key: 'A', text: escapeHtml(q.option_a) },
        { key: 'B', text: escapeHtml(q.option_b) },
        { key: 'C', text: escapeHtml(q.option_c) },
        { key: 'D', text: escapeHtml(q.option_d) },
    ];

    const diffColors = { easy:'diff-easy', medium:'diff-medium', hard:'diff-hard' };
    const diffLabels  = { easy:'🟢 Easy', medium:'🟡 Medium', hard:'🔴 Hard' };

    document.getElementById('quizBody').innerHTML = `
        <div>
            <div class="quiz-question-num">Question ${currentIndex + 1} of ${quizQueue.length}</div>
            <div class="quiz-question">${escapeHtml(q.question)}</div>
            <div class="quiz-difficulty ${diffColors[q.difficulty] || 'diff-easy'}">
                ${diffLabels[q.difficulty] || '🟢 Easy'}
                ${IS_ADAPTIVE ? '<span style="margin-left:8px;font-size:10px;opacity:0.7;">· Adaptive Mode</span>' : ''}
            </div>
            <div class="quiz-options" id="optionList">
                ${opts.map(o => `
                    <div class="quiz-option" id="opt-${o.key}" onclick="selectAnswer('${o.key}','${q.correct_option}',\`${(q.explanation||'').replace(/`/g,"'")}\`)">
                        <div class="option-bullet">${o.key}</div>
                        <div>${o.text}</div>
                    </div>
                `).join('')}
            </div>
            <div id="explanationBox"></div>
            <div class="quiz-controls" id="quizControls" style="display:none;">
                <div style="font-size:13px;color:var(--text-muted);">${currentIndex + 1} / ${quizQueue.length} answered</div>
                <button onclick="nextQuestion()" class="btn-primary-custom" style="padding:10px 28px;">
                    ${currentIndex + 1 < quizQueue.length ? 'Next Question <i class="fas fa-arrow-right"></i>' : 'See Results 🎉'}
                </button>
            </div>
        </div>
    `;
}

function selectAnswer(chosen, correct, explanation) {
    if (answered) return;
    answered = true;

    const isCorrect = chosen === correct;
    if (isCorrect) {
        score++;
        correctStreak++;
        wrongStreak = 0;
    } else {
        wrongStreak++;
        correctStreak = 0;
    }

    // Adaptive difficulty adjustment
    if (IS_ADAPTIVE) {
        if (correctStreak >= 2 && difficulty !== 'hard') {
            difficulty = difficulty === 'easy' ? 'medium' : 'hard';
            correctStreak = 0;
            // Insert harder question next if available
            const pool = difficulty === 'medium' ? mediumQ : hardQ;
            const unused = pool.filter(q => !quizQueue.slice(0, currentIndex+1).find(x => x.id === q.id));
            if (unused.length > 0 && currentIndex + 2 < quizQueue.length) {
                quizQueue.splice(currentIndex + 1, 0, unused[Math.floor(Math.random()*unused.length)]);
            }
        } else if (wrongStreak >= 2 && difficulty !== 'easy') {
            difficulty = difficulty === 'hard' ? 'medium' : 'easy';
            wrongStreak = 0;
        }
    }

    // Highlight correct/wrong
    document.querySelectorAll('.quiz-option').forEach(opt => {
        opt.style.pointerEvents = 'none';
        const key = opt.id.split('-')[1];
        if (key === correct) opt.classList.add('correct');
        else if (key === chosen && !isCorrect) opt.classList.add('wrong');
    });

    // Explanation
    if (explanation) {
        document.getElementById('explanationBox').innerHTML = `
            <div class="quiz-explanation">
                <i class="fas fa-lightbulb me-1"></i>
                <strong>Explanation:</strong> ${explanation}
            </div>
        `;
    }

    document.getElementById('quizControls').style.display = 'flex';
}

function nextQuestion() {
    currentIndex++;
    // Limit adaptive queue to reasonable length
    if (IS_ADAPTIVE && quizQueue.length > 10) {
        quizQueue = quizQueue.slice(0, 10);
    }
    renderQuestion();
}

function showScore() {
    clearInterval(timerInterval);
    document.getElementById('quizProgress').style.width = '100%';
    document.getElementById('quizBody').style.display   = 'none';
    document.getElementById('scoreScreen').style.display = 'block';

    const total   = quizQueue.length;
    const pct     = Math.round((score / total) * 100);
    const passed  = pct >= PASS_PCT;
    const timeStr = document.getElementById('timer')?.textContent || '00:00';

    document.getElementById('scoreNum').textContent     = `${score}/${total}`;
    document.getElementById('scoreHeading').textContent = passed ? '🎉 Congratulations! You Passed!' : '😔 Not quite — try again!';
    document.getElementById('scoreSubtext').innerHTML   = `
        You scored <strong style="color:${passed?'var(--primary)':'#ef4444'};">${pct}%</strong>
        (pass mark: ${PASS_PCT}%) in ${timeStr}.
        ${IS_ADAPTIVE ? '<br><span style="font-size:13px;color:var(--text-muted);">Final difficulty reached: <strong>'+difficulty+'</strong></span>' : ''}
    `;

    document.getElementById('scoreActions').innerHTML = `
        <button onclick="location.reload()" class="btn-outline-custom" style="padding:10px 24px;">
            <i class="fas fa-redo"></i> Retry Quiz
        </button>
        <a href="${BASE_URL}/quiz/index.php" class="btn-primary-custom" style="padding:10px 24px;">
            <i class="fas fa-list"></i> All Quizzes
        </a>
        ${IS_LOGGED && passed ? `<a href="${BASE_URL}/dashboard/student.php?tab=certificates" class="btn-enroll-sm" style="padding:10px 20px;font-size:14px;"><i class="fas fa-certificate"></i> View Certificates</a>` : ''}
    `;

    // Save result to server if logged in
    if (IS_LOGGED) {
        fetch(`${BASE_URL}/api/save_quiz_result.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ quiz_id: QUIZ_ID, score, total_questions: total, percentage: pct, passed: passed ? 1 : 0 })
        }).catch(() => {});
    }

    // Confetti for pass
    if (passed) launchConfetti();
}

function launchConfetti() {
    const colors = ['#22c55e','#3b82f6','#8b5cf6','#fbbf24','#ec4899'];
    for (let i = 0; i < 80; i++) {
        const el = document.createElement('div');
        el.style.cssText = `
            position:fixed;top:-10px;left:${Math.random()*100}vw;width:8px;height:8px;
            background:${colors[Math.floor(Math.random()*colors.length)]};
            border-radius:${Math.random()>0.5?'50%':'2px'};
            animation:confettiFall ${1.5+Math.random()*2}s ease-in forwards;
            animation-delay:${Math.random()*0.5}s;z-index:9999;pointer-events:none;
        `;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }
    const style = document.createElement('style');
    style.textContent = `@keyframes confettiFall{to{transform:translateY(110vh) rotate(720deg);opacity:0;}}`;
    document.head.appendChild(style);
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    startTimer();
    renderQuestion();
});
