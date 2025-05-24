<?php
session_start();
require_once 'db_connect.php';

// Lista województw
$lista_wojewodztw = [
    "dolnośląskie", "kujawsko-pomorskie", "lubelskie", "lubuskie",
    "łódzkie", "małopolskie", "mazowieckie", "opolskie",
    "podkarpackie", "podlaskie", "pomorskie", "śląskie",
    "świętokrzyskie", "warmińsko-mazurskie", "wielkopolskie", "zachodniopomorskie"
];

$isLoggedIn = isset($_SESSION['user_id']);
$username = ($isLoggedIn && isset($_SESSION['username'])) ? $_SESSION['username'] : '';
$user_id = $isLoggedIn ? intval($_SESSION['user_id']) : 0;

$kategorie = [
  "Remont Mieszkania","Remont łazienki","Remont kuchni","Elewacje","Elektrka","Hydraulika",
  "Posadzki","Tynki","Schody","Podłogi","Ogrodzenia","Wyburzenia i rozbiórki","Utylizacja",
  "Szukam Złotej rączki","Inne"
];

$miasto_filtr = isset($_GET['miasto']) ? trim($_GET['miasto']) : '';
$promien_filtr = isset($_GET['promien']) ? (int)$_GET['promien'] : 0;
$promienie_dostepne = [5, 10, 20, 30, 50];

$wojewodztwo_filtr = isset($_GET['wojewodztwo']) ? trim($_GET['wojewodztwo']) : '';

$sort = ($_GET['sort'] ?? 'newest') === 'oldest' ? 'oldest' : 'newest';
$kategoria = $_GET['kategoria'] ?? '';

$order = $sort === 'oldest' ? 'is_paid DESC, id ASC' : 'is_paid DESC, id DESC';
$where = "is_ad = 1 AND published = 1";
$params = [];

if ($kategoria && in_array($kategoria, $kategorie)) {
    $where .= " AND job_type = ?";
    $params[] = $kategoria;
}
if ($wojewodztwo_filtr && in_array($wojewodztwo_filtr, $lista_wojewodztw)) {
    $where .= " AND voivodeship = ?";
    $params[] = $wojewodztwo_filtr;
}

$stmt = $pdo->prepare("SELECT * FROM ads WHERE $where ORDER BY $order");
$stmt->execute($params);
$ads = $stmt->fetchAll();

$read_ids = [];
if ($user_id) {
    $stmt2 = $pdo->prepare("SELECT ad_id FROM read_ads WHERE user_id = ?");
    $stmt2->execute([$user_id]);
    $read_ids = $stmt2->fetchAll(PDO::FETCH_COLUMN, 0);
}

// Przygotuj skrócony opis do 85 znaków, z wielokropkiem jeśli dłuższy
function shortDesc($desc, $max = 75) {
    $desc = trim($desc);
    if (mb_strlen($desc) > $max) {
        return htmlspecialchars(mb_substr($desc, 0, $max - 3)) . '...';
    }
    return htmlspecialchars($desc);
}

