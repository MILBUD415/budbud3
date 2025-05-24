<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM ads WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$ads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Moje Ogłoszenia Budowlane</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { font-family: 'Segoe UI', Arial, Helvetica, sans-serif; background: #f7f8fa; margin: 0; }
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
      /* margin-left: 230px;  USUNIĘTE! */
      padding-left: 32px; /* Dodany padding, by nie kleić się do panelu */
      transition: margin-left 0.2s;
    }
    .header-bar {
      display: flex;
      align-items: center;
      gap: 18px;
      margin-bottom: 24px;
      margin-top: 8px;
      margin-left: 16px;
    }
    .header-logo {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-right: 0;
    }
    .header-logo img {
      height: 96px;
      width: 96px;
      display: block;
    }
    .header-logo-text {
      font-size: 1.22em;
      font-weight: bold;
      margin-top: 2px;
      letter-spacing: -1px;
      color: #191919;
      font-family: Arial, Helvetica, sans-serif;
    }
    .header-title {
      font-size: 2em;
      font-weight: 600;
      color: #191919;
      letter-spacing: -1px;
      margin-left: 20px;
      font-family: Arial, Helvetica, sans-serif;
      margin-top: 5px;
    }
    .add-ad-section, .ad-item {
      background: white; border: 1px solid #ddd; border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.08); padding: 20px;
      display: flex; align-items: center; justify-content: flex-start; margin-bottom: 24px;
      transition: box-shadow 0.1s;
    }
    .add-ad-section { cursor: pointer; gap: 14px; }
    .add-ad-section:hover { background-color: #fafafa; box-shadow: 0 6px 12px #ccc3; }
    .custom-image { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center;
      justify-content: center; font-size: 24px; color: white; font-weight: bold; background-color: #4CAF50; flex-shrink: 0;}
    .add-ad-label {
      font-weight: bold;
      font-size: 1.22em;
      color: #222;
      margin-left: 4px;
    }
    .ad-title-link { color: #222; text-decoration: none; font-size: 1.18em; font-weight: bold;}
    .ad-title-link:hover { text-decoration: underline; }
    .ad-image-thumb { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; margin-right: 18px; border: 1px solid #ddd; }
    .ad-content { display: flex; align-items: center; flex: 1; gap: 16px; }
    .forum-btn {
      margin-bottom: 18px;
      margin-left: 0;
      padding: 10px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      background: #2196f3;
      color: #fff;
      transition: background 0.16s;
      font-size: 1.08em;
      display: inline-block;
      box-shadow: 0 2px 8px #0001;
    }
    .forum-btn:hover {
      background: #1769aa;
    }
    .publish-btn {
      margin-left: 18px;
      padding: 7px 16px;
      border: none;
      border-radius: 7px;
      cursor: pointer;
      font-weight: bold;
      background: #4caf50;
      color: #fff;
      transition: background 0.16s;
      font-size: 1em;
      text-align: center;
      text-decoration: none;
      display: inline-block;
    }
    .publish-btn.published {
      background: #888;
      cursor: default;
      pointer-events: none;
    }
    .publish-btn.unpublish {
      background: #e44;
      color: #fff;
    }
    .ad-actions {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-left: 20px;
    }
    .status-badge {
      margin-left: 16px;
      font-size: 0.98em;
      padding: 3px 12px;
      border-radius: 8px;
      font-weight: bold;
      color: #fff;
      background: #bbb;
      display: inline-block;
    }
    .status-badge.published { background: #4caf50; }
    .status-badge.unpublished { background: #e44; }
    .add-paid-ad-section {
      background: #e6f0fb !important;
      border: 1px solid #b2d4f7 !important;
      color: #2162ad !important;
      gap: 14px;
      margin-bottom: 24px;
      cursor: pointer;
      display: flex;
      align-items: center;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.08);
      padding: 20px;
      font-weight: bold;
      font-size: 1.15em;
      transition: background 0.13s;
    }
    .add-paid-ad-section:hover {
      background: #d1e8fb !important;
    }
    .custom-image-blue {
      width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center;
      justify-content: center; font-size: 24px; color: white; font-weight: bold; background-color: #4fa5e6; flex-shrink: 0;
    }
    .add-paid-label {
      font-weight: bold;
      font-size: 1.19em;
      color: #2162ad;
      margin-left: 4px;
    }
    @media (max-width: 900px) {
      .container-content { padding-left: 0; }
      .sidebar { display: none; }
    }
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
      <div class="header-bar">
        <span class="header-logo">
          <img src="assets/tools-icon-login.png" alt="BudBud logo">
        </span>
        <span class="header-title">Moje Ogłoszenia Budowlane</span>
      </div>

      <button class="forum-btn" onclick="window.location.href='ogloszenia-publiczne.php'">
        Forum ogłoszeń
      </button>

      <div class="add-ad-section" onclick="window.location.href='ogloszenie-form.php'">
        <div class="custom-image">+</div>
        <span class="add-ad-label">Dodaj Darmowe Ogłoszenie</span>
      </div>
      <div class="add-paid-ad-section" onclick="window.location.href='ogloszenie-form.php?typ=platne'">
        <div class="custom-image-blue">+</div>
        <span class="add-paid-label">Dodaj Płatne Ogłoszenie</span>
      </div>

      <?php if (empty($ads)): ?>
        <div style='text-align:center; color:#888; margin-top:40px;'>Brak ogłoszeń.</div>
      <?php else: ?>
        <?php foreach ($ads as $ad): ?>
          <div class="ad-item" <?php if ($ad['is_paid']) echo 'style="border:2px solid #74b9ff"'; ?>>
            <div class="ad-content">
              <?php
                $imgThumb = '';
                if (!empty($ad['images'])) {
                    $imgs = @json_decode($ad['images'], true);
                    if (is_array($imgs) && isset($imgs[0]) && $imgs[0]) {
                        $imgThumb = $imgs[0];
                        if (!file_exists($imgThumb)) {
                            $imgThumb = 'https://via.placeholder.com/60x60?text=Foto';
                        }
                    }
                }
                if (!$imgThumb) {
                    $imgThumb = 'https://via.placeholder.com/60x60?text=Foto';
                }
              ?>
              <img class="ad-image-thumb" src="<?= htmlspecialchars($imgThumb) ?>" alt="miniaturka">
              <a class="ad-title-link" href="podglad-ogloszenia.php?id=<?= $ad['id'] ?>">
                <?= htmlspecialchars($ad['title']) ?: '(Bez tytułu)' ?>
              </a>
              <!-- Status ogłoszenia -->
              <?php if ($ad['published']): ?>
                <span class="status-badge published">Opublikowane</span>
              <?php else: ?>
                <span class="status-badge unpublished">Nieopublikowane</span>
              <?php endif; ?>

              <div class="ad-actions">
                <a href="podglad-ogloszenia.php?id=<?= $ad['id'] ?>" class="publish-btn" style="background:#2196f3;">Podgląd ogłoszenia</a>
                <a href="ogloszenie-form.php?id=<?= $ad['id'] ?>" class="publish-btn" style="background:#ffd600;color:#222;">Edytuj</a>
                <a href="usun-ogloszenie.php?id=<?= $ad['id'] ?>" class="publish-btn" style="background:#e44;" onclick="return confirm('Czy na pewno usunąć ogłoszenie?');">Usuń</a>
                <?php if (!$ad['published']): ?>
                  <a href="opublikuj-ogloszenie.php?id=<?= $ad['id'] ?>" class="publish-btn">Publikuj</a>
                <?php else: ?>
                  <a href="cofniecie-ogloszenia.php?id=<?= $ad['id'] ?>" class="publish-btn unpublish" onclick="return confirm('Czy na pewno cofnąć publikację tego ogłoszenia?');">Cofnij publikację</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>