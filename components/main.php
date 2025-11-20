<?php
// index.php — Global Food Insight Dashboard
// 세션 및 DB 연결 설정
session_start();

// TODO: 아래 접속 정보는 팀 번호/서버 환경에 맞게 수정
$DB_HOST = '127.0.0.1';
$DB_USER = 'team01';
$DB_PASS = 'team01';
$DB_NAME = 'team01';
$DB_PORT = 3306;

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo "DB 연결 실패: " . htmlspecialchars($mysqli->connect_error);
    exit;
}
$mysqli->set_charset('utf8mb4');

// 1) 주문수 기준 상위 4개 식당
$sqlTopRest = "
    SELECT r.rest_id, r.name AS rest_name, COUNT(*) AS orders
    FROM OrderHistory o
    JOIN Menu m       ON o.menu_id = m.menu_id
    JOIN Restaurant r ON m.rest_id = r.rest_id
    GROUP BY r.rest_id, r.name
    ORDER BY orders DESC, r.rest_id ASC
    LIMIT 4
";
$resTop = $mysqli->query($sqlTopRest);
$topRows = [];
if ($resTop) {
    while ($row = $resTop->fetch_assoc()) {
        $topRows[] = $row;
    }
    $resTop->free();
}

// 2) 최고 매출 지역 (OrderHistory.amount 합계 기준)
$sqlBestRegion = "
    SELECT CONCAT(re.country, ' ', re.city) AS region_name, SUM(o.amount) AS total_sales
    FROM OrderHistory o
    JOIN Menu m        ON o.menu_id = m.menu_id
    JOIN Restaurant r  ON m.rest_id = r.rest_id
    JOIN Region re     ON r.region_id = re.region_id
    GROUP BY re.region_id, re.country, re.city
    ORDER BY total_sales DESC
    LIMIT 1
";
$bestRegion = ['region_name'=>'-', 'total_sales'=>0];
if ($res = $mysqli->query($sqlBestRegion)) {
    if ($row = $res->fetch_assoc()) {
        $bestRegion = $row;
    }
    $res->free();
}

// 3) 최고 매출 계절 (주문일 월→계절 매핑 후 amount 합계)
$sqlBestSeason = "
    SELECT season, SUM(amount) AS total_sales
    FROM (
        SELECT
            CASE
              WHEN MONTH(order_date) IN (3,4,5)   THEN 'Spring'
              WHEN MONTH(order_date) IN (6,7,8)   THEN 'Summer'
              WHEN MONTH(order_date) IN (9,10,11) THEN 'Autumn'
              ELSE 'Winter'
            END AS season,
            amount
        FROM OrderHistory
    ) t
    GROUP BY season
    ORDER BY total_sales DESC
    LIMIT 1
";
$bestSeason = ['season'=>'-', 'total_sales'=>0];
if ($res = $mysqli->query($sqlBestSeason)) {
    if ($row = $res->fetch_assoc()) {
        $bestSeason = $row;
    }
    $res->free();
}

$mysqli->close();

// Chart.js용 데이터 준비
$labels = array_map(fn($r) => $r['rest_name'], $topRows);
$values = array_map(fn($r) => (int)$r['orders'], $topRows);
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <title>Tasty — Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
  <style>
    body { background: #eaf0ff; }
    .brand { font-weight: 800; font-size: 40px; letter-spacing: 1px; }
    .rank-card { border-radius: 18px; padding: 14px 18px; color:#fff; background: linear-gradient(135deg,#3b82f6,#60a5fa); box-shadow: 0 8px 20px rgba(59,130,246,0.2); }
    .rank-card:nth-child(2n) { background: linear-gradient(135deg,#74c365,#7bd36b); }
    .stat-card { background:#fff; border: 0; border-radius:18px; box-shadow: 0 10px 24px rgba(0,0,0,0.06); }
    .btn-wide { min-width: 280px; padding: 14px 20px; border-radius:14px; font-weight:600; }
  </style>
</head>
<body>
<div class="container py-4">

  <div class="d-flex align-items-center gap-2 mb-4">
    <div class="brand">뀨asty</div>
    <!-- 로그아웃 버튼 -->
    <a href="logout.php" class="btn btn-outline-dark fw-bold px-4">
      로그아웃
    </a>
  </div>

  <div class="row g-4">
    <!-- 좌측: 주문수 식당 랭킹 + 바 차트 -->
    <div class="col-lg-8">
      <div class="mb-3">
        <h5 class="fw-bold text-secondary">주문수 식당 랭킹</h5>
      </div>

      <div class="vstack gap-3 mb-4">
        <?php
        $rank = 1;
        foreach ($topRows as $r):
        ?>
          <div class="rank-card d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <span class="badge bg-dark fs-6 px-3 py-2"><?= (int)$rank ?>위</span>
              <span class="fs-5 fw-semibold"><?= htmlspecialchars($r['rest_name']) ?></span>
            </div>
            <div class="fs-5 fw-bold"><?= number_format((int)$r['orders']) ?>건</div>
          </div>
        <?php
          $rank++;
        endforeach;
        if (!$topRows) {
            echo '<div class="alert alert-light border">데이터가 없습니다.</div>';
        }
        ?>
      </div>

      <div class="card stat-card p-3">
        <canvas id="rankChart" height="120"></canvas>
      </div>
    </div>

    <!-- 우측: 최고매출 지역/계절 -->
    <div class="col-lg-4">
      <div class="card stat-card p-4 mb-4">
        <div class="text-secondary fw-semibold">최고매출지역</div>
        <div class="display-6 fw-bold mt-2">
          <?= htmlspecialchars($bestRegion['region_name']) ?>
        </div>
        <div class="mt-2 text-muted">매출: $<?= number_format((float)$bestRegion['total_sales']) ?></div>
      </div>

      <div class="card stat-card p-4">
        <div class="text-secondary fw-semibold">최고매출계절</div>
        <div class="display-6 fw-bold mt-2">
          <?= htmlspecialchars($bestSeason['season']) ?>
        </div>
        <div class="mt-2 text-muted">매출: $<?= number_format((float)$bestSeason['total_sales']) ?></div>
      </div>
    </div>
  </div>

  <!-- 하단 네비 버튼 -->
  <div class="d-flex flex-wrap gap-3 justify-content-center mt-5">
    <a href="/insight_category_menus.php" class="btn btn-primary btn-wide">카테고리-월별매출&메뉴 통계</a>
    <a href="/insight_category_region.php" class="btn btn-info btn-wide text-white">카테고리-지역 통계</a>
    <a href="restaurant_search.php" class="btn btn-secondary btn-wide">식당 검색</a>
  </div>

</div>

<script>
  // 상단 랭킹 바 차트
  const ctx = document.getElementById('rankChart').getContext('2d');
  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
      datasets: [{
        label: '주문수',
        data: <?= json_encode($values) ?>,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx) => `주문수: ${ctx.parsed.y}` } }
      },
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });
</script>

<!-- Bootstrap JS (옵션) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>