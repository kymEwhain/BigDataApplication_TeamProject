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
include_once "../functions/getRestaurantInfo.php";
include_once "../functions/favorite.php";

handleFavoriteToggle();
?>

<!DOCTYPE html>
<html lang="ko">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../css/restaurant_style.css" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/solid.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/regular.min.css">
    <title>Restaurant</title>
  </head>
  <body>
    <div class="container">
      <!-- 뒤로 가기 버튼 -->
        <div style="display:flex; flex-direction: row; justify-content: space-between;">
          <button class="btn back-btn" onclick="history.back()">
            <i class="bi bi-arrow-left"></i> Back
          </button>
          <button class="btn favoriteList-btn shadow" onclick="location.href='favorite.php';">
            Favorites
          </button>
        </div>
      <!-- 헤더: 상단 식당 이름 및 지역 -->
      <div class="header shadow">
        <?php
          renderRestaurantHeader( $rest_id );
        ?>
        <div><?php renderFavoriteButton($user_id, $rest_id); ?></div>
      </div>

      <!-- 본문 -->
      <div class="content">
        <!-- 메뉴 카드 -->
        <div class="card menu-card shadow">
          <h2>🍴 MENU</h2>
          <div class="menu-header">
            <p class="subtitle">Dishes</p>
            <div class="menu-order">
              <form method="get" action="">
                <input type="hidden" name="rest_id" value="<?php echo $rest_id; ?>">
                <select name="sort" onchange="history.replaceState(null, '', '?rest_id=<?=$rest_id?>&sort=' + this.value);location.reload();">
                  <option value="popular" <?= ($sort == 'popular') ? 'selected' : '' ?>>Most Popular</option>
                  <option value="price_asc" <?= ($sort == 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                  <option value="price_desc" <?= ($sort == 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                </select>
              </form>
            </div>
          </div>
          <div class="menu-List"><?php renderMenuList($rest_id, $sort) ?></div>
        </div>
        
        <!-- 리뷰 카드 -->
        <div class="card review-card">
          <!-- 리뷰 총평 -->
          <div class="review-total shadow">
            <h2>review</h2>
            <?php renderRating($rest_id) ?>
            <!-- TODO: 리뷰 작성 페이지 경로 수정하기 -->
            <?php renderReviewButton($rest_id, $user_id) ?> 
          </div>

          <!-- 리뷰 리스트 -->
          <div class="review-list">
            <?php 
            renderReviewList(rest_id: $rest_id); 
            ?>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>