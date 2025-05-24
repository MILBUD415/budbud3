<?php
session_start();
require_once 'db_connect.php';

// Włącz raportowanie błędów na czas testów (wyłącz po testach)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
// ... inne pola
$images = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obsługa zdjęć (max 5 plików, zapis do uploads/)
    if (!empty($_FILES['photos']['name'][0])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
            if ($key >= 5) break;
            if (!empty($_FILES['photos']['name'][$key]) && $_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed)) {
                    $fileName = uniqid('ad_', true) . '.' . $ext;
                    $targetFile = $uploadDir . $fileName;
                    if (move_uploaded_file($tmpName, $targetFile)) {
                        $images[] = $targetFile;
                    }
                }
            }
        }
    }

    // Zamień ścieżki zdjęć na JSON
    $imagesJson = json_encode($images, JSON_UNESCAPED_UNICODE);

    // Dodaj kolumnę images do zapytania SQL (jeśli masz więcej pól, dodaj je tutaj)
    $stmt = $pdo->prepare("INSERT INTO ads (title, description, user_id, images) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $description, $user_id, $imagesJson]);
    header('Location: moje-ogloszenia.php');
    exit;
}
?>
<!-- Przykładowy HTML formularza (upewnij się, że używasz enctype i multiple):
<form action="dodaj-ogloszenie.php" method="post" enctype="multipart/form-data">
  <input type="text" name="title" required placeholder="Tytuł ogłoszenia"><br>
  <textarea name="description" required placeholder="Opis ogłoszenia"></textarea><br>
  <input type="file" name="photos[]" multiple accept="image/*"><br>
  <button type="submit">Dodaj ogłoszenie</button>
</form>
-->