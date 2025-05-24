<?php
require_once 'db_connect.php';

// Przelewy24 wysyła POST z danymi transakcji.
$data = $_POST;

// Ustaw swój klucz CRC (to samo co w formularzu płatności)
$p24_crc = '10b1bb5c39b71a3a';

// Minimalna weryfikacja – sprawdzamy tylko, czy jest session_id
if (!empty($data['p24_session_id'])) {
    // Znajdź ogłoszenie po session_id
    $p24_session_id = $data['p24_session_id'];
    $stmt = $pdo->prepare("SELECT id FROM ads WHERE p24_session_id = ? LIMIT 1");
    $stmt->execute([$p24_session_id]);
    $ad = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ad) {
        // Oznacz ogłoszenie jako opłacone (is_paid = 1)
        $stmt = $pdo->prepare("UPDATE ads SET is_paid = 1 WHERE id = ?");
        $stmt->execute([$ad['id']]);
        echo "TRUE";
        exit;
    }
}

echo "ERROR";
exit;
?>
