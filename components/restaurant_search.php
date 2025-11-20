<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

/* =============== DB 연결 =============== */
$DB_HOST = 'localhost';
$DB_USER = 'team01';
$DB_PASS = 'team01';
$DB_NAME = 'team01';

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    exit("DB connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

/* 어떤 화면을 볼지 결정: detail / city / country */
$view = $_GET['view'] ?? 'detail';
if (!in_array($view, ['detail', 'city', 'country'], true)) {
    $view = 'detail';
}

/*
 * 하나의 ROLLUP 쿼리
 */
$sql = "
SELECT
    rg.country,
    rg.city,
    r.rest_id,
    r.name AS restaurant_name,
    ROUND(AVG(rv.score), 2)   AS avg_score,
    COUNT(DISTINCT r.rest_id) AS rest_cnt
FROM Restaurant r
JOIN Region rg   ON r.region_id = rg.region_id
LEFT JOIN Review rv ON rv.rest_id = r.rest_id
GROUP BY rg.country, rg.city, r.rest_id WITH ROLLUP
";

$res = $mysqli->query($sql);
if (!$res) {
    http_response_code(500);
    exit('SQL error: ' . $mysqli->error);
}

/* --------- 결과 정리 --------- */
$restaurants    = [];
$cityGroups     = [];
$countryGroups  = [];

while ($row = $res->fetch_assoc()) {
    $country = $row['country'];
    $city    = $row['city'];
    $restId  = $row['rest_id'];
    $name    = $row['restaurant_name'];
    $avg     = is_null($row['avg_score']) ? null : (float)$row['avg_score'];
    $cnt     = (int)$row['rest_cnt'];

    /* 1) 식당 레벨 */
    if (!is_null($restId)) {
        $restaurants[] = [
            'country' => $country,
            'city'    => $city,
            'rest_id' => $restId,
            'name'    => $name,
            'avg'     => $avg
        ];

        /* 도시 그룹 */
        $cityKey = $country . '|' . $city;
        if (!isset($cityGroups[$cityKey])) {
            $cityGroups[$cityKey] = [
                'country' => $country,
                'city'    => $city,
                'avg'     => null,
                'cnt'     => 0,
                'restaurants' => []
            ];
        }
        $cityGroups[$cityKey]['restaurants'][] = [
            'name'=>$name,
            'avg'=>$avg,
            'rest_id'=>$restId
        ];

        /* 국가 그룹 */
        if (!isset($countryGroups[$country])) {
            $countryGroups[$country] = [
                'country' => $country,
                'avg'     => null,
                'cnt'     => 0,
                'restaurants' => []
            ];
        }
        $countryGroups[$country]['restaurants'][] = [
            'city'=>$city,
            'name'=>$name,
            'avg'=>$avg,
            'rest_id'=>$restId
        ];
    }
    /* 2) 도시 ROLLUP */
    elseif (is_null($restId) && !is_null($city)) {
        $cityKey = $country . '|' . $city;
        $cityGroups[$cityKey]['avg'] = $avg;
        $cityGroups[$cityKey]['cnt'] = $cnt;
    }
    /* 3) 국가 ROLLUP */
    elseif (is_null($restId) && is_null($city) && !is_null($country)) {
        $countryGroups[$country]['avg'] = $avg;
        $countryGroups[$country]['cnt'] = $cnt;
    }
}

/* 정렬 */
usort($restaurants, fn($a,$b)=>($b['avg']<=>$a['avg'])?:strcmp($a['name'],$b['name']));
$cityGroupsList = array_values($cityGroups);
usort($cityGroupsList, fn($a,$b)=>($b['avg']<=>$a['avg'])?:strcmp($a['city'],$b['city']));
$countryGroupsList = array_values($countryGroups);
usort($countryGroupsList, fn($a,$b)=>($b['avg']<=>$a['avg'])?:strcmp($a['country'],$b['country']));
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>식당 검색 · OLAP Rollup & Drill-down</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{background:#eaf0ff;}
  .card-soft{background:#fff;border:0;border-radius:18px;box-shadow:0 10px 30px rgba(62,86,179,.12);padding:18px 20px;}
  .row-item{border-radius:18px;padding:18px 22px;margin-bottom:12px;color:#fff;display:flex;justify-content:space-between;align-items:center;cursor:pointer;}
  .row-blue{background:linear-gradient(135deg,#4ea2ff,#3f7fe5);}
  .row-green{background:linear-gradient(135deg,#8bc34a,#7fbf3e);}
  .score-text{font-weight:700;font-size:1.05rem;}
  .subtext{font-size:.9rem;opacity:.85;}
  .child-box{display:none;margin-top:10px;}
  .child-card{border-radius:14px;background:rgba(255,255,255,.16);padding:10px 14px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;}
</style>

<script>
function toggleBox(id){
    const box = document.getElementById(id);
    if (!box) return;
    box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'block' : 'none';
}
</script>
</head>
<body>
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="fw-bold mb-0">식당 검색</h2>
    <div class="btn-group">
      <a href="?view=detail"   class="btn btn-outline-primary btn-sm <?= $view==='detail'?'active':'' ?>">식당별 Drill-down</a>
      <a href="?view=city"     class="btn btn-outline-primary btn-sm <?= $view==='city'?'active':'' ?>">Roll up: 도시별</a>
      <a href="?view=country"  class="btn btn-outline-primary btn-sm <?= $view==='country'?'active':'' ?>">Roll up: 국가별</a>
    </div>
  </div>

<!-- =================== DETAIL VIEW =================== -->
<?php if ($view === 'detail'): ?>
    <h5 class="mb-3">평점 정렬</h5>
    <div class="card-soft mb-3">
      <?php foreach ($restaurants as $i=>$r): ?>
        <?php
          $color     = ($i%2===0? 'row-blue':'row-green');
          $detailUrl = 'RestaurantDetail.php?rest_id=' . urlencode($r['rest_id']);
        ?>
        <!-- 전체 배너를 링크로 감싸기 -->
        <a href="<?= $detailUrl ?>" class="text-decoration-none d-block">
          <div class="row-item <?= $color ?>">
            <div>
              <div class="fw-semibold"><?= htmlspecialchars($r['name']) ?></div>
              <div class="subtext"><?= htmlspecialchars($r['country'].' / '.$r['city']) ?></div>
            </div>
            <div class="score-text">
              평균 평점 <?= is_null($r['avg']) ? '-' : number_format($r['avg'], 2) ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

<!-- =================== CITY VIEW =================== -->
<?php elseif ($view === 'city'): ?>
    <h5 class="mb-3">Roll up: 도시별</h5>
    <div class="card-soft mb-4">
    <?php foreach ($cityGroupsList as $i=>$g): ?>
        <?php
          $color = ($i%2===0? 'row-blue':'row-green');
          $boxId = "cityBox".$i;
          $avgText = is_null($g['avg'])? '-' : number_format($g['avg'],2);
        ?>
        <div class="row-item <?= $color ?>" onclick="toggleBox('<?= $boxId ?>')">
          <div>
            <div class="fw-semibold"><?= htmlspecialchars($g['city']) ?></div>
            <div class="subtext"><?= htmlspecialchars($g['country']) ?> · 식당 <?= (int)$g['cnt'] ?>개</div>
          </div>
          <div class="score-text">평균 <?= $avgText ?></div>
        </div>

        <div class="child-box" id="<?= $boxId ?>">
        <?php foreach ($g['restaurants'] as $cr): ?>
            <div class="child-card" onclick="location.href='RestaurantDetail.php?rest_id=<?= $cr['rest_id'] ?? 1 ?>'">
              <div><?= htmlspecialchars($cr['name']) ?></div>
              <div class="score-text">
                <?= is_null($cr['avg']) ? '-' : number_format($cr['avg'], 2) ?>
              </div>
            </div>
        <?php endforeach; ?>
        </div>

    <?php endforeach; ?>
    </div>

<!-- =================== COUNTRY VIEW =================== -->
<?php elseif ($view === 'country'): ?>
    <h5 class="mb-3">Roll up: 국가별</h5>
    <div class="card-soft mb-4">
    <?php foreach ($countryGroupsList as $i=>$g): ?>
        <?php
          $color = ($i%2===0? 'row-blue':'row-green');
          $boxId = "countryBox".$i;
          $avgText = is_null($g['avg'])? '-' : number_format($g['avg'],2);
        ?>
        <div class="row-item <?= $color ?>" onclick="toggleBox('<?= $boxId ?>')">
          <div>
            <div class="fw-semibold"><?= htmlspecialchars($g['country']) ?></div>
            <div class="subtext">식당 <?= (int)$g['cnt'] ?>개</div>
          </div>
          <div class="score-text">평균 <?= $avgText ?></div>
        </div>

        <div class="child-box" id="<?= $boxId ?>">
        <?php foreach ($g['restaurants'] as $cr): ?>
            <div class="child-card" onclick="location.href='RestaurantDetail.php?rest_id=<?= $cr['rest_id'] ?? 1 ?>'">
              <div>
                <div class="fw-semibold"><?= htmlspecialchars($cr['name']) ?></div>
                <div class="subtext"><?= htmlspecialchars($cr['city']) ?></div>
              </div>
              <div class="score-text">
                <?= is_null($cr['avg']) ? '-' : number_format($cr['avg'], 2) ?>
              </div>
            </div>
        <?php endforeach; ?>
        </div>

    <?php endforeach; ?>
    </div>

<?php endif; ?>

</div>
</body>
</html>
