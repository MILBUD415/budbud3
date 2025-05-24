<?php
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION['user_id']) || !isset($_POST['ad_id'])) exit;
$user_id = intval($_SESSION['user_id']);
$ad_id = intval($_POST['ad_id']);
$stmt = $pdo->prepare("INSERT IGNORE INTO read_ads (user_id, ad_id) VALUES (?, ?)");
$stmt->execute([$user_id, $ad_id]);
