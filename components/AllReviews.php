<!-- 217100 Kim Yumin -->
<?php
session_start();
$rest_id = $_GET['rest_id'] ?? 1; 

include '../sql/db.php';
include_once "../functions/getRestaurantInfo.php";
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
            <?php renderAllReviewsList($rest_id); ?>
        </div> 
</body>
</html>
