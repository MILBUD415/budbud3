<?php
session_start();
require_once 'db_connect.php';

echo "<h2>Dane sesji użytkownika</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p><strong>user_id:</strong> " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p style='color:red;'>Nie jesteś zalogowany.</p>";
}

echo "<h2>Wszystkie ogłoszenia w tabeli 'ads'</h2>";
$stmt = $pdo->query("SELECT id, title, user_id, is_ad FROM ads ORDER BY id DESC");
$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo "<p>Brak ogłoszeń.</p>";
} else {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Tytuł</th><th>user_id</th><th>is_ad</th><th>Status widoczności</th></tr>";
    foreach ($rows as $row) {
        $status = ($row['is_ad'] == 1) ? "POWINNO być widoczne publicznie" : "Prywatne";
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['is_ad']}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
