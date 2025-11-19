<!-- 217100 Kim Yumin -->
<?php
include_once '../sql/db.php';

/**
 * 식당 헤더 렌더링 (이름 + 지역)
 * @param int $rest_id 식당 ID
 */
function renderRestaurantHeader($rest_id) {
  $mysqli = connectDB();

  $stmt = $mysqli->prepare("
      SELECT r.name AS restaurant_name, g.city, g.country
      FROM Restaurant r 
      JOIN Region g ON r.region_id = g.region_id
      WHERE r.rest_id = ?
  ");
  $stmt->bind_param("i", $rest_id); // i = integer
  $stmt->execute();
  $res = $stmt->get_result();


  if ($res && $row = $res->fetch_assoc()) {
    echo '
    <div class="rest-header__info">
      <div class="rest-header__name">' . htmlspecialchars($row['restaurant_name']) . '</div>
      <div class="rest-header__btns">
        <button class="btn rest-header__btn">' . htmlspecialchars($row['city']) . ', ' . htmlspecialchars($row['country']) . '</button>
        <button class="btn rest-header__btn" onclick="location.href=\'./AllReviews.php?rest_id='.$rest_id.'\'">review</button>
      </div>
    </div>
      ';
  } else {
    echo "<div>식당 정보를 찾을 수 없습니다.</div>";
  }

  $stmt->close();
  $mysqli->close();
}



// ==================== 메뉴 ====================

/**
 * 메뉴 리스트 렌더링
 * @param int $rest_id 식당 ID
 * @param string $sort 정렬 방식 (popular|price_asc|price_desc)
 */
function renderMenuList($rest_id, $sort='popular') {
  $menus = getMenuList($rest_id, $sort);

  if (empty($menus)) {
      echo "<div>메뉴를 불러올 수 없습니다.</div>";
      return;
  }

  foreach ($menus as $menu) {
        echo '
            <div class="menu-list__item hover">
                <img src="../' . htmlspecialchars($menu['image_url']) . '" 
                     alt="' . htmlspecialchars($menu['product_name']) . '" />
                <div class="menu-item__info">
                    <span class="menu-item__name">' . htmlspecialchars($menu['product_name']) . '</span>
                    <span class="menu-item__price shadow">$' . htmlspecialchars($menu['price']) . '</span>
                </div>
            </div>
        ';
    }
}

/**
 * 메뉴 목록 조회 (배열로 반환)
 * @param int $rest_id 식당 ID
 * @param string $sort 정렬 방식
 * @return array 메뉴 목록
 */
function getMenuList($rest_id, $sort = 'popular') {
    $mysqli = connectDB();
    
    // 정렬 조건 설정
    switch ($sort) {
        case 'price_asc':
            $orderClause = "ORDER BY m.price ASC";
            break;
        case 'price_desc':
            $orderClause = "ORDER BY m.price DESC";
            break;
        case 'popular':
        default:
            $orderClause = "ORDER BY total_orders DESC, m.price ASC";
            break;
    }
    
    $sql = "
        SELECT m.menu_id, m.product_name, m.price, m.image_url, 
               IFNULL(SUM(o.quantity), 0) AS total_orders
        FROM Menu AS m 
        LEFT JOIN OrderHistory AS o ON m.menu_id = o.menu_id
        WHERE m.rest_id = ?
        GROUP BY m.menu_id
        $orderClause
    ";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $rest_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $menus = $res->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    $mysqli->close();
    
    return $menus;
}



// ==================== 리뷰 ====================

/**
 * 리뷰 평점 및 개수 렌더링
 * @param int $rest_id 식당 ID
 */
function renderRating($rest_id) {
    $stats = getReviewStats($rest_id);
    
    if ($stats && $stats['avg_score'] !== null) {
        $avg = round($stats['avg_score']);
        $stars = str_repeat('<i class="bi bi-star-fill"></i>', $avg);
        
        echo '
            <div class="stars">' . $stars . '</div>
            <div class="review-summary-block__count">
                <span style="color:#4d4d4d">Total review</span>
                <span style="font-size:25px; font-weight:bold">'.$stats['review_count'].'</span>
            </div>
        ';
    } else {
        echo "<div>Be the first to rate this!</div>";
    }
}

/**
 * 리뷰 통계 조회 (배열로 반환)
 * @param int $rest_id 식당 ID
 * @return array|null 평균 평점 및 리뷰 수
 */
function getReviewStats($rest_id) {
    $mysqli = connectDB();
    
    $stmt = $mysqli->prepare("
        SELECT AVG(score) as avg_score, COUNT(*) as review_count
        FROM Review
        WHERE rest_id = ?
    ");
    $stmt->bind_param("i", $rest_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stats = $res->fetch_assoc();
    
    $stmt->close();
    $mysqli->close();
    
    return $stats;
}

/**
 * 리뷰 리스트 렌더링
 * @param int $rest_id 식당 ID
 */
function renderReviewList($rest_id) {
    $reviews = getReviewList($rest_id);
    
    if (empty($reviews)) {
        echo "<div style='text-align:center;color: #888;background: white;height: 100%;border-radius: 30px;'>No reviews yet</div>";
        return;
    }
    
    foreach ($reviews as $review) {
        $stars = str_repeat('<i class="bi bi-star-fill"></i>', $review['score']);
        $date = substr($review['created_at'], 0, 10);
        $myBadge = $review['is_mine'] ?
            "<span style='font-size:11px;
                        background:#007bff;
                        color:white;
                        padding:0px 6px 2px;
                        border-radius:12px;
                        margin-left:5px;'>my</span>" : "";
        
        echo '
            <div class="review-list__item shadow">
                <div class="stars small" style="margin-bottom: 5px;">' . $stars . $myBadge . '</div>
                <div class="review-item__text">' . htmlspecialchars($review['comment']) . '</div>
                <div class="review-item__date">' . htmlspecialchars($date) . '</div>
            </div>
        ';
    }
}

/**
 * 리뷰 목록 조회 (배열로 반환)
 * @param int $rest_id 식당 ID
 * @return array 리뷰 목록
 */
function getReviewList($rest_id) {
    $mysqli = connectDB();
    $user_id = $_SESSION['user_id'] ?? 0;
    
    $stmt = $mysqli->prepare("
      SELECT 
        Review.*, 
        (user_id = ?) AS is_mine 
      FROM Review
      WHERE rest_id = ?
      ORDER BY 
        (user_id = ?) DESC,
        created_at DESC 
      LIMIT 2
    ");
    $stmt->bind_param("iii", $user_id,$rest_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $reviews = $res->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    $mysqli->close();
    
    return $reviews;
}

/**
 * 모든 리뷰 리스트 렌더링
 * @param int $rest_id 식당 ID
 */
function renderAllReviewsList($rest_id) {
    $reviews = getAllReviewsList($rest_id); 
    
    if (empty($reviews)) {
        echo "<div class='no-reviews'>No reviews yet</div>";
        return;
    }
    
    foreach ($reviews as $review) {
        $stars = str_repeat('<i class="bi bi-star-fill"></i>', $review['score']);
        $date = substr($review['created_at'], 0, 10);
        $comment = htmlspecialchars($review['comment']); 
        
        echo "
            <div class='review-list-block__item'>
                <div class='stars small'>$stars</div>
                <div class='review-item__text'>$comment</div>
                <div class='review-item__date'>$date</div>
            </div>
        ";
    }
}

/**
 * 모든 리뷰 목록 조회 (배열로 반환)
 * @param int $rest_id 식당 ID
 * @return array 모든 리뷰 목록
 */
function getAllReviewsList($rest_id) {
    $mysqli = connectDB();
    
    $stmt = $mysqli->prepare("
      SELECT * FROM Review
      WHERE rest_id = ?
      ORDER BY 
        created_at DESC 
    ");

    $stmt->bind_param("i", $rest_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $reviews = $res->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    $mysqli->close();
    
    return $reviews;
}

/**
 * 리뷰 작성/수정 버튼 렌더링
 * @param int $rest_id 식당 ID
 * @param int|null $user_id 사용자 ID
 */
function renderReviewButton($rest_id, $user_id = null) {
    if (!$user_id) {
        echo '<button class="btn review-summary-block__action shadow" onclick="alert(\'로그인 후 작성 가능합니다.\')">Do/fix review</button>';
        return;
    }
    
    $hasReview = hasUserReviewed($rest_id, $user_id);
    $btnText = $hasReview ? "Fix review" : "Do review";
    
    echo '<button class="btn review-summary-block__action shadow" 
          onclick="location.href=\'tempReviewWrite.php?rest_id='.$rest_id.'&user_id='.$user_id.'\'">'
          .$btnText.'</button>';
}

/**
 * 사용자가 리뷰를 작성했는지 확인
 * @param int $rest_id 식당 ID
 * @param int $user_id 사용자 ID
 * @return bool 리뷰 작성 여부
 */
function hasUserReviewed($rest_id, $user_id) {
    $mysqli = connectDB();
    
    $stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM Review WHERE rest_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $rest_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    
    $stmt->close();
    $mysqli->close();
    
    return $row['cnt'] > 0;
}
?>