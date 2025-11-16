<?php
include_once "../sql/db.php";

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
 * 즐겨찾기 토글 요청 처리 (GET 방식)
 * 페이지 로드 시 자동으로 실행됨
 */
function handleFavoriteToggle() {
    if (isset($_GET['favorite_action']) && $_GET['favorite_action'] === 'toggle') {
        $user_id = $_SESSION['user_id'] ?? null;
        $rest_id = $_GET['rest_id'] ?? null;
        
        if ($user_id && $rest_id) {
            toggleFavorite($user_id, $rest_id);
            
            // URL에서 favorite_action 파라미터 제거 후 리다이렉트
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
        echo '<a href="../pages/login.php" class="favorite-btn favorite-login-required" 
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
    $toggleUrl = '?' . http_build_query($params);
    
    // 버튼 HTML (링크 방식)
    echo '
    <div>
        <a href="'.$toggleUrl.'" 
            class="favorite-btn '.$sizeClass.' '.$activeClass.'" 
            title="'.($isFavorited ? 'Remove from favorites' : 'Add to favorites').'">
            <i class="fa-heart '.$iconClass.'"></i>
        </a>
    </div>';
}
?>