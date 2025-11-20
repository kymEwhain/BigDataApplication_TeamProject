<?php
session_start();
require_once '../sql/db.php';

$mysqli = connectDB();

// 예시: ?rest_id=1
$rest_id = isset($_GET['rest_id']) ? (int)$_GET['rest_id'] : 1;

// 리뷰 존재 여부 확인
$has_review = false;

if (!empty($_SESSION['user_id'])) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM Review WHERE user_id = ? AND rest_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $rest_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $has_review = ($row && $row['cnt'] > 0);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Restaurant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="top-nav">
    <span>/restaurant.php</span>
</div>

<div class="page-wrapper">
    <div class="card">
        <h1 class="card-title">식당 상세 (예시)</h1>

        <?php if (empty($_SESSION['user_id'])): ?>
            <p>리뷰를 작성하려면 <a href="login.php">로그인</a> 해주세요.</p>
        <?php else: ?>
            <?php if ($has_review): ?>
                <a href="my_review.php?rest_id=<?= $rest_id ?>" class="btn">Do, Fix Review</a>
            <?php else: ?>
                <a href="review_new.php?rest_id=<?= $rest_id ?>" class="btn">Do, Fix Review</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>