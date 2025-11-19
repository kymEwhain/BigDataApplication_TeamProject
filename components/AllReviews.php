<!-- 217100 Kim Yumin -->
<?php
session_start();
$rest_id = $_GET['rest_id'] ?? 1; 

include '../sql/db.php';
$mysqli = connectDB();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Reviews</title>
    <link rel="stylesheet" href="../css/rest-module-ui.css" />
    <link rel="stylesheet" href="../css/all-reviews.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="l-container">
        <div class="header">
            <h2>All Reviews</h2>
            <button class="btn back-btn" onclick="location.href='RestaurantDetail.php?rest_id=<?= $rest_id ?>'">Back</button>
        </div>
        <div class="review-list-block scroll">
            <?php
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
                        <div class='review-list-block__item'>
                            <div class='stars small'>$stars</div>
                            <div class='review-item__text'>$comment</div>
                            <div class='review-item__date'>$date</div>
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
