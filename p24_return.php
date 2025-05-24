<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $sessionId = $_POST['p24_session_id'] ?? $_GET['p24_session_id'] ?? '';
    if (!$sessionId) die("Brak sessionId z Przelewy24.");

    // Ustaw is_paid = 2 (opłacone)
    $stmt = $pdo->prepare("UPDATE ads SET is_paid = 2 WHERE p24_session_id = ?");
    $stmt->execute([$sessionId]);

    header("Location: moje-ogloszenia.php?platnosc=ok");
    exit;
} else {
    echo "Nieprawidłowe żądanie!";
}
?>