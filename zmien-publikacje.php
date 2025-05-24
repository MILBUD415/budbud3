<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once "db_connect.php";
$id = (int)($_POST['id'] ?? 0);
$nowy_status = (int)($_POST['nowy_status'] ?? 0);
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("UPDATE ogloszenia SET is_public=? WHERE id=? AND user_id=?");
$stmt->execute([$nowy_status, $id, $user_id]);
header("Location: moje-ogloszenia.php");
exit;
?>