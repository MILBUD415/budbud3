<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Usuwamy tylko ogłoszenie należące do zalogowanego użytkownika
if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
}

header('Location: moje-ogloszenia.php');
exit;
?>