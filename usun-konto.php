<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Brak autoryzacji";
    exit;
}

$user_id = $_SESSION['user_id'];
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!$password) {
    http_response_code(400);
    echo "Wpisz hasło";
    exit;
}

// Pobierz hash hasła z bazy
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo "Nie znaleziono użytkownika";
    exit;
}

$hash = $row['password'];
if (!password_verify($password, $hash)) {
    http_response_code(401);
    echo "Nieprawidłowe hasło";
    exit;
}

// USUŃ konto użytkownika
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
if (!$stmt->execute([$user_id])) {
    http_response_code(500);
    echo "Błąd serwera przy usuwaniu konta";
    exit;
}

// Wyloguj użytkownika
session_destroy();

echo "OK";
exit;
?>