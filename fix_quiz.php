<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();

// Fetch all quiz questions
$questions = $db->query("SELECT * FROM quiz_questions")->fetchAll();

$count = 0;
foreach ($questions as $q) {
    $db->prepare("UPDATE quiz_questions SET
        question    = ?,
        option_a    = ?,
        option_b    = ?,
        option_c    = ?,
        option_d    = ?,
        explanation = ?
        WHERE id = ?
    ")->execute([
        htmlspecialchars_decode(html_entity_decode($q['question'],    ENT_QUOTES|ENT_HTML5, 'UTF-8')),
        htmlspecialchars_decode(html_entity_decode($q['option_a'],    ENT_QUOTES|ENT_HTML5, 'UTF-8')),
        htmlspecialchars_decode(html_entity_decode($q['option_b'],    ENT_QUOTES|ENT_HTML5, 'UTF-8')),
        htmlspecialchars_decode(html_entity_decode($q['option_c'],    ENT_QUOTES|ENT_HTML5, 'UTF-8')),
        htmlspecialchars_decode(html_entity_decode($q['option_d'],    ENT_QUOTES|ENT_HTML5, 'UTF-8')),
        htmlspecialchars_decode(html_entity_decode($q['explanation'], ENT_QUOTES|ENT_HTML5, 'UTF-8')),
        $q['id']
    ]);
    $count++;
}

echo "<p>Updated $count questions. All HTML tags stored as plain text.</p>";
echo "<p><a href='http://localhost/edu-core/quiz/index.php'>Go to Quiz</a> | ";
echo "<a href='http://localhost/edu-core/'>Go to Home</a></p>";
?>
