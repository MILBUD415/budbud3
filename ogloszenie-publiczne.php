<?php
require_once 'db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo "<h2 style='color:red;text-align:center;'>Nie znaleziono ogłoszenia.</h2>";
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM ads WHERE id=? AND is_ad=1 AND published=1");
$stmt->execute([$id]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ad) {
    echo "<h2 style='color:red;text-align:center;'>Ogłoszenie nie istnieje lub nie jest publiczne.</h2>";
    exit;
}
$images = @json_decode($ad['images'], true);
if (empty($images)) $images = [];
$mainImage = !empty($images) ? $images[0] : 'https://via.placeholder.com/300x300?text=Brak+zdjęcia';

session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? intval($_SESSION['user_id']) : 0;

$investorId = intval($ad['user_id'] ?? 0);

$stmt = $pdo->prepare("SELECT o.*, u.first_name FROM opinions o LEFT JOIN users u ON o.author_id = u.id WHERE o.investor_id = ? ORDER BY o.id DESC");
$stmt->execute([$investorId]);
$opinions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalOpinions = count($opinions);
$totalStars = 0;
foreach ($opinions as $op) $totalStars += intval($op['stars']);
$percent = $totalOpinions > 0 ? round($totalStars / ($totalOpinions * 5) * 100) : 0;

