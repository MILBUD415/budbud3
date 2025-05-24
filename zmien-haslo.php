<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword1 = $_POST['new_password1'] ?? '';
    $newPassword2 = $_POST['new_password2'] ?? '';

    // Pobierz aktualne hasło z bazy
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "Użytkownik nie znaleziony.";
    } elseif (!password_verify($currentPassword, $user['password'])) {
        $error = "Obecne hasło jest nieprawidłowe.";
    } elseif (strlen($newPassword1) < 6) {
        $error = "Nowe hasło musi mieć minimum 6 znaków.";
    } elseif ($newPassword1 !== $newPassword2) {
        $error = "Nowe hasła nie są zgodne.";
    } else {
        $newHash = password_hash($newPassword1, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $userId]);
        $success = "Hasło zostało zmienione!";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zmiana hasła</title>
    <meta name="viewport" content="width=400">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
            /* Brick background from assets */
            background: url('assets/brick-background.jpg') center center / cover no-repeat fixed;
        }
        .center-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .form-card {
            background: #fff;
            border-radius: 18px;
            padding: 38px 32px 28px 32px;
            box-shadow: 0 6px 32px #0002;
            min-width: 320px;
            max-width: 98vw;
        }
        h2 {
            margin-top: 0;
            margin-bottom: 24px;
            color: #222;
            font-weight: 600;
            letter-spacing: -1px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 22px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
            color: #222;
        }
        input[type="password"] {
            width: 100%;
            padding: 11px;
            border-radius: 7px;
            border: 1px solid #bbb;
            font-size: 1em;
            margin-bottom: 2px;
        }
        .btn-save {
            width: 100%;
            padding: 12px;
            border-radius: 7px;
            background: #2196f3;
            color: #fff;
            font-size: 1.1em;
            font-weight: bold;
            border: none;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.13s;
        }
        .btn-save:hover {
            background: #1976d2;
        }
        .info-message {
            padding: 10px 0 7px 0;
            margin-bottom: 7px;
            border-radius: 7px;
            color: #fff;
            background: #43a047;
            text-align: center;
            font-weight: bold;
        }
        .error-message {
            padding: 10px 0 7px 0;
            margin-bottom: 7px;
            border-radius: 7px;
            color: #fff;
            background: #e53935;
            text-align: center;
            font-weight: bold;
        }
        @media (max-width: 500px) {
            .form-card { min-width: 98vw; padding: 18px 5vw; }
        }
    </style>
</head>
<body>
    <div class="center-box">
        <form class="form-card" method="post" autocomplete="off">
            <h2>Zmiana hasła</h2>
            <?php if ($success): ?>
                <div class="info-message"><?= htmlspecialchars($success) ?></div>
            <?php elseif ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label for="current_password">Obecne hasło:</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password" />
            </div>
            <div class="form-group">
                <label for="new_password1">Nowe hasło:</label>
                <input type="password" id="new_password1" name="new_password1" required autocomplete="new-password" />
            </div>
            <div class="form-group">
                <label for="new_password2">Powtórz nowe hasło:</label>
                <input type="password" id="new_password2" name="new_password2" required autocomplete="new-password" />
            </div>
            <button class="btn-save" type="submit">Zmień hasło</button>
        </form>
    </div>
</body>
</html>