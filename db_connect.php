<?php
$host = 'sql160.lh.pl';
$db   = 'serwer358246_budbud';
$user = 'serwer358246_budbud';
$pass = 'RobalEt95.!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
        throw new Exception('Rozszerzenie PDO lub pdo_mysql nie jest dostępne na serwerze.');
    }
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    echo "Błąd połączenia z bazą danych: " . $e->getMessage();
    exit;
} catch (PDOException $e) {
    echo "Błąd PDO: " . $e->getMessage();
    exit;
}
?>