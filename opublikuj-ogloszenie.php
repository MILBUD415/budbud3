<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$adId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($adId > 0) {
    // Pobierz ogłoszenie
    $stmt = $pdo->prepare("SELECT is_paid, published FROM ads WHERE id = ? AND user_id = ?");
    $stmt->execute([$adId, $userId]);
    $ad = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ad) {
        $isPaid = isset($ad['is_paid']) ? intval($ad['is_paid']) : 0;
        $alreadyPublished = intval($ad['published']) === 1;

        // Jeśli płatne i nieopłacone – blokada publikacji!
        if ($isPaid === 1 && !$alreadyPublished) {
            // Przekieruj do płatności
            header("Location: platnosc.php?ad_id=" . $adId);
            exit;
        }
        // Jeśli darmowe i nieopublikowane – publikuj
        if (($isPaid === 0 || $isPaid === null) && !$alreadyPublished) {
            $stmt = $pdo->prepare("UPDATE ads SET published = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$adId, $userId]);
        }
        // Jeśli płatne, ale już opłacone i nieopublikowane – publikuj
        if ($isPaid === 2 && !$alreadyPublished) {
            $stmt = $pdo->prepare("UPDATE ads SET published = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$adId, $userId]);
        }
    }
}

header("Location: moje-ogloszenia.php");
exit;
?>