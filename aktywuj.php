<?php
require_once "db_connect.php";
$email = $_GET['email'] ?? '';
$code = $_GET['code'] ?? '';

if ($email && $code) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND activation_code = ? AND is_active = 0");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch();

    if ($user) {
        $pdo->prepare("UPDATE users SET is_active = 1, activation_code = NULL WHERE email = ?")->execute([$email]);
        echo "Konto zostało aktywowane. Możesz się teraz zalogować.";
    } else {
        echo "Błędny link lub konto już aktywowane.";
    }
} else {
    echo "Brak danych do aktywacji.";
}
?>