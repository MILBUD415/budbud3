<?php
// Do testów: wyświetlanie błędów PHP (usuń te linie na produkcji)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!$email || !$password) {
        $error = "Uzupełnij wszystkie pola!";
    } else {
        $stmt = $pdo->prepare("SELECT id, password, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (isset($user['is_active']) && !$user['is_active']) {
                $error = "Twoje konto nie zostało jeszcze aktywowane. Sprawdź swoją skrzynkę e-mail i kliknij link aktywacyjny lub wpisz kod aktywacyjny.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                header("Location: panel-klienta.php");
                exit;
            }
        } else {
            $error = "Nieprawidłowy email lub hasło!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie klienta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f7f7;
            background-image: url('assets/brick-background.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-outer {
            min-height: 100vh;
            width: 100vw;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            position: relative;
        }
        .center-v-box {
            position: absolute;
            left: 0; right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .logo-top {
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .logo-top a {
            display: inline-block;
        }
        .logo-top img {
            height: 68px;
            width: auto;
            display: block;
            transition: filter 0.2s;
        }
        .logo-top img:hover {
            filter: brightness(1.15) drop-shadow(0 2px 6px #0008);
        }
        .logo-title-link {
            margin-top: 12px;
            font-size: 2em;
            font-weight: bold;
            color: #fff;
            letter-spacing: -1.5px;
            text-align: center;
            text-shadow: 0 3px 18px #000b, 0 1px 2px #000b;
            text-decoration: none;
            transition: color 0.18s, text-shadow 0.18s;
            cursor: pointer;
            line-height: 1.2;
            user-select: none;
        }
        .logo-title-link:hover {
            color: #ffd600;
            text-shadow: 0 3px 22px #000e, 0 2px 4px #000b;
        }
        .login-container {
            max-width: 400px;
            width: 96%;
            margin: 0 auto;
            padding: 32px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        h2 { text-align: center; color: #007bff; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; }
        input[type="email"], input[type="password"] {
            width: 100%; padding: 10px; border: 1px solid #ccc;
            border-radius: 5px; font-size: 16px;
        }
        .error { color: #e44; margin-bottom: 12px; text-align: center; }
        button {
            width: 100%; padding: 12px; background: #007bff; color: #fff;
            font-size: 16px; font-weight: bold; border: none; border-radius: 5px; cursor: pointer;
            transition: background 0.2s;
        }
        button:hover { background: #0056b3; }
        .register-link { display: block; text-align: center; margin-top: 16px; }
        @media (max-width: 600px) {
            .center-v-box { padding: 12px; }
            .login-container { padding: 18px 8px; }
            .logo-title-link { font-size: 1.35em; }
        }
    </style>
</head>
<body>
    <div class="login-outer">
        <div class="center-v-box">
            <div class="logo-top">
                <a href="index.html" title="BudBud - Strona główna">
                    <img src="assets/tools-icon.png" alt="BudBud logo">
                </a>
                <a href="index.html" class="logo-title-link" title="BudBud - Strona główna">
                    Baza Ogłoszeń Budowlanych
                </a>
            </div>
            <div class="login-container">
                <h2>Logowanie klienta</h2>
                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="form-group">
                        <label for="email">E-mail:</label>
                        <input type="email" name="email" id="email" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">Hasło:</label>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <button type="submit">Zaloguj się</button>
                </form>
                <a href="register-client.php" class="register-link">Nie masz konta? Zarejestruj się</a>
            </div>
        </div>
    </div>
</body>
</html>