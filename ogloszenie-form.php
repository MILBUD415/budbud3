<?php
session_start();
require_once 'db_connect.php';

$lista_wojewodztw = [
    "dolnośląskie", "kujawsko-pomorskie", "lubelskie", "lubuskie",
    "łódzkie", "małopolskie", "mazowieckie", "opolskie",
    "podkarpackie", "podlaskie", "pomorskie", "śląskie",
    "świętokrzyskie", "warmińsko-mazurskie", "wielkopolskie", "zachodniopomorskie"
];

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Wczytaj dane użytkownika do JS (do autouzupełniania)
$userData = [];
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
if ($rowUser = $stmtUser->fetch(PDO::FETCH_ASSOC)) {
    $userData = [
        "first_name"   => $rowUser['first_name'] ?? "",
        "last_name"    => $rowUser['last_name'] ?? "",
        "address"      => $rowUser['street'] ?? "",
        "city"         => $rowUser['city'] ?? "",
        "voivodeship"  => $rowUser['voivodeship'] ?? "",
        "contact"      => $rowUser['phone'] ?? "",
    ];
}

// Ustal, czy ogłoszenie ma być płatne na podstawie GET lub POST (przy POST hidden field)
$isPlatne = false;
if (
    (isset($_GET['typ']) && $_GET['typ'] === 'platne')
    || (isset($_POST['typ']) && $_POST['typ'] === 'platne')
) {
    $isPlatne = true;
}

