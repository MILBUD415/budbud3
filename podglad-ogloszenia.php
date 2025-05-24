<?php
session_start();
require_once 'db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) {
    echo "<h2 style='color:red;text-align:center;'>Nieprawidłowe ID ogłoszenia.</h2>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM ads WHERE id = ?");
$stmt->execute([$id]);
$ad = $stmt->fetch();

if (!$ad) {
    echo "<h2 style='text-align:center; margin-top:50px; font-weight:bold;'>Nie znaleziono ogłoszenia.</h2>";
    exit;
}

$images = [];
if (!empty($ad['images'])) {
    $images = @json_decode($ad['images'], true);
    if (!is_array($images)) $images = [];
}
$mainImage = (!empty($images) && !empty($images[0])) ? $images[0] : "https://via.placeholder.com/350x350?text=Brak+zdjecia";
$title = htmlspecialchars($ad['title']);
$investor = htmlspecialchars($ad['investor']);
$job_type = htmlspecialchars($ad['job_type']);
$contact = htmlspecialchars($ad['contact']);
$address = htmlspecialchars($ad['address']);
$start_date = htmlspecialchars($ad['start_date']);
$end_date = htmlspecialchars($ad['end_date']);
$supervision = htmlspecialchars($ad['supervision']);
$description = nl2br(htmlspecialchars($ad['description']));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Podgląd Ogłoszenia</title>
  <meta name="viewport" content="width=1200">
  <link rel="stylesheet" href="ogloszenie-form.css">
  <style>
    body { background: #f7f8fa; margin: 0; font-family: 'Segoe UI', Arial, sans-serif;}
    .container-main { display: flex; }
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
      position: fixed;
      left: 0; top: 0; bottom: 0;
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
      padding: 25px 0 32px 0;
      min-height: 100vh;
      background: #f7f8fa;
      padding-left: 230px; /* przesunięcie pod panel */
      transition: margin-left 0.2s;
    }
    /* HEADER BAR */
    .header-bar {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 23px;
      margin-top: 0;
      margin-left: 0;
      width: 100%;
      max-width: 1200px;
    }
    .header-logo {
      display: flex;
      align-items: center;
      margin-right: 0;
    }
    .header-logo img {
      height: 96px;  /* 64px * 1.5 = 96px */
      width: 96px;
      margin-right: 0;
      display: block;
    }
    /* Usunięto .header-logo-text */
    .header-title {
      font-size: 2.2em;
      font-weight: bold;
      color: #191919;
      letter-spacing: -1px;
      font-family: Arial, Helvetica, sans-serif;
      margin-left: 12px; /* zmniejszony odstęp do logo */
      margin-top: 0;
      margin-bottom: 0;
      white-space: nowrap;
      line-height: 1.1;
    }
    .action-btns { margin-left: auto; display: flex; gap: 10px;}
    .action-btn {
      padding: 10px 26px; border: none; border-radius: 6px; font-size: 1.1em; font-weight: 600;
      cursor: pointer; transition: background 0.17s; margin-left: 2px;
    }
    .action-btn.edit { background: #ffd600; color: #222; }
    .action-btn.edit:hover { background: #ffe066;}
    .action-btn.delete { background: #ff5252; color: #fff;}
    .action-btn.delete:hover { background: #c62828;}
    .main-ad-flex {
      display: flex; gap: 24px; align-items: stretch;
      margin: 0 auto 22px auto; max-width: 1200px; min-height: 410px;
    }
    .ad-gallery-card {
      flex: 1.5; background: #fff; border-radius: 18px; box-shadow: 0 4px 20px #0001;
      display: flex; flex-direction:column; align-items: center; justify-content: flex-start; position: relative; min-height: 390px; padding-top: 28px;
    }
    .gallery-main-img {
      width: 340px;
      height: 340px;
      object-fit: contain;
      border-radius: 14px;
      border: 1px solid #eee;
      box-shadow: 0 0 8px #ddd;
      margin-bottom: 18px;
      background: #fff;
      transition: box-shadow 0.2s;
      display: block;
      cursor: zoom-in;
    }
    .gallery-thumbs {
      display: flex;
      justify-content: center;
      gap: 14px;
      margin-bottom: 16px;
    }
    .gallery-thumb-img {
      width: 62px;
      height: 62px;
      object-fit: cover;
      border-radius: 8px;
      border: 2px solid #ccc;
      cursor: pointer;
      transition: border 0.2s;
      background: #fff;
      cursor: zoom-in;
    }
    .gallery-thumb-img.active {
      border: 2px solid #007bff;
      box-shadow: 0 0 6px #007bff55;
    }
    .zoom-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      z-index: 10002;
      background: rgba(255,255,255,0.7);
      border: none;
      color: #333;
      font-size: 38px;
      font-weight: bold;
      width: 56px;
      height: 56px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.15s;
      user-select: none;
    }
    .zoom-arrow:hover { background: #2196f3; color: #fff; }
    .zoom-arrow.left { left: 18px; }
    .zoom-arrow.right { right: 18px; }
    .ad-details-card {
      flex: 1;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 20px #0001;
      padding: 26px 28px;
      min-width: 320px;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      margin-bottom: 18px;
    }
    .ad-details-list {
      font-size: 1.14em;
      line-height: 2.1;
      margin-bottom: 18px;
    }
    .ad-details-list b { color: #191919; }
    .ad-details-list .icon { margin-right: 8px; }
    .map-box { margin-top: 10px; border-radius: 12px; overflow: hidden; border: 1px solid #eee; background: #fafafa; }
    .ad-desc-card {
      max-width: 1200px;
      margin: 0 auto 30px auto;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 20px #0001;
      padding: 24px 30px;
      font-size: 1.18em;
      color: #222;
      word-break: break-word;
      white-space: pre-wrap;
      overflow-wrap: break-word;
    }
    .ad-desc-title { font-weight: bold; margin-bottom: 6px; color: #6e6e6e;}
    @media (max-width: 1100px) {
      .main-ad-flex, .ad-desc-card { max-width: 98vw; }
      .header-bar { max-width: 98vw; width: auto; }
    }
    @media (max-width: 900px) {
      .container-content { padding-left: 0; margin-left: 0; }
      .sidebar { display: none; }
      .main-ad-flex { flex-direction: column; align-items: stretch;}
      .zoom-arrow.left { left: 2px; }
      .zoom-arrow.right { right: 2px; }
      .header-bar { width: 100vw; max-width: 100vw; }
      .header-title { font-size: 1.3em; max-width: 150px; }
      .header-logo img { height: 96x; width: 96px; }
    }
    body.modal-open .gallery-arrow,
    body.modal-open .zoom-arrow.gallery { display: none !important; }
    #imgModal .zoom-arrow { display: flex !important; }
  </style>
</head>
<body>
  <div class="container-main">
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
    <div class="container-content">
      <!-- HEADER BAR -->
      <div class="header-bar">
        <span class="header-logo">
          <img src="assets/tools-icon-login.png" alt="BudBud logo">
        </span>
        <span class="header-title">Podgląd Ogłoszenia</span>
        <div class="action-btns">
          <button class="action-btn edit" onclick="window.location.href='ogloszenie-form.php?id=<?= $id ?>';return false;">Edytuj</button>
          <button class="action-btn delete" onclick="if(confirm('Czy na pewno usunąć ogłoszenie?'))window.location.href='usun-ogloszenie.php?id=<?= $id ?>';return false;">Usuń</button>
        </div>
      </div>
      <!-- /HEADER BAR -->
      <div class="main-ad-flex">
        <div class="ad-gallery-card">
          <button type="button" class="zoom-arrow gallery left" id="galleryPrev" style="display:<?= count($images) > 1 ? 'flex':'none' ?>;left:10px;top:170px;position:absolute;">&#8592;</button>
          <img id="mainPhoto" src="<?= htmlspecialchars($mainImage) ?>" class="gallery-main-img zoomable-img" alt="Zdjęcie ogłoszenia">
          <button type="button" class="zoom-arrow gallery right" id="galleryNext" style="display:<?= count($images) > 1 ? 'flex':'none' ?>;right:10px;top:170px;position:absolute;">&#8594;</button>
          <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs">
              <?php foreach ($images as $i => $img): ?>
                <img src="<?= htmlspecialchars($img) ?>"
                     class="gallery-thumb-img<?= $i == 0 ? ' active' : '' ?> zoomable-img"
                     alt="Miniaturka"
                     data-img="<?= htmlspecialchars($img) ?>"
                     onclick="setMainPhoto(this)">
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="ad-details-card">
          <div class="ad-details-list">
            <div><span class="icon">👤</span> <b>inwestor:</b> <?= $investor ?></div>
            <div><span class="icon">🛠️</span> <b>Kategoria:</b> <span style="color:#d32f2f;font-weight:600;"><?= $job_type ?></span></div>
            <div><span class="icon">📞</span> <b>Kontakt:</b> <?= $contact ?></div>
            <div><span class="icon">📍</span> <b>Adres:</b> <?= $address ?></div>
            <div><span class="icon">🗓️</span> <b>Termin rozpoczęcia:</b> <?= $start_date ?></div>
            <div><span class="icon">⏱️</span> <b>Termin zakończenia:</b> <?= $end_date ?></div>
            <div><span class="icon">🧑‍💼</span> <b>Nadzór budowlany:</b> <span style="font-weight:600;"><?= $supervision ?></span></div>
          </div>
          <div class="map-box">
            <iframe src="https://maps.google.com/maps?q=<?= urlencode($address) ?>&t=&z=13&ie=UTF8&iwloc=&output=embed"
                    width="100%" height="150" frameborder="0" style="border:0;" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>
          </div>
        </div>
      </div>
      <div class="ad-desc-card">
        <span class="ad-desc-title">opis:</span>
        <?= $description ?>
      </div>
    </div>
  </div>
  <!-- MODAL do powiększania zdjęć -->
  <div id="imgModal" style="display:none; position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);z-index:9999;justify-content:center;align-items:center;">
    <button class="zoom-arrow left" id="modalPrev" style="display:none;">&#8592;</button>
    <span id="imgModalClose" style="position:absolute;top:36px;right:46px;font-size:38px;font-weight:bold;color:#fff;cursor:pointer;z-index:10001;">&times;</span>
    <img id="imgModalImg" src="" alt="Duże zdjęcie" style="max-width:90vw;max-height:88vh;box-shadow:0 0 28px #000a;background:#fff;border-radius:10px;display:block;z-index:10000;">
    <button class="zoom-arrow right" id="modalNext" style="display:none;">&#8594;</button>
  </div>
  <script>
    // Miniatury + podmiana głównego zdjęcia
    let images = <?php echo json_encode($images); ?>;
    let currentImgIdx = 0;
    function setMainPhoto(el) {
      document.getElementById('mainPhoto').src = el.dataset.img;
      document.querySelectorAll('.gallery-thumb-img').forEach(function(img,i){
        img.classList.remove('active');
        if(img===el) currentImgIdx = i;
      });
      el.classList.add('active');
    }
    // Strzałki galeria
    function showGalleryArrows() {
      document.getElementById('galleryPrev').style.display = (images.length > 1) ? 'flex' : 'none';
      document.getElementById('galleryNext').style.display = (images.length > 1) ? 'flex' : 'none';
    }
    document.getElementById('galleryPrev').onclick = function(e){
      e.stopPropagation();
      if(images.length < 2) return;
      currentImgIdx = (currentImgIdx-1+images.length)%images.length;
      let url = images[currentImgIdx];
      document.getElementById('mainPhoto').src = url;
      document.querySelectorAll('.gallery-thumb-img').forEach(function(img,i){
        img.classList.toggle('active', i===currentImgIdx);
      });
    }
    document.getElementById('galleryNext').onclick = function(e){
      e.stopPropagation();
      if(images.length < 2) return;
      currentImgIdx = (currentImgIdx+1)%images.length;
      let url = images[currentImgIdx];
      document.getElementById('mainPhoto').src = url;
      document.querySelectorAll('.gallery-thumb-img').forEach(function(img,i){
        img.classList.toggle('active', i===currentImgIdx);
      });
    }
    // MODAL powiększania zdjęcia + przewijanie strzałkami
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
          document.body.classList.add('modal-open'); // Dodane: ukryj galeryjne strzałki
          modal.style.display = 'flex';
          document.body.style.overflow='hidden';
          modalPrev.style.display = (zoomImgs.length > 1) ? 'flex' : 'none';
          modalNext.style.display = (zoomImgs.length > 1) ? 'flex' : 'none';
        }
      });
      modalPrev.onclick = function(e){ e.stopPropagation(); updateModalImg(zoomIdx-1);}
      modalNext.onclick = function(e){ e.stopPropagation(); updateModalImg(zoomIdx+1);}
      modalClose.onclick = function(){
        modal.style.display='none';
        document.body.classList.remove('modal-open'); // Dodane: przywróć galeryjne strzałki
        document.body.style.overflow='';
      };
      modal.onclick = function(e){
        if(e.target===modal) {
          modal.style.display='none';
          document.body.classList.remove('modal-open');
          document.body.style.overflow='';
        }
      };
      document.addEventListener('keydown', function(e){
        if(modal.style.display !== 'flex') return;
        if(e.key==='Escape') {
          modal.style.display='none';
          document.body.classList.remove('modal-open');
          document.body.style.overflow='';
        }
        if(e.key==='ArrowLeft') updateModalImg(zoomIdx-1);
        if(e.key==='ArrowRight') updateModalImg(zoomIdx+1);
      });
    });
  </script>
</body>
</html>