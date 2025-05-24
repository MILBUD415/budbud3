<?php
session_start();
require_once 'db_connect.php';

// Przelewy24 config (sandbox/test)
$p24_merchant_id = 347392;
$p24_pos_id      = 347392;
$p24_crc         = '10b1bb5c39b71a3a';
$p24_api_key     = 'TU_WSTAW_SWOJ_SANDBOX_API_KEY'; // <<< WPROWADŹ SWÓJ KLUCZ API SANDBOX

$p24_amount      = 499; // 4,99 zł w groszach
$p24_currency    = 'PLN';
$p24_description = "Opłata za ogłoszenie na BudBud";
$p24_email       = "budbudetmanscy@wp.pl";
$p24_country     = 'PL';
$p24_url_return  = 'http://budbud.pl/p24_return.php';

// Sprawdź logowanie i czy jest ID ogłoszenia
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;
if (!$ad_id) { die("Błąd: brak ID ogłoszenia."); }

// Pobierz ogłoszenie z bazy
$stmt = $pdo->prepare("SELECT * FROM ads WHERE id = ? AND user_id = ?");
$stmt->execute([$ad_id, $user_id]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ad) { die("Nie znaleziono ogłoszenia."); }

// --- Sesja płatności ---
$p24_session_id = $ad['p24_session_id'];
if (empty($p24_session_id)) {
    $p24_session_id = uniqid('budbud_', true);
    $stmt = $pdo->prepare("UPDATE ads SET p24_session_id = ? WHERE id = ?");
    $stmt->execute([$p24_session_id, $ad_id]);
}

// 1. GET – wyświetl podsumowanie i przycisk
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $ad_title = htmlspecialchars($ad['title']);
    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <title>Płatność Przelewy24</title>
        <meta name="viewport" content="width=900, initial-scale=1.0">
<style>
    body {
        background: #f6f7f9;
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
    }
    .main-wrap {
        max-width: 900px;
        margin: 40px auto 0 auto;
        padding: 40px 0 60px 0;
    }
    .pay-box {
        background: #fff;
        border: 1.5px solid #eee;
        border-radius: 4px;
        box-shadow: 0 2px 10px #0001;
        padding: 38px 40px 32px 40px;
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 36px;
        margin-bottom: 30px;
    }
    .budbud-col {
        flex: 1 1 220px;
        text-align: center;
    }
    .budbud-col img {
        width: 114px;
        height: 114px;
        margin-bottom: 8px;
    }
            .budbud-title {
                font-size: 1.3em;
                font-weight: bold;
                margin-bottom: 0;
                margin-top: 8px;
            }
            .przelewy-col {
                flex: 1 1 320px;
                text-align: center;
            }
            .przelewy-col img {
                width: 144px;
                height: auto;
                margin-bottom: 10px;
            }
            .przelewy-desc {
                color: #888;
                font-size: 1.05em;
                margin-bottom: 16px;
            }
            .przelewy-desc strong {
                color: #111;
            }
            .przelewy-desc a {
                color: #4466cc;
                text-decoration: none;
            }
            .pay-btn {
                background: #111;
                color: #fff;
                border: none;
                border-radius: 5px;
                padding: 14px 38px;
                font-size: 1.12em;
                font-weight: bold;
                cursor: pointer;
                margin-top: 12px;
                transition: background 0.18s;
            }
            .pay-btn:hover {
                background: #232323;
            }
            .info-section {
                margin-top: 18px;
                font-size: 1.06em;
            }
            .info-section b, .info-section strong {
                font-weight: bold;
            }
            .info-section .art-title {
                margin-top: 18px;
            }
            @media (max-width: 800px) {
                .main-wrap { max-width: 100%; padding: 8px; }
                .pay-box { flex-direction: column; align-items: center; padding: 24px 10px 20px 10px;}
                .budbud-col, .przelewy-col { width: 100%; }
            }
        </style>
    </head>
    <body>
        <div class="main-wrap">
            <div class="pay-box">
                <div class="budbud-col">
                    <img src="assets/tools-icon-login.png" alt="BudBud logo">
                    <div class="budbud-title">Baza Ogłoszeń Budowlanych</div>
                </div>
                <div class="przelewy-col">
                    <img src="assets/logo-p24.png" alt="Przelewy24 logo">
                    <div class="przelewy-desc">
                        <strong>Zapłać przez Przelewy24</strong><br>
                        Dodaj Ogłoszenie Płatne. Kwota: <span style="color:#4466cc;">4,99zł</span><br>
                        Ogłoszenie: <span style="color:#4466cc;"><?= $ad_title ?></span>
                    </div>
                    <form method="POST" style="margin:0;">
                        <button type="submit" class="pay-btn">Zapłać przez Przelewy24</button>
                    </form>
                </div>
            </div>
            <div class="info-section">
                <b>Termin dostawy:</b><br>
                Zamówienia są realizowane i wysyłane w ciągu 24 godzin od zaksięgowania wpłaty. Przesyłki kurierskie dostarczane są zwykle w ciągu 1-2 dni roboczych.
                <div class="art-title" style="margin-top:22px;">
                    <b>Art. 27. [Termin do odstąpienia od umowy]</b>
                </div>
                <div>
                    1. Konsument, który zawarł umowę na odległość lub poza lokalem przedsiębiorstwa, może w terminie 14 dni odstąpić od niej bez podawania przyczyny i bez ponoszenia kosztów, z wyjątkiem kosztów określonych w art. 33, art. 34 ust. 2 i art. 35.
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 2. POST – rejestracja transakcji przez API i przekierowanie do Przelewy24
if (empty($p24_api_key) || $p24_api_key === 'TU_WSTAW_SWOJ_SANDBOX_API_KEY') {
    echo "<h2 class='err'>Błąd konfiguracji: nie podano klucza API Przelewy24!</h2>";
    exit;
}

$register_url = 'https://sandbox.przelewy24.pl/api/v1/transaction/register';
$data = [
    "merchantId"    => $p24_merchant_id,
    "posId"         => $p24_pos_id,
    "sessionId"     => $p24_session_id,
    "amount"        => $p24_amount,
    "currency"      => $p24_currency,
    "description"   => $p24_description,
    "email"         => $p24_email,
    "country"       => $p24_country,
    "urlReturn"     => $p24_url_return,
    "urlStatus"     => $p24_url_return,
    "sign"          => hash('sha384',
                        $p24_session_id . "|" .
                        $p24_merchant_id . "|" .
                        $p24_amount . "|" .
                        $p24_currency . "|" .
                        $p24_crc
                    )
];

$ch = curl_init($register_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: Bearer ' . $p24_api_key
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo "<h2 class='err'>Błąd połączenia z Przelewy24: " . curl_error($ch) . "</h2>";
    exit;
}
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['data']['token'])) {
    $token = $result['data']['token'];
    header("Location: https://sandbox.przelewy24.pl/trnRequest/$token");
    exit;
} else {
    echo "<h2>Błąd płatności Przelewy24!</h2>";
    if (isset($result['error'])) {
        echo "<pre>Błąd: " . htmlspecialchars($result['error']) . " (kod: " . htmlspecialchars($result['code'] ?? '') . ")</pre>";
    } else {
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
    exit;
}
?>