// Tryb edycji: pobierz dane ogłoszenia jeśli jest przekazane id
$editMode = false;
$ad = [
    'title' => '',
    'investor' => '',
    'job_type' => '',
    'description' => '',
    'address' => '',
    'city' => '',
    'voivodeship' => '',
    'start_date' => '',
    'end_date' => '',
    'contact' => '',
    'supervision' => '',
    'images' => []
];
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM ads WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $adFromDb = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($adFromDb) {
        $ad = $adFromDb;
        $editMode = true;
        // Przygotuj obrazy do podglądu
        $ad['images'] = [];
        if (!empty($adFromDb['images'])) {
            $imgs = @json_decode($adFromDb['images'], true);
            if (is_array($imgs)) $ad['images'] = $imgs;
        }
        $isPlatne = isset($adFromDb['is_paid']) && $adFromDb['is_paid'] == 1;
    } else {
        echo "<h2 style='color:red;text-align:center;'>Nie znaleziono ogłoszenia do edycji.</h2>";
        exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['adTitle'] ?? '');
    $investor    = trim($_POST['investor'] ?? '');
    $jobType     = trim($_POST['jobType'] ?? '');
    $desc        = trim($_POST['desc'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $voivodeship = trim($_POST['voivodeship'] ?? '');
    $startDate   = trim($_POST['start_date'] ?? '');
    $endDate     = trim($_POST['end_date'] ?? '');
    $contact     = trim($_POST['contact'] ?? '');
    $supervision = trim($_POST['supervision'] ?? '');
    $userId      = $_SESSION['user_id'];
    $images = [];

    // Obsługa zdjęć (max 5 plików, zapis do uploads/)
    $uploadDir = __DIR__ . '/uploads/';
    $webDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
            if ($key >= 5) break;
            if (!empty($_FILES['photos']['name'][$key]) && is_uploaded_file($tmpName)) {
                $ext = strtolower(pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed)) {
                    $fileName = uniqid('ad_', true) . '.' . $ext;
                    $targetFile = $uploadDir . $fileName;
                    $relativeTargetFile = $webDir . $fileName;
                    if (move_uploaded_file($tmpName, $targetFile)) {
                        $images[] = $relativeTargetFile;
                    }
                }
            }
        }
    }

    // Jeśli edycja i nie przesłano nowych zdjęć, zachowaj stare zdjęcia
    if ($editMode && empty($images)) {
        $images = $ad['images'];
    }

    if (!$title || !$desc || !$city || !$voivodeship) {
        $error = "Tytuł, opis, miasto i województwo są wymagane!";
    } elseif (!in_array($voivodeship, $lista_wojewodztw)) {
        $error = "Nieprawidłowe województwo!";
    } else {
        $imagesJson = json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($editMode) {
            $stmt = $pdo->prepare("
                UPDATE ads SET title=?, investor=?, job_type=?, description=?, address=?, city=?, voivodeship=?, start_date=?, end_date=?, contact=?, supervision=?, images=?
                WHERE id=? AND user_id=?
            ");
            $stmt->execute([
                $title, $investor, $jobType, $desc, $address, $city, $voivodeship, $startDate, $endDate, $contact, $supervision, $imagesJson, $id, $userId
            ]);
            header('Location: moje-ogloszenia.php');
            exit;
        } else {
            $is_paid = $isPlatne ? 1 : 0;
            $stmt = $pdo->prepare("
                INSERT INTO ads (user_id, title, investor, job_type, description, address, city, voivodeship, start_date, end_date, contact, supervision, images, is_ad, is_paid)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([
                $userId, $title, $investor, $jobType, $desc, $address, $city, $voivodeship, $startDate, $endDate, $contact, $supervision, $imagesJson, $is_paid
            ]);
            $new_ad_id = $pdo->lastInsertId();
            if ($isPlatne) {
                header('Location: platnosc.php?ad_id=' . $new_ad_id);
                exit;
            } else {
                header('Location: moje-ogloszenia.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>ogloszenie-form</title>
  <meta name="viewport" content="width=800">
  <link rel="stylesheet" href="ogloszenie-form.css">
  <style>
    .ad-title-bar {
      background: #222;
      color: #fff;
      padding: 16px 18px;
      font-size: 1.18em;
      border-radius: 14px 14px 0 0;
      margin-bottom: 0px;
      display: flex;
      align-items: center;
      min-height: 44px;
    }
    .ad-title-bar input {
      background: transparent;
      color: #fff;
      border: none;
      font-size: 1.15em;
      width: 100%;
      outline: none;
      font-weight: bold;
    }
    .investor-row-flex {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 14px;
      margin-bottom: 0;
    }
    .investor-row-flex .input-box {
      flex: 1;
    }
    .uzupelnij-btn-niebieski {
      background: #2353e7;
      color: #fff;
      border: none;
      padding: 10px 24px;
      font-size: 1.05em;
      font-weight: bold;
      cursor: pointer;
      border-radius: 7px;
      transition: background 0.18s, transform 0.12s;
      outline: none;
      min-width: 120px;
      max-width: 210px;
      box-shadow: 0 2px 9px #0001;
      text-align: center;
      letter-spacing: 0.01em;
      height: 42px;
      margin-bottom: 0;
      margin-top: 0;
      margin-left: 0;
      margin-right: 0;
    }
    .uzupelnij-btn-niebieski:hover {
      background: #1637a5;
      transform: scale(1.04);
    }
    @media (max-width: 700px) {
      .investor-row-flex {
        flex-direction: column;
        gap: 10px;
      }
      .uzupelnij-btn-niebieski {
        width: 100%;
        min-width: 110px;
        font-size: 1em;
        margin: 8px 0 0 0;
        height: 40px;
      }
    }
  </style>
</head>
<body>
  <div class="main-bg">
    <form class="form-container" autocomplete="off" id="ogloszenieForm" method="post" enctype="multipart/form-data">
      <?php if ($error): ?>
        <div style="color:red; margin-bottom:10px;"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($editMode): ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
      <?php endif; ?>
      <?php if ($isPlatne): ?>
        <input type="hidden" name="typ" value="platne">
      <?php endif; ?>
      <div class="ad-title-bar">
        <input id="adTitleInput" name="adTitle" maxlength="60" required placeholder="Dodaj nazwę ogłoszenia"
               value="<?= htmlspecialchars($ad['title']) ?>" />
      </div>
      <div class="investor-row-flex">
        <div class="input-box" style="margin:0;padding:0;">
          <label for="investorInput" class="label" style="margin-bottom:3px;">INWESTOR:</label>
          <input id="investorInput" name="investor" maxlength="60" placeholder="Imię i nazwisko"
                 value="<?= htmlspecialchars($ad['investor']) ?>" />
        </div>
        <button type="button" class="uzupelnij-btn-niebieski" id="uzupelnijDaneBtn">Uzupełnij Dane</button>
      </div>
      <div class="label">Kategoria:</div>
      <div class="input-box dropdown-parent" id="jobTypeBox" tabindex="0">
        <select id="jobTypeInput" name="jobType" required>
          <option value="">Wybierz kategorię</option>
          <?php
          $kategorie = [
             "Remont Mieszkania","Remont łazienki","Remont kuchni","Elewacje","Elektrka","Hydraulika",
             "Posadzki","Tynki","Schody","Podłogi","Ogrodzenia","Wyburzenia i rozbiórki","Utylizacja",
             "Szukam Złotej rączki","Inne"
          ];
          foreach($kategorie as $kat) {
            $sel = ($ad['job_type'] === $kat) ? 'selected' : '';
            echo "<option $sel>".htmlspecialchars($kat)."</option>";
          }
          ?>
        </select>
      </div>
      <div class="label">OPIS :</div>
      <div class="desc-box">
        <textarea id="descInput" name="desc" rows="5" maxlength="2000" required placeholder="Dodaj opis zlecenia"><?= htmlspecialchars($ad['description']) ?></textarea>
      </div>
      <div class="label">Adres :</div>
      <div class="input-box">
        <input id="addressInput" name="address" maxlength="80" placeholder="dodaj adres :"
               value="<?= htmlspecialchars($ad['address']) ?>" />
      </div>
      <div class="label">Miasto :</div>
      <div class="input-box">
        <input id="cityInput" name="city" maxlength="40" required placeholder="np. Rumia, Polska"
               value="<?= htmlspecialchars($ad['city']) ?>" />
      </div>
      <div class="label">Województwo :</div>
      <div class="input-box">
        <select name="voivodeship" id="voivodeshipInput" required>
          <option value="">Wybierz województwo</option>
          <?php foreach ($lista_wojewodztw as $w): ?>
            <option value="<?= htmlspecialchars($w) ?>" <?= ($ad['voivodeship'] == $w ? 'selected' : '') ?>>
                <?= htmlspecialchars(ucfirst($w)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="label term-label">
        Termin rozpoczęcia :
        <span class="date-box">
          data :
          <input type="date" class="date-input" name="start_date" id="startDateInput"
                 value="<?= htmlspecialchars($ad['start_date']) ?>" />
        </span>
      </div>
      <div class="label term-label">
        Termin zakończenia :
        <span class="date-box">
          data :
          <input type="date" class="date-input" name="end_date" id="endDateInput"
                 value="<?= htmlspecialchars($ad['end_date']) ?>" />
        </span>
      </div>
      <div class="label">Kontakt :</div>
      <div class="input-box">
        <input id="contactInput" name="contact" maxlength="60" placeholder="Numer telefonu / Email"
               value="<?= htmlspecialchars($ad['contact']) ?>" />
      </div>
      <div class="label">Nadzór budowlany :</div>
      <div class="input-box dropdown-parent" id="supervisionBox" tabindex="0">
        <select id="supervisionInput" name="supervision">
          <option value="">Wybierz opcje :</option>
          <option <?= $ad['supervision'] === "Kierownik Budowlany" ? "selected" : "" ?>>Kierownik Budowlany</option>
          <option <?= $ad['supervision'] === "Tylko Ja" ? "selected" : "" ?>>Tylko Ja</option>
          <option <?= $ad['supervision'] === "Żona" ? "selected" : "" ?>>Żona</option>
        </select>
      </div>
      <div class="label">Dodaj zdjęcia (max 5): <?= $editMode ? '<span style="color:#888;font-size:0.95em;">(pozostaw puste, aby nie zmieniać)</span>' : '' ?></div>
      <div class="input-box">
        <input type="file" name="photos[]" id="photoInput" multiple accept="image/*" />
        <div id="photoPreview" style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
          <?php if ($editMode && !empty($ad['images'])):
            foreach($ad['images'] as $img) {
              echo '<img src="'.htmlspecialchars($img).'" style="width:80px;height:80px;object-fit:cover;border:1px solid #ccc;border-radius:4px;">';
            }
          endif; ?>
        </div>
      </div>
      <button type="submit" class="submit-btn"><?= $editMode ? "Zapisz zmiany" : ($isPlatne ? "Przejdź do płatności" : "Zatwierdź") ?></button>
      <div class="footer">
        POG. GAZOWE: 992 • POG. ENERGETYCZNE: 991 • POG. WODOCIĄGOWE: 994<br>
        POLICJA: 997 • STRAŻ POŻARNA: 998 • POG. RATUNKOWE: 999<br>
      </div>
    </form>
  </div>
  <script>
    document.getElementById("photoInput").addEventListener("change", function () {
      const preview = document.getElementById("photoPreview");
      preview.innerHTML = "";
      Array.from(this.files).slice(0, 5).forEach(file => {
        const reader = new FileReader();
        reader.onload = function (e) {
          const img = document.createElement("img");
          img.src = e.target.result;
          img.style.width = "80px";
          img.style.height = "80px";
          img.style.objectFit = "cover";
          img.style.border = "1px solid #ccc";
          img.style.borderRadius = "4px";
          preview.appendChild(img);
        };
        reader.readAsDataURL(file);
      });
    });

    // UZUPEŁNIJ DANE - autouzupełnianie danymi z profilu
    const userData = <?= json_encode($userData) ?>;
    document.getElementById('uzupelnijDaneBtn').addEventListener('click', function() {
      // Imię i nazwisko -> INWESTOR
      document.getElementById('investorInput').value = (userData.first_name + " " + userData.last_name).trim();
      // Adres
      document.getElementById('addressInput').value = userData.address;
      // Miasto
      document.getElementById('cityInput').value = userData.city;
      // Województwo
      if (userData.voivodeship) {
          document.getElementById('voivodeshipInput').value = userData.voivodeship;
      }
      // Kontakt (tel/email)
      document.getElementById('contactInput').value = userData.contact;
    });
  </script>
</body>
</html>