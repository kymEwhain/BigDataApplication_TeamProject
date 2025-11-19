<!-- 217100 Kim Yumin -->
<?php
include_once "../sql/db.php";
include_once "getRestaurantInfo.php";

/**
 * 즐겨찾기 여부 확인
 * @param int $user_id 사용자 ID
 * @param int $rest_id 식당 ID
 * @return bool 즐겨찾기 여부
 */
function isFavorited($user_id, $rest_id) {
    if (!$user_id || !$rest_id) {
        return false;
    }
    
    try {
        $mysqli = connectDB();
        $stmt = $mysqli->prepare("SELECT 1 FROM Favorite WHERE user_id=? AND rest_id=? LIMIT 1");
        $stmt->bind_param("ii", $user_id, $rest_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $favorited = ($result->num_rows > 0);
        
        $stmt->close();
        $mysqli->close();
        
        return $favorited;
    } catch (Exception $e) {
        error_log("isFavorited Error: " . $e->getMessage());
        return false;
    }
}

/**
 * 즐겨찾기 추가
 * @param int $user_id 사용자 ID
 * @param int $rest_id 식당 ID
 * @return bool 성공 여부
 */
function addFavorite($user_id, $rest_id) {
    try {
        $mysqli = connectDB();
        $stmt = $mysqli->prepare("INSERT INTO Favorite(user_id, rest_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $user_id, $rest_id);
        $success = $stmt->execute();
        
        $stmt->close();
        $mysqli->close();
        
        return $success;
    } catch (Exception $e) {
        error_log("addFavorite Error: " . $e->getMessage());
        return false;
    }
}

/**
 * 즐겨찾기 삭제
 * @param int $user_id 사용자 ID
 * @param int $rest_id 식당 ID
 * @return bool 성공 여부
 */
function removeFavorite($user_id, $rest_id) {
    try {
        $mysqli = connectDB();
        $stmt = $mysqli->prepare("DELETE FROM Favorite WHERE user_id=? AND rest_id=?");
        $stmt->bind_param("ii", $user_id, $rest_id);
        $success = $stmt->execute();
        
        $stmt->close();
        $mysqli->close();
        
        return $success;
    } catch (Exception $e) {
        error_log("removeFavorite Error: " . $e->getMessage());
        return false;
    }
}

/**
 * 즐겨찾기 토글 (추가 ↔ 삭제)
 * @param int $user_id 사용자 ID
 * @param int $rest_id 식당 ID
 * @return array ['success' => bool, 'action' => 'added'|'removed', 'message' => string]
 */
function toggleFavorite($user_id, $rest_id) {
    if (!$user_id || !$rest_id) {
        return [
            'success' => false,
            'action' => null,
            'message' => 'Invalid user_id or rest_id'
        ];
    }
    
    $isFavorited = isFavorited($user_id, $rest_id);
    
    if ($isFavorited) {
        // 즐겨찾기 삭제
        $success = removeFavorite($user_id, $rest_id);
        return [
            'success' => $success,
            'action' => 'removed',
            'message' => $success ? 'Removed from favorites' : 'Failed to remove'
        ];
    } else {
        // 즐겨찾기 추가
        $success = addFavorite($user_id, $rest_id);
        return [
            'success' => $success,
            'action' => 'added',
            'message' => $success ? 'Added to favorites' : 'Failed to add'
        ];
    }
}

/**
 * 사용자의 즐겨찾기 목록 가져오기
 * @param int $user_id 사용자 ID
 * @return array 식당 ID 배열
 */
function getUserFavorites($user_id) {
    if (!$user_id) {
        return [];
    }
    
    try {
        $mysqli = connectDB();
        $stmt = $mysqli->prepare("SELECT rest_id FROM Favorite WHERE user_id=? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $favorites = [];
        while ($row = $result->fetch_assoc()) {
            $favorites[] = $row['rest_id'];
        }
        
        $stmt->close();
        $mysqli->close();
        
        return $favorites;
    } catch (Exception $e) {
        error_log("getUserFavorites Error: " . $e->getMessage());
        return [];
    }
}

/**
 * 사용자의 즐겨찾기 목록과 식당 정보 가져오기
 * @param int $user_id 사용자 ID
 * @return array 식당 정보 배열
 */
function getUserFavoriteRestaurants($user_id) {
    if (!$user_id) return [];

    try {
        $mysqli = connectDB();

        $sql = "
            SELECT r.rest_id, r.name, r.rating, CONCAT(rg.country, ' ', rg.city) AS region_name
            FROM Favorite f
            JOIN Restaurant r ON f.rest_id = r.rest_id
            LEFT JOIN Region rg ON r.region_id = rg.region_id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC
        ";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $restaurants = [];
        while ($row = $result->fetch_assoc()) {
            $restaurants[] = $row;
        }

        $stmt->close();
        $mysqli->close();

        return $restaurants;
    } catch (Exception $e) {
        error_log("getUserFavoriteRestaurants Error: " . $e->getMessage());
        return [];
    }
}


/**
 * 즐겨찾기 토글 요청 처리 
 */
function handleFavoriteToggle() {
    if (isset($_GET['favorite_action']) && $_GET['favorite_action'] === 'toggle') {
        $user_id = $_SESSION['user_id'] ?? null;
        $rest_id = $_GET['rest_id'] ?? null;
        
        if ($user_id && $rest_id) {
            toggleFavorite($user_id, $rest_id);
            
            $params = $_GET;
            unset($params['favorite_action']);
            
            $url = strtok($_SERVER['REQUEST_URI'], '?');
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            
            header("Location: " . $url);
            exit;
        }
    }
}

/**
 * 즐겨찾기 버튼 렌더링 (페이지 새로고침 방식)
 * @param int $user_id 사용자 ID
 * @param int $rest_id 식당 ID
 * @param string $size 버튼 크기 ('small', 'medium', 'large')
 */
function renderFavoriteButton($user_id, $rest_id, $size = 'medium') {
    // 로그인하지 않은 경우
    if (!$user_id) {
        echo '<a href="../components/tempLogin.php" class="favorite-btn favorite-login-required" 
                 title="Login required">
                <i class="fa-regular fa-heart"></i>
              </a>';
        return;
    }
    
    // 즐겨찾기 상태 확인
    $isFavorited = isFavorited($user_id, $rest_id);
    $iconClass = $isFavorited ? "fa-solid" : "fa-regular";
    $activeClass = $isFavorited ? "active" : "";
    
    // 크기 클래스
    $sizeClass = "favorite-btn-" . $size;
    
    // 현재 URL 파라미터 유지하면서 favorite_action 추가
    $params = $_GET;
    $params['favorite_action'] = 'toggle';
    $params['rest_id'] = $rest_id;
    $toggleUrl = '?' . http_build_query($params);
    
    // 버튼 HTML (링크 방식)
    echo '
        <a href="'.$toggleUrl.'" 
            class="favorite-btn '.$sizeClass.' '.$activeClass.'" 
            title="'.($isFavorited ? 'Remove from favorites' : 'Add to favorites').'">
            <i class="fa-heart '.$iconClass.'"></i>
        </a>
    ';
}

/**
 * 즐겨찾기 페이지 식당 목록 렌더링
 * @param int $user_id 사용자 ID
 */
function renderFavoriteList($user_id){
    $restaurants = getUserFavoriteRestaurants($user_id);

    if (empty($restaurants)) {
        echo "<p>즐겨찾기한 식당이 없습니다.</p>";
        return;
    }

    foreach ($restaurants as $rest) {
        echo '<div class="favorite-list__item shadow">';
        
        // 식당 정보 + 이미지
        echo '<div class="restaurant-item__info">';
        
        $image_path = "../images/restaurants/" . $rest['rest_id'] . ".jpg";
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
            $image_path = "../images/restaurants/default.jpg";
        }

        echo '<img src="' . $image_path . '" alt="' . htmlspecialchars($rest['name']) . '">';
        
        echo '<div class="restaurant-item__text">';
        echo '<div class="restaurant-item__name" style="font-weight:bold;">' . htmlspecialchars($rest['name']) . '</div>';
        
        // 별 평점
        $stats = getReviewStats($rest['rest_id']);
        $maxStars = 5; // 만점 5

        if ($stats && $stats['avg_score'] !== null) {
            $fullStars = floor($stats['avg_score']); // 꽉 찬 별
            $halfStar = ($stats['avg_score'] - $fullStars >= 0.5) ? 1 : 0; // 반 별
            $emptyStars = $maxStars - $fullStars - $halfStar; // 빈 별 수

            $starsHtml = str_repeat('<i class="bi bi-star-fill"></i>', $fullStars); // 꽉 찬 별
            if ($halfStar) $starsHtml .= '<i class="bi bi-star-half"></i>'; // 반 별
            $starsHtml .= str_repeat('<i class="bi bi-star"></i>', $emptyStars); // 빈 별
        } else {
            // 리뷰 없는 경우: 빈 별 5개
            $starsHtml = str_repeat('<i class="bi bi-star"></i>', $maxStars);
        }

        echo '<div class="stars small">' . $starsHtml . '</div>';


        echo '<div class="restaurant-item__region">' . htmlspecialchars($rest['region_name']) . '</div>';

        echo '</div>'; // restaurant_text

        echo '</div>'; // restaurant_info

        // 즐겨찾기 버튼
        echo '<div class="restaurant-item__actions">';
        renderFavoriteButton($user_id, $rest['rest_id'], 'large');
        echo '</div>';

        echo '</div>'; // restaurant_item
    }
}



?>
