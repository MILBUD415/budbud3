<?php
require_once 'db_connect.php';
session_start();
$ad_id = intval($_GET['ad_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("UPDATE ads SET is_paid=1, published=1 WHERE id=? AND user_id=?");
$stmt->execute([$ad_id, $user_id]);

echo "<h2>Płatność zakończona sukcesem!</h2>";
echo "<p>Twoje płatne ogłoszenie zostało opublikowane i pojawi się na górze listy ogłoszeń.</p>";
echo "<a href='ogloszenia-publiczne.php'>Przejdź do ogłoszeń</a>";
