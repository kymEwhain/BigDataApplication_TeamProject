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
include "../functions/getRestaurantInfo.php";
?>

<!DOCTYPE html>
<html lang="ko">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    />
    <title>My favorite Restaurants</title>
  </head>
  <body>
    <div class="container">
      <!-- 뒤로 가기 버튼 -->
      <button class="btn back-btn" onclick="history.back()">
          <i class="bi bi-arrow-left"></i> Back
      </button>
      <div class="header shadow">
        
      </div>

      <!-- 본문 -->
      <div class="content">
        
      </div>
    </div>
  </body>
</html>