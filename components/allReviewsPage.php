<?php
session_start();
$rest_id = $_GET['rest_id'] ?? 1; // 기본값 1

include '../sql/db.php'; // 데이터베이스 연결 파일
$mysqli = connectDB();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Reviews</title>
    <link rel="stylesheet" href="../css/restaurant_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: "Noto Sans KR", sans-serif;
            background-color: #e2e9fb;
            margin: 0;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        .review-container {
            background: #fff;
            border-radius: 12px;
            width: 700px;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: transparent;
            color: black;
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 0px 30px;
        }

        .btn {
            background-color: #4a9df8;
            color: white;
            font-weight: bold;
            width: 60px;
            padding: 5px;
        }

        .review-card {
            padding: 0px 30px 20px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            max-height: 80vh; /* 화면의 80% 높이 */
            overflow-y: auto; /* 카드 안에서만 스크롤 */
        }

        .review-card::-webkit-scrollbar {
            width: 13px;
        }

        .review-card::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 12px;
            border: 2px solid white;
        }

        .review-card::-webkit-scrollbar-track {
            background: #f5f5f5;
            border-radius: 10px;
        }

        .review-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .stars i {
            color: gold;
            font-size: 1.2rem;
            margin-right: 2px;
        }

        .review-text {
            color: #ff6600;
            font-weight: bold;
        }

        .review-date {
            font-size: 0.8rem;
            color: #666;
            text-align: right;
            margin-top: 4px;
        }

        h2 {
            margin-bottom: 20px;
        }

        /* No reviews message */
        .no-reviews {
            text-align: center;
            padding: 20px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="review-container">
        <div class="header">
            <h2>All Reviews</h2>
            <button class="btn back-button" onclick="location.href='restaurant.php?rest_id=<?= $rest_id ?>'">Back</button>
        </div>
        <div class="review-card">
            <?php
            // 모든 리뷰 가져오기
            $stmt = $mysqli->prepare("SELECT * FROM Review WHERE rest_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $rest_id);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $stars = str_repeat('<i class="bi bi-star-fill"></i>', $row['score']);
                    $date = substr($row['created_at'], 0, 10);
                    $comment = htmlspecialchars($row['comment']);
                    echo "
                        <div class='review-item'>
                            <div class='stars'>$stars</div>
                            <div class='review-text'>$comment</div>
                            <div class='review-date'>$date</div>
                        </div>
                    ";
                }
            } else {
                echo "<div class='no-reviews'>No reviews yet</div>";
            }

            $stmt->close();
            $mysqli->close();
            ?>
        </div>
    </div>
</body>
</html>
