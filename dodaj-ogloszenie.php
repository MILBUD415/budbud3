<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_public   = isset($_POST['is_public']) ? 1 : 0;
    $userId      = $_SESSION['user_id'];
    $images = [];

    // Obsługa zdjęć (max 5 plików)
    if (!empty($_FILES['photos']['name'][0])) {
        $uploadDir = __DIR__ . '/uploads/';
        $webDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
            if ($key >= 5) break;
            if (!empty($_FILES['photos']['name'][$key]) && is_uploaded_file($tmpName)) {
                $ext = strtolower(pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION));
                $fileName = uniqid('ad_', true) . '.' . $ext;
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($tmpName, $targetFile)) {
                    $images[] = $webDir . $fileName;
                }
            }
        }
    }

    if (!$title || !$description) {
        $error = "Tytuł i opis są wymagane!";
    } else {
        $imagesJson = json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("
            INSERT INTO ads (user_id, title, investor, job_type, description, address, start_date, end_date, contact, supervision, images, is_ad)
            VALUES (?, ?, '', ?, ?, '', '', '', '', '', ?, ?)
        ");
        $stmt->execute([$userId, $title, $category, $description, $imagesJson, $is_public]);

        header('Location: moje-ogloszenia.php');
        exit;
    }
}
?>
