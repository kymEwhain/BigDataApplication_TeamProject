<?php
require_once "config/db.php";

$category_id = $_GET['category'] ?? 1;
$start = $_GET['start'] ?? '2024-01-01';
$end   = $_GET['end']   ?? '2024-12-31';

// DB 연결
$db = new PDO($dsn, $db_user, $db_pass);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 카테고리 목록 불러오기
$cat_stmt = $db->query("SELECT category_id, name FROM Category ORDER BY category_id ASC");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

/* 1) 월별 매출 */
$sql_month = "
    SELECT
        DATE_FORMAT(o.order_date, '%Y-%m') AS month,
        SUM(o.amount) AS monthly_total,
        SUM(SUM(o.amount)) OVER (
            ORDER BY DATE_FORMAT(o.order_date, '%Y-%m')
        ) AS cumulative_sales
    FROM OrderHistory o
    JOIN Menu m ON o.menu_id = m.menu_id
    WHERE m.category_id = ?
      AND o.order_date BETWEEN ? AND ?
    GROUP BY month
    ORDER BY month
";
$stmt = $db->prepare($sql_month);
$stmt->execute([$category_id, $start, $end]);
$monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 2) 메뉴별 총 판매량 */
$sql_menu = "
    SELECT
        m.product_name,
        SUM(o.quantity) AS total_qty,
        RANK() OVER (ORDER BY SUM(o.quantity) DESC) AS rank_qty
    FROM OrderHistory o
    JOIN Menu m ON o.menu_id = m.menu_id
    WHERE m.category_id = ?
      AND o.order_date BETWEEN ? AND ?
    GROUP BY m.menu_id
    ORDER BY total_qty DESC
";
$stmt = $db->prepare($sql_menu);
$stmt->execute([$category_id, $start, $end]);
$menu_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
<title>카테고리별 통계</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<style>
.chart-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);  /* 2개씩 배치 */
    gap: 20px;                              /* 간격 */
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

.chart-box {
    background: #fff;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;              /* 내부도 중앙정렬 */
    justify-content: center;
    align-items: center;
}

.filter-form {
    margin: 20px auto;
    background: #f9f9f9;
    padding: 20px;
    border-radius: 12px;
}
</style>
<body>

<h2 style="text-align:center;">카테고리별 기간 통계</h2>

<form method="GET" class="filter-form">
    <div style="display: flex; flex-direction: row; gap: 15px;">

        <!-- 카테고리 선택 -->
        <div>
            <label for="category">카테고리</label>
            <select id="category" name="category" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>"
                        <?= ($category_id == $cat['category_id'] ? 'selected' : '') ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- 시작 날짜 -->
        <div>
            <label for="start">시작 날짜</label>
            <input type="date" id="start" name="start" value="<?= $start ?>" required class="form-control">
        </div>

        <!-- 종료 날짜 -->
        <div>
            <label for="end">종료 날짜</label>
            <input type="date" id="end" name="end" value="<?= $end ?>" required class="form-control">
        </div>

        <!-- 버튼 -->
        <button type="submit" class="btn btn-primary">조회하기</button>
    </div>
</form>

<div class="chart-grid">
    
    <div class="chart-box">
        <canvas id="lineChart"></canvas>
    </div>

    <div class="chart-box">
        <canvas id="donutChart"></canvas>
    </div>

</div>

<script>
// PHP → JS 데이터 변환
const monthly = <?= json_encode($monthly) ?>;
const menuStats = <?= json_encode($menu_stats) ?>;

// 라벨
const labels = monthly.map(r => r.month);
const sales = monthly.map(r => r.monthly_total);

// 메뉴별
const menuLabels = menuStats.map(m => m.product_name);
const menuQty = menuStats.map(m => m.total_qty);

// ***** 라인 차트 *****
new Chart(document.getElementById("lineChart"), {
    type: "line",
    data: {
        labels: labels,
        datasets: [{
            label: "월별 매출",
            data: sales,
            borderColor: "#5a66f2",
            tension: 0.3
        }]
    }
});

// ***** 도넛 차트 *****
new Chart(document.getElementById("donutChart"), {
    type: "doughnut",
    data: {
        labels: menuLabels,
        datasets: [{
            data: menuQty,
            backgroundColor: ["#6c5ce7","#0984e3","#fab1a0","#fd79a8","#55efc4","#ffeaa7"]
        }]
    }
});
</script>

</body>
</html>