function getCoordinates($city) {
    $cityEnc = urlencode($city);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=$cityEnc";
    $opts = [
        "http" => [
            "header" => "User-Agent: BudBud/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $res = @file_get_contents($url, false, $context);
    $data = json_decode($res, true);
    if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
        return [
            'lat' => (float)$data[0]['lat'],
            'lon' => (float)$data[0]['lon']
        ];
    }
    return false;
}

function haversine($lat1, $lon1, $lat2, $lon2) {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $lat1 = deg2rad($lat1);
    $lat2 = deg2rad($lat2);
    $a = sin($dLat/2) * sin($dLat/2) +
         sin($dLon/2) * sin($dLon/2) * cos($lat1) * cos($lat2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

if ($miasto_filtr && $promien_filtr && in_array($promien_filtr, $promienie_dostepne)) {
    $miasto_coords = getCoordinates($miasto_filtr);
    if ($miasto_coords) {
        $ads_okolica = [];
        foreach ($ads as $ad) {
            $miasto_ogloszenie = '';
            if (!empty($ad['city'])) {
                $miasto_ogloszenie = $ad['city'];
            } elseif (!empty($ad['address'])) {
                $miasto_ogloszenie = $ad['address'];
            }
            $miasto_ogloszenie = trim($miasto_ogloszenie);

            if (!$miasto_ogloszenie) continue;

            if (stripos($miasto_ogloszenie, 'Polska') === false) {
                $miasto_ogloszenie .= ', Polska';
            }

            $ad_coords = getCoordinates($miasto_ogloszenie);
            if ($ad_coords) {
                $dist = haversine($miasto_coords['lat'], $miasto_coords['lon'], $ad_coords['lat'], $ad_coords['lon']);
                if ($dist <= $promien_filtr) {
                    $ad['odleglosc'] = round($dist, 1);
                    $ads_okolica[] = $ad;
                }
            }
        }
        $ads = $ads_okolica;
    } else {
        $ads = [];
        $miasto_filtr_error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Forum Ogłoszeń Budowlanych</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
      background-color: #f7f8fa;
      margin: 0;
      padding: 0;
    }
    .container-main {
      display: flex;
      min-height: 100vh;
    }
    .sidebar {
      width: 230px;
      background: #1a2332;
      color: #fff;
      padding-top: 34px;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      box-shadow: 2px 0 12px #0001;
      z-index: 50;
    }
    .menu {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .menu li {
      padding: 13px 32px;
      font-size: 1.09em;
      cursor: pointer;
      border-left: 4px solid transparent;
      transition: background 0.18s, color 0.18s, border-color 0.18s;
    }
    .menu li.active,
    .menu li:hover {
      background: #242e42;
      color: #33aaff;
      border-left: 4px solid #33aaff;
    }
    .menu li.logout {
      color: #ff7276;
      font-weight: bold;
      margin-top: 40px;
    }
    .menu li.logout:hover {
      background: none;
      color: #e11;
      border-left: 4px solid transparent;
    }
    .container-content {
      flex: 1;
      padding: 32px 0 32px 0;
      min-height: 100vh;
      background: #f7f8fa;
      transition: margin-left 0.2s;
      padding-left: 32px;
    }
    .filters-wrapper {
      max-width: 800px;
      margin: 0 auto 36px auto;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 4px 18px #0001;
      padding: 32px 32px 18px 32px;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .filters-row-top, .filters-row-bottom {
      display: flex;
      flex-wrap: wrap;
      gap: 40px;
      align-items: flex-end;
      justify-content: flex-start;
      margin-bottom: 0;
    }
    .filters-row-top {
      margin-bottom: 22px;
    }
    .filters-col {
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 170px;
      flex: 1 1 170px;
      max-width: 255px;
    }
    .filters-col label {
      font-weight: bold;
      margin-bottom: 0;
      margin-left: 2px;
      font-size: 1em;
    }
    .filters-col select, .filters-col input[type="text"] {
      padding: 8px 14px;
      font-size: 1em;
      border-radius: 8px;
      border: 1px solid #bbb;
      background: #fafbfc;
      margin-right: 0;
      margin-bottom: 0;
    }
    .filters-col input[type="text"] {
      width: 100%;
    }
    .filters-col.button-col {
      min-width: 90px;
      max-width: 120px;
      flex: 0 0 90px;
      align-items: flex-end;
      justify-content: flex-end;
    }
    .filters-col.button-col button {
      padding: 8px 18px;
      font-size: 1em;
      border-radius: 8px;
      border: none;
      background: #2196f3;
      color: #fff;
      cursor: pointer;
      font-weight: bold;
      margin-left: 0;
      margin-top: 18px;
      transition: background 0.18s;
      width: 100%;
    }
    .filters-col.button-col button:hover {
      background: #1769aa;
    }
    .filters-col .reset-link {
      margin-top: 12px;
      color: #2196f3;
      text-decoration: underline;
      font-size: 0.98em;
      display: block;
    }
    .filters-col .reset-link:hover {
      color: #1769aa;
    }
    .ad-card {
      display: flex;
      align-items: center;
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 10px;
      padding: 16px;
      margin-bottom: 20px;
      max-width: 800px;
      margin-left: auto;
      margin-right: auto;
      text-decoration: none;
      color: black;
      transition: background 0.2s, border 0.2s;
      scroll-margin-top: 120px;
      cursor: pointer;
    }
    .ad-card.paid {
      border: 2.5px solid #74b9ff !important;
      box-shadow: 0 0 12px #74b9ff33;
    }
    .ad-card:hover {
      background: #f0f0f0;
    }
    .ad-card img {
      width: 120px;
      height: 90px;
      object-fit: cover;
      border-radius: 6px;
      margin-right: 20px;
      border: 1px solid #bbb;
    }
    .ad-title {
      font-size: 18px;
      font-weight: bold;
      margin: 0;
      color: #007bff;
      transition: color 0.15s;
      display: inline-block;
    }
    .ad-title.read {
      color: #888888 !important;
    }
    .ad-category {
      font-size: 0.98em;
      background: #f3e7c6;
      color: #222;
      display: inline-block;
      border-radius: 5px;
      padding: 2px 12px;
      margin-left: 10px;
      margin-bottom: 4px;
      font-weight: 600;
      border: 1px solid #e5d8b2;
    }
    .ad-distance {
      font-size:0.97em;
      color: #417b05;
      margin-top: 5px;
      margin-bottom: 2px;
      font-style: italic;
      display: block;
    }
    .miasto-error {
      color: #c00;
      background: #fff8f8;
      border: 1px solid #fcc;
      padding: 10px 18px;
      border-radius: 7px;
      max-width: 700px;
      margin: 15px auto 0 auto;
      text-align: center;
    }
    .ad-description {
      font-size: 0.98em;
      color: #666;
      margin-top: 5px;
      margin-bottom: 7px;
      display: block;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      max-width: 100%;
      word-break: break-all;
      background: #f6f6f9;
      border-radius: 7px;
      padding: 7px 13px;
    }
    @media (max-width: 1200px) {
      .filters-wrapper, .ad-card {
        max-width: 98vw;
        padding-left: 5vw;
        padding-right: 5vw;
      }
    }
    @media (max-width: 900px) {
      .sidebar { position: static; width: 100%; min-height: 0; }
      .container-content { padding-left: 0; }
      .filters-wrapper { padding: 18px 6vw 18px 6vw; }
      .filters-col input[type="text"], .filters-col select { width: 100%; }
    }
  </style>
</head>
<body>
  <div class="container-main">
    <?php if ($isLoggedIn): ?>
<nav class="sidebar">
  <ul class="menu">
    <li onclick="window.location.href='panel-klienta.php'">Panel Klienta</li>
    <li class="active" onclick="window.location.href='moje-ogloszenia.php'">Moje Ogłoszenia Budowlane</li>
    <li onclick="window.location.href='moja-sprzedaz.php'">Sprzedaż</li>
    <li onclick="window.location.href='nieruchomosci.php'">Nieruchomości</li>
    <li onclick="window.location.href='moj-profil.php'">Mój Profil</li>
    <li onclick="window.location.href='fachowcy.php'">Fachowcy</li>
    <li onclick="window.location.href='poczta.php'">Poczta</li>
    <li onclick="window.location.href='kalkulator.php'">Kalkulator</li>
    <li onclick="window.location.href='promocje.php'">Promocje</li>
    <li onclick="window.location.href='pomoc.php'">Pomoc</li>
    <li class="logout" onclick="window.location.href='logout.php'">Wyloguj się</li>
  </ul>
</nav>
    <?php endif; ?>

    <div class="container-content">
      <h1 style="text-align:center;">Forum Ogłoszeń Budowlanych</h1>

      <div class="filters-wrapper">
        <form method="get" action="">
          <div class="filters-row-top">
            <div class="filters-col">
              <label for="sort">Sortuj:</label>
              <select name="sort" id="sort">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Od najnowszych</option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Od najstarszych</option>
              </select>
            </div>
            <div class="filters-col">
              <label for="kategoria">Kategoria:</label>
              <select name="kategoria" id="kategoria">
                <option value="">Wszystkie</option>
                <?php foreach ($kategorie as $kat): ?>
                  <option value="<?= htmlspecialchars($kat) ?>" <?= $kategoria===$kat ? 'selected' : '' ?>><?= htmlspecialchars($kat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="filters-row-bottom">
            <div class="filters-col">
              <label for="wojewodztwo">Województwo:</label>
              <select name="wojewodztwo" id="wojewodztwo">
                <option value="">Wszystkie</option>
                <?php foreach ($lista_wojewodztw as $w): ?>
                  <option value="<?= htmlspecialchars($w) ?>" <?= ($wojewodztwo_filtr==$w ? 'selected':'') ?>>
                    <?= htmlspecialchars(ucfirst($w)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filters-col">
              <label for="miasto">Miasto:</label>
              <input type="text" name="miasto" id="miasto" value="<?= htmlspecialchars($miasto_filtr) ?>" placeholder="np. Wejherowo, Polska">
            </div>
            <div class="filters-col">
              <label for="promien">Odległość:</label>
              <select name="promien" id="promien">
                <option value="0">Dowolna</option>
                <?php foreach ($promienie_dostepne as $p): ?>
                  <option value="<?= $p ?>" <?= ($promien_filtr==$p ? 'selected':'') ?>>+<?= $p ?>km</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filters-col button-col">
              <button type="submit">Filtruj</button>
              <?php if ($sort !== 'newest' || $kategoria || $wojewodztwo_filtr || $miasto_filtr || $promien_filtr): ?>
                <a href="ogloszenia-publiczne.php" class="reset-link">Wyczyść filtry</a>
              <?php endif; ?>
            </div>
          </div>
        </form>
      </div>

      <?php if (isset($miasto_filtr_error) && $miasto_filtr_error): ?>
        <div class="miasto-error">
          Nie znaleziono miasta: <b><?= htmlspecialchars($miasto_filtr) ?></b>. Sprawdź pisownię i spróbuj ponownie.
        </div>
      <?php endif; ?>

      <?php if (empty($ads)): ?>
        <p style="text-align:center;">Brak opublikowanych ogłoszeń.</p>
      <?php else: ?>
        <?php foreach ($ads as $ad): 
          $images = json_decode($ad['images'], true);
          $mainImage = (!empty($images) && !empty($images[0])) ? $images[0] : 'https://via.placeholder.com/120x90?text=Brak+zdjęcia';
          $is_read = in_array($ad['id'], $read_ids);
          $is_paid = isset($ad['is_paid']) && $ad['is_paid'] ? true : false;
        ?>
          <a href="ogloszenie-publiczne.php?id=<?php echo $ad['id']; ?>"
             class="ad-card<?= $is_paid ? ' paid' : '' ?>"
             data-ad-id="<?= $ad['id'] ?>">
            <img src="<?php echo htmlspecialchars($mainImage); ?>" alt="Miniatura">
            <div>
              <div class="ad-title<?= $is_read ? ' read' : '' ?>">
                <?php echo htmlspecialchars($ad['title']); ?>
                <?php if ($ad['job_type']): ?>
                  <span class="ad-category"><?= htmlspecialchars($ad['job_type']) ?></span>
                <?php endif; ?>
              </div>
              <?php if (isset($ad['odleglosc'])): ?>
                <span class="ad-distance">Odległość: <?= $ad['odleglosc'] ?> km</span>
              <?php endif; ?>
              <div class="ad-description">
                <?= shortDesc($ad['description'], 75) ?>
              </div>
              <?php if (!empty($ad['voivodeship'])): ?>
                <span style="color:#2196f3;font-size:0.97em;">Województwo: <?= htmlspecialchars(ucfirst($ad['voivodeship'])) ?></span>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <script>
    // AJAX: Po kliknięciu zapisz ogłoszenie jako przeczytane
    document.querySelectorAll('.ad-card').forEach(function(card) {
      card.addEventListener('click', function(e) {
        var adId = this.getAttribute('data-ad-id');
        fetch('zapisz-przeczytane.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'ad_id=' + encodeURIComponent(adId)
        });
        var titleDiv = this.querySelector('.ad-title');
        if(titleDiv) titleDiv.classList.add('read');
        sessionStorage.setItem('forumScroll', window.scrollY);
      });
    });
    window.onload = function() {
      if (window.location.hash && sessionStorage.getItem('forumScroll')) {
        window.scrollTo({top: parseInt(sessionStorage.getItem('forumScroll'), 10), behavior: "auto"});
        sessionStorage.removeItem('forumScroll');
      }
    };
  </script>
</body>
</html>