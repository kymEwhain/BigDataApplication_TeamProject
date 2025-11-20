<?php
session_start();
require_once '../sql/db.php';

$mysqli = connectDB();

// 로그인 체크
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '사용자';

$rest_id = isset($_GET['rest_id']) ? (int)$_GET['rest_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rest_id = (int)($_POST['rest_id'] ?? 0);
}
if ($rest_id <= 0) {
    exit('유효한 식당 ID가 필요합니다.');
}

// 이미 리뷰 있음? → my_review로 이동
$stmt = $mysqli->prepare("SELECT review_id FROM Review WHERE user_id = ? AND rest_id = ?");
$stmt->bind_param("ii", $user_id, $rest_id);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();

if ($existing && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my_review.php?rest_id=$rest_id");
    exit;
}

$errors = [];
$comment = '';
$score = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['comment'] ?? '');
    $score   = (int)($_POST['score'] ?? 0);

    if ($score < 1 || $score > 5) $errors[] = "평점은 1~5점이어야 합니다.";
    if ($comment === '') $errors[] = "리뷰 내용을 입력해주세요.";

    if (empty($errors)) {
        $stmt = $mysqli->prepare("INSERT INTO Review (user_id, rest_id, score, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $rest_id, $score, $comment);
        $stmt->execute();

        header("Location: RestaurantDetail.php?rest_id=$rest_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>새 리뷰 작성</title>
    <link rel="stylesheet" href="../css/log_reg_rev_style.css">
</head>
<body>

<div class="page-wrapper">
    <div class="card card-wide">
        <div class="review-header">
            <div class="review-username">
                <?= htmlspecialchars($user_name) ?>님의 리뷰
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="review_new.php">
            <input type="hidden" name="rest_id" value="<?= $rest_id ?>">
            <input type="hidden" name="score" id="score-input" value="<?= $score ?>">

            <div class="star-rating" id="star-rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?= $i <= $score ? 'selected' : 'unselected' ?>" data-value="<?= $i ?>">★</span>
                <?php endfor; ?>
            </div>

            <textarea name="comment" class="textarea" placeholder="review" required><?= htmlspecialchars($comment) ?></textarea>

            <div style="text-align:right;">
                <button type="submit" class="btn" style="width:200px;">작성 완료</button>
            </div>
        </form>
    </div>
</div>

<script>
const stars = document.querySelectorAll('#star-rating .star');
const scoreInput = document.getElementById('score-input');

stars.forEach(star => {
    star.addEventListener('click', () => {
        const v = parseInt(star.dataset.value);
        scoreInput.value = v;

        stars.forEach(s => {
            const sv = parseInt(s.dataset.value);
            if (sv <= v) {
                s.classList.add('selected');
                s.classList.remove('unselected');
            } else {
                s.classList.add('unselected');
                s.classList.remove('selected');
            }
        });
    });
});
</script>

</body>
</html>