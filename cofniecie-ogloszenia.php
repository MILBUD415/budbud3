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
    $stmt = $pdo->prepare("UPDATE ads SET published = 0 WHERE id = ? AND user_id = ?");
    $stmt->execute([$adId, $userId]);
}

header("Location: moje-ogloszenia.php");
exit;
?>