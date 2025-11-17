<?php
session_start();           
$user_id = $_SESSION['user_id'] ?? null; 
/*
if (!$user_id) {
  header("Location: tempLogin.php");
  exit;
}
*/

$rest_id = $_GET['rest_id'] ?? 1; 
$sort = $_GET['sort'] ?? 'popular';
include_once "../functions/favorite.php";
?>

<!DOCTYPE html>
<html lang="ko">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../css/favorite_style.css" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/solid.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/regular.min.css">
    <title>식당 페이지</title>
    <title>My favorite Restaurants</title>
  </head>
  <body>
    <div class="container">
      <!-- 뒤로 가기 버튼 -->
      <div style="display:flex; flex-direction: row; justify-content: space-between;">
          <button class="btn back-btn" onclick="history.back()">
            <i class="bi bi-arrow-left"></i> Back
          </button>
          <h3 style="padding: 20px;"> my Favorite Restaurants </h3>
          <button class="btn favoriteList-btn shadow invisible" onclick="location.href='favorite.php';">
            Favorites
          </button>
        </div>
      
      <!-- 본문 -->
      <div class="content">
        <div class="favorite_list">
          <?php renderFavoriteList($user_id); ?>
        </div>
      </div>
    </div>
  </body>
</html>