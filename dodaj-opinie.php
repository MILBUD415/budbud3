<?php
session_start();
require_once 'db_connect.php';

// Tylko zalogowani mogą dodawać opinie
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Musisz być zalogowany!";
    exit;
}

// Odbierz dane z POST
$author_id = intval($_SESSION['user_id']);
$investor_id = isset($_POST['investor_id']) ? intval($_POST['investor_id']) : 0;
$stars = isset($_POST['stars']) ? intval($_POST['stars']) : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if ($investor_id <= 0 || $stars < 1 || $stars > 5 || $content === '') {
    http_response_code(400);
    echo "Nieprawidłowe dane!";
    exit;
}

// Sprawdź, czy użytkownik nie ocenia sam siebie
if ($author_id === $investor_id) {
    http_response_code(403);
    echo "Nie możesz wystawić opinii samemu sobie!";
    exit;
}

// (Opcjonalnie) sprawdź, czy użytkownik już dodał opinię temu inwestorowi
$stmt = $pdo->prepare("SELECT COUNT(*) FROM opinions WHERE investor_id=? AND author_id=?");
$stmt->execute([$investor_id, $author_id]);
if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    echo "Już wystawiłeś opinię temu inwestorowi!";
    exit;
}

// (Opcjonalnie) sprawdź, czy taki inwestor istnieje w users
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id=?");
$stmt->execute([$investor_id]);
if ($stmt->fetchColumn() == 0) {
    http_response_code(404);
    echo "Nie znaleziono inwestora!";
    exit;
}

// Dodaj opinię do bazy
$stmt = $pdo->prepare("INSERT INTO opinions (investor_id, author_id, stars, content, created_at) VALUES (?, ?, ?, ?, NOW())");
$ok = $stmt->execute([$investor_id, $author_id, $stars, $content]);

if ($ok) {
    echo "OK";
} else {
    http_response_code(500);
    // DEBUG: komunikat SQL (usuń na produkcji)
    $err = $stmt->errorInfo();
    echo "Błąd zapisu opinii! " . $err[2];
}
?>