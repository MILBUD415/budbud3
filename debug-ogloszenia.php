<?php
require_once 'db_connect.php';

echo "<h2>Lista ogłoszeń w bazie (tabela 'ads')</h2>";
$stmt = $pdo->query("SELECT id, title, user_id, is_ad FROM ads ORDER BY id DESC");
$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo "<p>Brak ogłoszeń w bazie danych.</p>";
} else {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Tytuł</th><th>user_id</th><th>is_ad (czy opublikowane)</th></tr>";
    foreach ($rows as $row) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['is_ad']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