function googleMapEmbed($address) {
    $q = urlencode($address . ', Polska');
    return '<iframe src="https://www.google.com/maps?q='.$q.'&output=embed" width="100%" height="150" style="border:0;border-radius:8px;" loading="lazy"></iframe>';
}
function shortenOpinion($text, $max = 65) {
    $text = trim($text);
    if (mb_strlen($text) > $max) {
        return mb_substr($text, 0, $max-3).'...';
    }
    return $text;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($ad['title']); ?> - Ogłoszenie Budowlane</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    body { background: #f6f7fa; margin: 0; font-family: Arial,sans-serif; }
    .page-wrapper { width: 100vw; min-height: 100vh; background: #f6f7fa; }
    .header-bar {
      display: flex; align-items: center; gap: 16px;
      margin-top: 24px; margin-left: 36px; margin-bottom: 0;
    }
    .header-logo { display: flex; align-items: center; gap: 8px; }
    .header-logo img { height: 90pxx; }
    .header-logo-text { font-size: 1.2em; font-weight: bold; color: #191919; }
    .header-title {
      font-size: 2em;
      font-weight: bold;
      color: #222;
      margin-left: 30px;
      flex: 1;
      letter-spacing: -1px;
      white-space: nowrap;
    }
    .go-back-row {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      width: 100%;
      margin: 18px 0 0 0;
      position: relative;
      z-index: 2;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }
    .back-btn {
      font-size: 1.10em;
      font-weight: bold;
      border: 3px solid #2196f3;
      color: #fff;
      background: #2196f3;
      padding: 12px 44px;
      border-radius: 9px;
      text-decoration: none;
      cursor: pointer;
      display: inline-block;
      transition: background 0.13s, color 0.13s;
      position: relative;
      margin-bottom: 7px;
    }
    .back-btn:hover {
      background: #1769aa;
      color: #fff;
      border-color: #1769aa;
    }

    .title-box {
      width: 100%; max-width: 900px; margin: 13px auto 30px auto;
      background: #fff; border-radius: 15px; box-shadow: 0 4px 16px #0001;
      font-size: 1.65em; font-weight: bold; padding: 24px 48px;
      display: flex; align-items: center; letter-spacing: -1px;
      justify-content: center;
      text-align: center;
      min-height: 48px;
    }

    .main-flex-row {
      width: 100%;
      display: flex;
      justify-content: center;
      gap: 38px;
      margin-bottom: 16px;
    }
    .ad-image-box, .ad-info-box {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 16px #0001;
      min-height: 340px;
    }
    .ad-image-box {
      padding: 24px 26px 18px 26px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-width: 525px;
      width: 525px;
      max-width: 100%;
      position: relative;
      box-sizing: border-box;
      margin-right: 0;
      margin-bottom: 0;
      margin-top: 0;
    }
    .ad-image-box img.main { max-width: 420px; max-height: 270px; border-radius: 12px; border: 1px solid #e6e6e6; cursor: zoom-in; }
    .gallery-thumbs {
      display: flex; gap: 10px; margin-top: 16px; flex-wrap: wrap; justify-content: center;
    }
    .gallery-thumbs img {
      width: 54px; height: 54px; object-fit: cover;
      border-radius: 7px; border: 2px solid #eee;
      cursor: pointer; transition: border 0.18s;
      background: #fff;
      cursor: zoom-in;
    }
    .gallery-thumbs img.active, .gallery-thumbs img:hover {
      border: 2px solid #2196f3;
    }
    .gallery-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255,255,255,0.8);
      border: none;
      color: #333;
      font-size: 34px;
      font-weight: bold;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      z-index: 10;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.15s;
      user-select: none;
    }
    .gallery-arrow.left { left: 10px; }
    .gallery-arrow.right { right: 10px; }

    .ad-info-box {
      padding: 24px 28px 20px 28px;
      min-width: 320px;
      max-width: 350px;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 7px;
      font-size: 1.08em;
    }
    .ad-info-row { margin-bottom: 7px; display: flex; align-items: center; gap: 7px;}
    .ad-info-box .label { font-weight: bold; color: #222; }
    .ad-info-box .val { color: #222; font-weight: 500;}
    .ad-info-box i { width: 20px; text-align: center; color: #555; }
    .ad-info-box .map { margin-top: 13px; border-radius: 8px; overflow: hidden; }

    .login-btn-mini {
      color: #2196f3;
      background: none;
      border: none;
      font-weight: bold;
      text-decoration: underline;
      cursor: pointer;
      font-size: 1em;
      padding: 0;
      margin: 0;
      display: inline;
    }
    .login-btn-mini:hover { color: #1769aa; }

    .desc-box {
      width: 100%; max-width: 900px; margin: 18px auto 28px auto;
      background: #fff; border-radius: 15px; box-shadow: 0 4px 16px #0001;
      font-size: 1.15em; padding: 16px 36px; 
      font-weight: 400; color: #333;
      display: flex;
      align-items: flex-start;
    }
    .desc-box b { font-weight: bold; margin-right: 8px;}

    .opinie-section {
      width: 100%;
      display: flex;
      justify-content: center;
      gap: 28px;
      margin: 0 auto 38px auto;
      flex-wrap: wrap;
      max-width: 900px;
    }
    .opinie-box {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 16px #0001;
      padding: 22px 28px 24px 28px;
      min-width: 260px;
      max-width: 340px;
      display: flex;
      flex-direction: column;
      align-items: center;
      font-size: 1.09em;
      margin-bottom: 12px;
      min-height: 210px;
      cursor: pointer;
      transition: box-shadow 0.18s, transform 0.13s;
      position: relative;
      text-align: left;
      border: 2px solid #f6f7fa;
    }
    .opinie-box:hover {
      box-shadow: 0 8px 32px #0002;
      transform: translateY(-3px) scale(1.01);
      border: 2px solid #2196f3;
    }
    .opinie-title {
      font-size: 1.25em;
      font-weight: bold;
      margin-bottom: 8px;
      margin-top: 0;
      letter-spacing: 0.5px;
      text-align: center;
      width: 100%;
    }
    .opinie-empty {
      color: #2d3bac;
      font-size: 1em;
      margin-top: 4px;
      margin-bottom: 13px;
      display: block;
      font-weight: 500;
      text-align: center;
      text-decoration: underline;
      cursor: pointer;
    }
    .opinie-add-btn {
      color: #222;
      font-weight: bold;
      font-size: 1.01em;
      margin-bottom: 11px;
      background: none;
      border: none;
      cursor: pointer;
      text-decoration: underline;
      transition: color 0.17s;
      margin-top: 0;
      width: 100%;
      text-align: center;
    }
    .opinie-add-btn:hover {
      color: #2196f3;
      text-decoration: underline;
    }
    .stars-box {
      display: flex;
      align-items: center;
      gap: 4px;
      margin-bottom: 6px;
      margin-top: 0;
      justify-content: center;
    }
    .star-btn {
      font-size: 2em;
      color: #ddd;
      background: none;
      border: none;
      cursor: pointer;
      padding: 0 2px;
      transition: color 0.18s;
    }
    .star-btn.filled {
      color: #ffd700;
      text-shadow: 0 0 8px #ffeb70cc;
    }
    .opinie-percent {
      margin-top: 2px;
      margin-bottom: 6px;
      font-size: 1.07em;
      color: #222;
      font-weight: bold;
      text-align: center;
    }
    .opinie-percent-desc {
      font-size: 0.97em;
      color: #333;
      margin-bottom: 2px;
      text-align: center;
      font-weight: bold;
    }
    .mini-opinion {
      background: #f5f7fb;
      border-radius: 8px;
      padding: 7px 10px 6px 10px;
      margin-top: 10px;
      width: 100%;
      font-size: 0.98em;
      color: #333;
      min-height: 36px;
      margin-bottom: 7px;
      box-sizing: border-box;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    .mini-opinion .author {
      color: #555;
      font-weight: bold;
      margin-bottom: 2px;
      font-size: 0.98em;
    }
    .mini-opinion .opinion-stars {
      font-size: 1.06em;
      margin-bottom: 1px;
      color: #ffd700;
    }
    .mini-opinion .content {
      margin: 4px 0 0 0;
      font-size: 1em;
      word-break: break-word;
    }
    .modal-bg {
      display: none;
      position: fixed;
      top:0; left:0; width:100vw; height:100vh;
      background: rgba(0,0,0,0.45);
      z-index: 10001;
      align-items: center;
      justify-content: center;
    }
    .modal-opinia {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 40px #0002;
      padding: 38px 32px 22px 32px;
      min-width: 330px;
      max-width: 95vw;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      z-index: 10002;
      font-size: 1.09em;
    }
    .modal-opinia .close-modal {
      position: absolute;
      top: 19px; right: 23px;
      font-size: 1.8em;
      color: #888;
      background: none;
      border: none;
      cursor: pointer;
      transition: color 0.18s;
    }
    .modal-opinia .close-modal:hover { color: #c82333; }
    .modal-opinia textarea {
      width: 98%; min-width: 230px; max-width: 430px;
      border-radius: 7px;
      font-size: 1em;
      padding: 9px 12px;
      border: 1.5px solid #ccc;
      margin: 8px 0 18px 0;
      resize: vertical;
      min-height: 80px;
      max-height: 230px;
    }
    .modal-opinia .stars-box { margin-bottom: 13px; margin-top: 4px;}
    .modal-opinia .opinie-percent { margin-bottom: 16px; }
    .modal-opinia .zatwierdz-btn {
      font-size: 1.12em;
      font-weight: bold;
      background: #2196f3;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 9px 25px;
      margin-top: 0;
      cursor: pointer;
      transition: background 0.16s;
      margin-bottom: 0;
    }
    .modal-opinia .zatwierdz-btn:hover {
      background: #1769aa;
    }
    @media (max-width: 1200px) {
      .main-flex-row { flex-wrap: wrap; }
      .opinie-section { flex-direction: column; gap: 16px; }
      .title-box { max-width: 97vw;}
      .desc-box { max-width: 97vw;}
    }
    @media (max-width: 1000px) {
      .main-flex-row { flex-direction: column; gap: 22px;}
      .opinie-section { max-width: 97vw; margin-top: 22px; }
      .ad-image-box, .ad-info-box { min-width: 0; width: 100%; max-width: 700px; }
      .header-title { font-size: 1.3em; }
      .header-logo img { height: 32px; }
    }
    @media (max-width: 700px) {
      .header-bar { flex-direction: column; align-items: flex-start; gap: 20px; }
      .gallery-arrow.left { left: 2px; }
      .gallery-arrow.right { right: 2px; }
      .ad-image-box { min-width: 0; width: 100%; }
      .opinie-section { flex-direction: column; gap: 20px;}
      .opinie-box { min-width: 0; width: 100%; max-width: 100vw;}
    }
  </style>
</head>
<body>
  <div class="page-wrapper">
    <div class="header-bar">
      <div class="header-logo">
        <img src="assets/tools-icon-login.png" alt="BudBud logo">
      </div>
      <span class="header-title">Baza Ogłoszeń budowlanych</span>
    </div>

    <div class="go-back-row">
      <a href="ogloszenia-publiczne.php" class="back-btn" id="backToForum">Powrót do Ogłoszeń</a>
    </div>
    <div class="title-box"><?php echo htmlspecialchars($ad['title']); ?></div>

    <div class="main-flex-row">
      <div class="ad-image-box">
        <button type="button" class="gallery-arrow left" id="galleryPrev" style="display:<?= count($images) > 1 ? 'flex':'none' ?>;"><i class="fa fa-arrow-left"></i></button>
        <img src="<?php echo htmlspecialchars($mainImage); ?>" alt="Zdjęcie ogłoszenia" id="mainAdImg" class="main zoomable-img">
        <button type="button" class="gallery-arrow right" id="galleryNext" style="display:<?= count($images) > 1 ? 'flex':'none' ?>;"><i class="fa fa-arrow-right"></i></button>
        <?php if (count($images) > 1): ?>
          <div class="gallery-thumbs">
            <?php foreach ($images as $idx => $img): ?>
              <img src="<?php echo htmlspecialchars($img); ?>" alt="Miniatura <?php echo $idx+1; ?>" class="<?php echo $idx==0?'active':''; ?> zoomable-img" onclick="setMainImg('<?php echo htmlspecialchars($img); ?>', this)">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="ad-info-box">
        <div class="ad-info-row"><i class="fa fa-user"></i><span class="label">inwestor:</span>
          <span class="val">
          <?php if ($isLoggedIn): ?>
              <?php echo htmlspecialchars($ad['investor']); ?>
          <?php else: ?>
              <button class="login-btn-mini" onclick="window.location.href='index.html';event.stopPropagation();">Zaloguj się</button>
          <?php endif; ?>
          </span>
        </div>
        <div class="ad-info-row"><i class="fa fa-phone"></i><span class="label">Kontakt:</span>
          <span class="val">
          <?php if ($isLoggedIn): ?>
              <?php echo htmlspecialchars($ad['contact']); ?>
          <?php else: ?>
              <button class="login-btn-mini" onclick="window.location.href='index.html';event.stopPropagation();">Zaloguj się</button>
          <?php endif; ?>
          </span>
        </div>
        <div class="ad-info-row"><i class="fa fa-location-dot"></i><span class="label">Adres:</span>
          <span class="val">
          <?php if ($isLoggedIn): ?>
              <?php echo htmlspecialchars($ad['address']); ?>
          <?php else: ?>
              <button class="login-btn-mini" onclick="window.location.href='index.html';event.stopPropagation();">Zaloguj się</button>
          <?php endif; ?>
          </span>
        </div>
        <div class="ad-info-row"><i class="fa fa-toolbox"></i><span class="label">Kategoria:</span>
          <span class="val"><?php echo htmlspecialchars($ad['job_type']); ?></span>
        </div>
        <div class="ad-info-row"><i class="fa fa-calendar-days"></i><span class="label">Termin rozpoczęcia:</span> <span class="val"><?php echo htmlspecialchars($ad['start_date']); ?></span></div>
        <div class="ad-info-row"><i class="fa fa-calendar-check"></i><span class="label">Termin zakończenia:</span> <span class="val"><?php echo htmlspecialchars($ad['end_date']); ?></span></div>
        <div class="ad-info-row"><i class="fa fa-user-helmet-safety"></i><span class="label">Nadzór budowlany:</span> <span class="val"><?php echo htmlspecialchars($ad['supervision']); ?></span></div>
        <div class="map"><?= googleMapEmbed($ad['address']); ?></div>
      </div>
    </div>
    <div class="desc-box"><b>opis:</b> <?php echo nl2br(htmlspecialchars($ad['description'])); ?></div>

    <div class="opinie-section">
      <?php
        $op_box1 = isset($opinions[0]) ? [$opinions[0]] : [];
        $op_box2 = [];
        if(isset($opinions[1])) $op_box2[] = $opinions[1];
        if(isset($opinions[2])) $op_box2[] = $opinions[2];
      ?>

      <div class="opinie-box" onclick="window.location='komentarze-o-mnie.php?user=<?=$investorId?>';" style="cursor:pointer;">
        <div class="opinie-title">Opinia</div>
        <?php if ($isLoggedIn): ?>
          <button class="opinie-add-btn" id="addOpinionBtn">Dodaj opinię o Inwestorze +</button>
        <?php else: ?>
          <span style="color:#999; font-size:1em; margin-bottom:9px;display:block;text-align:center;">Zaloguj się, aby dodać opinię</span>
        <?php endif; ?>
        <div class="stars-box" id="opinieStarsBox">
          <?php for($i=1;$i<=5;$i++): ?>
            <button class="star-btn<?= ($percent/20)>=$i?' filled':'' ?>" type="button" disabled>★</button>
          <?php endfor; ?>
        </div>
        <div class="opinie-percent" style="font-weight: bold;"><?= $percent ?>%</div>
        <div class="opinie-percent-desc" style="font-weight:bold; text-align:center;">Jest Zadowolonych z Tego Inwestora</div>
        <?php
          if (count($op_box1) === 0) {
            echo '<span class="opinie-empty">( Brak opinii )</span>';
          } else {
            foreach($op_box1 as $op) {
              echo '<div class="mini-opinion">';
              echo '<span class="author">'.htmlspecialchars($op['first_name'] ?? "Użytkownik").'</span>';
              echo '<span class="opinion-stars">';
              for($i=1;$i<=5;$i++) echo '<span'.($i<=$op['stars']?' style="color:#ffd700;"':'').'>★</span>';
              echo '</span>';
              echo '<span class="content">'.htmlspecialchars(shortenOpinion($op['content'], 65)).'</span>';
              echo '</div>';
            }
          }
        ?>
      </div>

      <div class="opinie-box" onclick="window.location='komentarze-o-mnie.php?user=<?=$investorId?>';" style="cursor:pointer;">
        <div class="opinie-title">Opinia</div>
        <?php
          if (count($op_box2) === 0) {
            echo '<span class="opinie-empty">( Brak opinii )</span>';
          } else {
            foreach($op_box2 as $op) {
              echo '<div class="mini-opinion">';
              echo '<span class="author">'.htmlspecialchars($op['first_name'] ?? "Użytkownik").'</span>';
              echo '<span class="opinion-stars">';
              for($i=1;$i<=5;$i++) echo '<span'.($i<=$op['stars']?' style="color:#ffd700;"':'').'>★</span>';
              echo '</span>';
              echo '<span class="content">'.htmlspecialchars(shortenOpinion($op['content'], 65)).'</span>';
              echo '</div>';
            }
          }
        ?>
      </div>
    </div>

    <div id="imgModal" style="display:none; position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);z-index:9999;justify-content:center;align-items:center;">
      <button class="zoom-arrow left" id="modalPrev" style="display:none;">&#8592;</button>
      <span id="imgModalClose" style="position:absolute;top:36px;right:46px;font-size:38px;font-weight:bold;color:#fff;cursor:pointer;z-index:10001;">&times;</span>
      <img id="imgModalImg" src="" alt="Duże zdjęcie" style="max-width:90vw;max-height:88vh;box-shadow:0 0 28px #000a;background:#fff;border-radius:10px;display:block;z-index:10000;">
      <button class="zoom-arrow right" id="modalNext" style="display:none;">&#8594;</button>
    </div>
    <div class="modal-bg" id="opiniaModalBg">
      <div class="modal-opinia">
        <button class="close-modal" id="closeOpiniaModal" title="Zamknij okno">&times;</button>
        <div class="opinie-title">Dodaj opinię o inwestorze</div>
        <div class="stars-box" id="modalStarsBox">
          <?php for($i=1;$i<=5;$i++): ?>
            <button class="star-btn" type="button" data-star="<?= $i ?>">★</button>
          <?php endfor; ?>
        </div>
        <textarea id="opiniaContent" maxlength="500" placeholder="Twoja opinia (max 500 znaków)" required></textarea>
        <div class="opinie-percent" id="modalPercent">0%</div>
        <button class="zatwierdz-btn" id="zatwierdzOpiniaBtn">Zatwierdź opinię</button>
      </div>
    </div>
  </div>
  <script>
    let images = <?php echo json_encode($images); ?>;
    let currentImgIdx = 0;
    function setMainImg(url, thumb) {
      document.getElementById('mainAdImg').src = url;
      document.querySelectorAll('.gallery-thumbs img').forEach(function(img,i){
        img.classList.remove('active');
        if(img === thumb) currentImgIdx = i;
        if(img.src === url) currentImgIdx = i;
      });
      thumb.classList.add('active');
    }
    document.getElementById('galleryPrev').onclick = function(e){
      e.stopPropagation();
      if(images.length < 2) return;
      currentImgIdx = (currentImgIdx-1+images.length)%images.length;
      let url = images[currentImgIdx];
      document.getElementById('mainAdImg').src = url;
      document.querySelectorAll('.gallery-thumbs img').forEach(function(img,i){
        img.classList.toggle('active', i===currentImgIdx);
      });
    }
    document.getElementById('galleryNext').onclick = function(e){
      e.stopPropagation();
      if(images.length < 2) return;
      currentImgIdx = (currentImgIdx+1)%images.length;
      let url = images[currentImgIdx];
      document.getElementById('mainAdImg').src = url;
      document.querySelectorAll('.gallery-thumbs img').forEach(function(img,i){
        img.classList.toggle('active', i===currentImgIdx);
      });
    }
    document.getElementById('backToForum').addEventListener('click', function(e) {
      e.preventDefault();
      if (sessionStorage.forumScroll) {
        window.location = this.href + '#scroll';
      } else {
        window.location = this.href;
      }
    });

    document.addEventListener('DOMContentLoaded', function() {
      const modal = document.getElementById('imgModal');
      const modalImg = document.getElementById('imgModalImg');
      const modalClose = document.getElementById('imgModalClose');
      const modalPrev = document.getElementById('modalPrev');
      const modalNext = document.getElementById('modalNext');
      let zoomImgs = Array.from(document.querySelectorAll('.zoomable-img'));
      let zoomIdx = 0;

      function updateModalImg(idx) {
        zoomIdx = (idx + zoomImgs.length) % zoomImgs.length;
        modalImg.src = zoomImgs[zoomIdx].src;
      }
      document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('zoomable-img')) {
          zoomImgs = Array.from(document.querySelectorAll('.zoomable-img'));
          zoomIdx = zoomImgs.findIndex(img => img === e.target);
          modalImg.src = e.target.src;
          modal.style.display = 'flex';
          document.body.style.overflow='hidden';
          modalPrev.style.display = (zoomImgs.length > 1) ? 'flex' : 'none';
          modalNext.style.display = (zoomImgs.length > 1) ? 'flex' : 'none';
        }
      });
      modalPrev.onclick = function(e){ e.stopPropagation(); updateModalImg(zoomIdx-1);}
      modalNext.onclick = function(e){ e.stopPropagation(); updateModalImg(zoomIdx+1);}
      modalClose.onclick = function(){ modal.style.display='none'; document.body.style.overflow=''; };
      modal.onclick = function(e){ if(e.target===modal) { modal.style.display='none'; document.body.style.overflow=''; } };
      document.addEventListener('keydown', function(e){
        if(modal.style.display !== 'flex') return;
        if(e.key==='Escape') { modal.style.display='none'; document.body.style.overflow=''; }
        if(e.key==='ArrowLeft') updateModalImg(zoomIdx-1);
        if(e.key==='ArrowRight') updateModalImg(zoomIdx+1);
      });

      let opiniaModalBg = document.getElementById('opiniaModalBg');
      let addOpinionBtn = document.getElementById('addOpinionBtn');
      let closeOpiniaModal = document.getElementById('closeOpiniaModal');
      let zatwierdzOpiniaBtn = document.getElementById('zatwierdzOpiniaBtn');
      let modalStarsBox = document.getElementById('modalStarsBox');
      let opiniaContent = document.getElementById('opiniaContent');
      let modalPercent = document.getElementById('modalPercent');
      let selectedStars = 0;

      if(addOpinionBtn){
        addOpinionBtn.onclick = function(e){
          e.stopPropagation();
          opiniaModalBg.style.display = "flex";
          document.body.style.overflow = "hidden";
          selectedStars = 0;
          updateStars(0);
          opiniaContent.value = "";
          modalPercent.textContent = "0%";
        };
      }
      if(closeOpiniaModal){
        closeOpiniaModal.onclick = function(){
          opiniaModalBg.style.display = "none";
          document.body.style.overflow = "";
        };
      }
      if(opiniaModalBg){
        opiniaModalBg.onclick = function(e){
          if(e.target === opiniaModalBg){
            opiniaModalBg.style.display = "none";
            document.body.style.overflow = "";
          }
        };
      }
      function updateStars(stars){
        selectedStars = stars;
        Array.from(modalStarsBox.querySelectorAll('.star-btn')).forEach(function(btn,i){
          btn.classList.toggle('filled', i < stars);
        });
        modalPercent.textContent = (stars*20) + "%";
      }
      Array.from(modalStarsBox.querySelectorAll('.star-btn')).forEach(function(btn,idx){
        btn.onmouseenter = function(){ updateStars(idx+1); };
        btn.onclick = function(){ updateStars(idx+1); };
      });
      modalStarsBox.onmouseleave = function(){ updateStars(selectedStars); };

      if(zatwierdzOpiniaBtn){
        zatwierdzOpiniaBtn.onclick = function(){
          if(selectedStars === 0){
            alert("Zaznacz ilość gwiazdek!");
            return;
          }
          if(opiniaContent.value.trim() === ""){
            alert("Wpisz treść opinii!");
            return;
          }
          var xhr = new XMLHttpRequest();
          xhr.open("POST", "dodaj-opinie.php");
          xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
          xhr.onload = function(){
            if(xhr.status === 200){
              location.reload();
            }else{
              alert("Błąd podczas zapisu opinii!\n" + xhr.responseText);
            }
          };
          xhr.send("investor_id=<?= $investorId ?>&stars="+selectedStars+"&content="+encodeURIComponent(opiniaContent.value));
        };
      }
    });
  </script>
</body>
</html>