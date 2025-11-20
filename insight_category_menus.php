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

// 월별 매출
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

// 메뉴별 총 판매량
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
<title>카테고리별 메뉴 통계</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<style>
.chart-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

.chart-box {
    background: #fff;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: center;
    align-items: center;
}

body{
    background-color: #e2e9fb;
}
</style>
<body>
<div class="container mt-4">

<h2 class="text-center"><b>카테고리별 매출 분석 - 월별 매출 및 각 메뉴별 판매량</b></h2>

<form method="GET" class="row g-3 border p-4 rounded mt-4">

    <div class="col-md-4">
        <label class="form-label">카테고리</label>
        <select class="form-select" name="category">
            <?php foreach($categories as $cat): ?>
            <option value="<?= $cat['category_id'] ?>"
                <?= $cat['category_id']==$category_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
            </option>
            <?php endforeach ?>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">시작 날짜</label>
        <input type="date" class="form-control" name="start" value="<?= $start ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label">종료 날짜</label>
        <input type="date" class="form-control" name="end" value="<?= $end ?>">
    </div>

    <div class="col-12">
        <button class="btn btn-primary w-100">조회</button>
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

</div>

<script>
// 데이터 변환
const monthly = <?= json_encode($monthly) ?>;
const menuStats = <?= json_encode($menu_stats) ?>;

// 라벨
const labels = monthly.map(r => r.month);
const sales = monthly.map(r => r.monthly_total);
const cumulative = monthly.map(r => r.cumulative_sales);

// 메뉴별
const menuLabels = menuStats.map(m => m.product_name);
const menuQty = menuStats.map(m => m.total_qty);

// ***** 라인 차트 *****
new Chart(document.getElementById("lineChart"), {
    type: "line",
    data: {
        labels: labels,
        datasets: [
            {
                label: "월별 매출 ($)",
                data: sales,
                borderColor: "#5a66f2",
                backgroundColor: "rgba(90,102,242,0.2)",
                tension: 0.3
            },
            {
                label: "누적 매출 ($)",
                data: cumulative,
                borderColor: "#ff7675",
                backgroundColor: "rgba(255,118,117,0.2)",
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: false
            }
        }
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
