<?php
// Ustaw bezpieczne parametry ciasteczka sesji (ważne przy HTTPS i mobile)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'budbud.pl', // <-- Twoja domena BEZ https:// i www
    'secure' => true,           // Wymuś HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Spójność z login-client.php: używamy user_id jako identyfikatora sesji
if (!isset($_SESSION['user_id'])) {
    header('Location: login-client.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Klienta</title>
    <link rel="stylesheet" href="panel-klienta.css?v=2">
    <style>
        .logout-center {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .logout-center .panel {
            max-width: 300px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 24px;
            margin-left: 24px;
        }
        .logo img {
            height: 60px;
        }
        .logo .subtitle {
            font-size: 1.3em;
            font-weight: bold;
            color: #222;
            letter-spacing: -0.5px;
        }
    </style>
</head>
<body style="background-image: url('assets/brick-background.jpg');">
    <div class="header">
        <div class="logo">
            <img src="assets/tools-icon.png" alt="BudBud Logo">
            <span class="subtitle">Baza Ogłoszeń Budowlanych</span>
        </div>
    </div>
    <h1 class="panel-title">Panel Klienta</h1>
    <div class="container">
        <a href="moje-ogloszenia.php" class="panel">
            <h3>Ogłoszenia Budowlane</h3>
            <p>Sprawdź swoje ogłoszenia</p>
        </a>
        <!-- ZMIANA: Przekierowanie na moja-sprzedaz.php -->
        <a href="moja-sprzedaz.php" class="panel">
            <h3>Sprzedaż</h3>
            <p>Przedmioty wystawione na sprzedaż</p>
        </a>
        <a href="nieruchomosci.html" class="panel">
            <h3>Nieruchomości</h3>
            <p>Sprzedaż i wynajem nieruchomości</p>
        </a>
        <a href="moj-profil.php" class="panel">
            <h3>Mój Profil</h3>
            <p>Dane kontaktowe<br>Miejscowość</p>
        </a>
        <a href="fachowcy.html" class="panel">
            <h3>Fachowcy</h3>
            <p>Budowlańcy<br>Projektanci<br>Złote rączki</p>
        </a>
        <a href="poczta.html" class="panel">
            <h3>Poczta</h3>
            <p>Sprawdź skrzynkę pocztową</p>
        </a>
        <a href="kalkulator.html" class="panel">
            <h3>Kalkulator</h3>
            <p>Kalkulator remontowy</p>
        </a>
        <a href="promocje.html" class="panel">
            <h3>Promocje</h3>
            <p>Promocje i Aktualności Hurtowni oraz Sklepów Budowlanych</p>
        </a>
        <a href="pomoc.html" class="panel">
            <h3>Pomoc</h3>
            <p>Pomoc Supportu<br>Zgłoszenia błędów<br>Najczęściej zadawane pytania</p>
        </a>
    </div>

    <!-- ✅ Wyloguj się – wycentrowany na dole -->
    <div class="logout-center">
        <a href="logout.php" class="panel">
            <h3>Wyloguj się</h3>
            <p>Zakończ sesję i wróć do logowania</p>
        </a>
    </div>
</body>
</html>