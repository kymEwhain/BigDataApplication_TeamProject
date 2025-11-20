<?php
require_once "config/db.php";

try {
    $db = new PDO($dsn, $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "DB 연결 오류: " . $e->getMessage();
    exit;
}

$category_id = $_GET['category'] ?? 1;
$start = $_GET['start'] ?? '2024-01-01';
$end   = $_GET['end']   ?? '2024-12-31';

// 카테고리 목록 가져오기
$cat_stmt = $db->query("SELECT category_id, name FROM Category ORDER BY category_id ASC");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "
    SELECT 
        r.country,
        r.city,
        SUM(o.amount) AS total_amount
    FROM OrderHistory o
    JOIN Menu m ON o.menu_id = m.menu_id
    JOIN Restaurant res ON m.rest_id = res.rest_id
    JOIN Region r ON res.region_id = r.region_id
    WHERE m.category_id = :cat
    AND o.order_date BETWEEN :start AND :end
    GROUP BY r.country, r.city WITH ROLLUP;
";

$stmt = $db->prepare($sql);
$stmt->execute([
    ':cat' => $category_id,
    ':start' => $start,
    ':end' => $end,
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$country_map = [
    'Korea' => 'South Korea',
    'USA' => 'United States',
];

$country_summary = [];
$city_summary = [];

foreach ($rows as $r) {

    $orig_country = $r['country'];

    // NULL(전체 합계)은 제외
    if ($orig_country === null) {
        continue;
    }
    $country = $country_map[$orig_country] ?? $orig_country;

    // ROLLUP에서 city null = 국가 합계
    if ($r['city'] === null) {
        // 국가 단위 합계
        $country_summary[$country] = (float)$r['total_amount'];
    } else {
        // 도시 단위 합계
        $city_summary[$country] ??= [];
        $city_summary[$country][] = [
            'city' => $r['city'],
            'total' => (float)$r['total_amount']
        ];
    }
}

$iso_to_country = [
    'KR' => 'South Korea',
    'US' => 'United States',
    'JP' => 'Japan',
    'CN' => 'China',
    'FR' => 'France',
    'IT' => 'Italy',
];

// JS로 넘길 때 JSON 인코딩
$city_summary_json = json_encode($city_summary, JSON_UNESCAPED_UNICODE);
$iso_to_country_json = json_encode($iso_to_country, JSON_UNESCAPED_UNICODE);

?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>지역별 인기도 분석</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.chart-box {
    background:#fff;
    padding:20px;
    border-radius:10px;
    box-shadow:0 2px 4px rgba(0,0,0,0.1);
    margin-top:20px;
}
body{
    background-color: #e2e9fb;
}
</style>
</head>

<body>

<div class="container mt-4">

<div class="d-flex align-items-center mb-3">
    <a href="index.php" class="btn btn-light border rounded-circle p-2 shadow-sm">
        <i class="bi bi-house-fill fs-3"></i>
    </a>
    <h2 class="m-0 flex-grow-1 text-center"><b>카테고리별 매출 분석 - 지역별 매출</b></h2>
</div>

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

<div class="row mt-4">
    <div class="col-md-8">
        <div id="geoChart" style="width:100%; height:500px;" class="chart-box"></div>
    </div>

    <div class="col-md-4">
        <h3>도시별 상세</h3>
        <div id="cityPanel" class="chart-box" style="min-height:300px;">
            <p>지도를 클릭하면 도시별 정보가 여기 표시됩니다.</p>
        </div>
    </div>
</div>


<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
google.charts.load('current', {'packages':['geochart']});
google.charts.setOnLoadCallback(drawRegionsMap);

const CITY_SUMMARY = <?= $city_summary_json ?>;
const ISO_TO_COUNTRY = <?= $iso_to_country_json ?>;

function drawRegionsMap() {

    // GeoChart에 들어갈 국가 데이터
    const data = google.visualization.arrayToDataTable([
        ['Country', 'Sales($)'],
        <?php foreach ($country_summary as $country => $amount): ?>
            ['<?= $country ?>', <?= $amount ?>],
        <?php endforeach; ?>
    ]);

    const options = {
        colorAxis: { colors: ['#FFE0B2', '#FB8C00', '#E65100'] },
        backgroundColor: '#f8f9fa',
        datalessRegionColor: '#DDD',
        tooltip: { isHtml: true },
        enableRegionInteractivity: true,

        // 깜빡임 최소화
        regionStrokeColor: '#ffffff',
        regionStrokeWidth: 1
    };

    const chart = new google.visualization.GeoChart(
        document.getElementById('geoChart')
    );

    chart.draw(data, options);

    // 국가 클릭 시 오른쪽 패널 업데이트
    google.visualization.events.addListener(chart, 'regionClick', function(event) {
        const iso = event.region; // ex) KR
        const countryName = ISO_TO_COUNTRY[iso] ?? null;

        if (!countryName || !CITY_SUMMARY[countryName]) {
            document.getElementById('cityPanel').innerHTML =
                "<p>도시 데이터가 없습니다.</p>";
            return;
        }

        // 도시 목록 HTML 생성
        let html = `<h4>${countryName}</h4><ul>`;
        CITY_SUMMARY[countryName].forEach(c => {
            html += `<li><b>${c.city}</b>: ${c.total.toLocaleString()} $</li>`;
        });
        html += `</ul>`;

        document.getElementById('cityPanel').innerHTML = html;
    });
}

</script>

</div>
</body>
</html>
