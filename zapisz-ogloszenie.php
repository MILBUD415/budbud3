<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id     = $_SESSION['user_id'];
$id          = isset($_POST['id']) ? intval($_POST['id']) : 0;
$adTitle     = trim($_POST['adTitle'] ?? '');
$investor    = trim($_POST['investor'] ?? '');
$jobType     = trim($_POST['jobType'] ?? '');
$desc        = trim($_POST['desc'] ?? '');
$address     = trim($_POST['address'] ?? '');
$start_date  = trim($_POST['start_date'] ?? '');
$end_date    = trim($_POST['end_date'] ?? '');
$contact     = trim($_POST['contact'] ?? '');
$supervision = trim($_POST['supervision'] ?? '');
$images      = [];

// Katalog do zapisu plików
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Pobierz stare zdjęcia przy edycji
$prevImages = [];
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT images FROM ads WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['images'])) {
        $prevImages = @json_decode($row['images'], true);
        if (!is_array($prevImages)) $prevImages = [];
    }
}

// Obsługa uploadu zdjęć (max 5)
if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
    foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
        if ($key >= 5) break;
        $fileName = $_FILES['photos']['name'][$key];
        $fileError = $_FILES['photos']['error'][$key];
        $fileSize = $_FILES['photos']['size'][$key];
        if (!empty($fileName) && $fileError === UPLOAD_ERR_OK && $fileSize > 0) {
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $uniqueName = uniqid('ad_', true) . '.' . $ext;
                $targetFile = $uploadDir . $uniqueName;
                $relativeTargetFile = 'uploads/' . $uniqueName;
                if (move_uploaded_file($tmpName, $targetFile)) {
                    $images[] = $relativeTargetFile;
                }
            }
        }
    }
}

// Jeśli edycja i nie przesłano nowych zdjęć, zostają stare
if ($id > 0 && empty($images)) {
    $images = $prevImages;
}

$imagesJson = json_encode($images, JSON_UNESCAPED_UNICODE);

if ($id > 0) {
    // Aktualizacja istniejącego ogłoszenia
    $stmt = $pdo->prepare("
        UPDATE ads SET
            title = ?,
            investor = ?,
            job_type = ?,
            description = ?,
            address = ?,
            start_date = ?,
            end_date = ?,
            contact = ?,
            supervision = ?,
            images = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([
        $adTitle,
        $investor,
        $jobType,
        $desc,
        $address,
        $start_date,
        $end_date,
        $contact,
        $supervision,
        $imagesJson,
        $id,
        $user_id
    ]);
} else {
    // Nowe ogłoszenie
    $stmt = $pdo->prepare("
        INSERT INTO ads 
            (user_id, title, investor, job_type, description, address, start_date, end_date, contact, supervision, images)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $adTitle,
        $investor,
        $jobType,
        $desc,
        $address,
        $start_date,
        $end_date,
        $contact,
        $supervision,
        $imagesJson
    ]);
}

header("Location: moje-ogloszenia.php");
exit;
?>
