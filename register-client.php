<?php
require_once 'db_connect.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $street = trim($_POST['street'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $voivodeship = trim($_POST['voivodeship'] ?? '');
    $country = trim($_POST['country'] ?? '');

    // Walidacja
    if ($password !== $confirmPassword) {
        $error = "❌ Hasła się nie zgadzają.";
    } elseif (strlen($password) < 6) {
        $error = "❌ Hasło musi mieć co najmniej 6 znaków.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ Nieprawidłowy adres e-mail.";
    } else {
        // Sprawdź, czy email już istnieje
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "❌ Użytkownik o podanym e-mailu już istnieje.";
        }
    }

    if (!$error) {
        // Dodanie obsługi aktywacji konta
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $activation_code = bin2hex(random_bytes(32));
        $is_active = 0;

        $stmt = $pdo->prepare(
            "INSERT INTO users (email, first_name, last_name, phone, password, street, postal_code, city, voivodeship, country, is_active, activation_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $email, $firstName, $lastName, $phone, $hashedPassword,
            $street, $postalCode, $city, $voivodeship, $country, $is_active, $activation_code
        ]);

        // Wysyłka maila aktywacyjnego
        $activation_link = "https://budbud.pl/aktywuj.php?email=" . urlencode($email) . "&code=" . $activation_code;
        $subject = "Aktywacja konta w BudBud";
        $message = "Dziękujemy za rejestrację w BudBud!\n\nAby aktywować konto, kliknij poniższy link:\n$activation_link\n\nJeśli link nie działa, skopiuj go do przeglądarki.\n\nPozdrawiamy,\nZespół BudBud";
        $headers = "From: noreply@budbud.pl\r\n";
        mail($email, $subject, $message, $headers);

        $success = "Rejestracja przebiegła pomyślnie. Sprawdź skrzynkę e-mail i aktywuj konto.
        <br>Jeśli nie widzisz wiadomości, sprawdź folder SPAM.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Rejestracja - Klient</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles-register.css">
</head>
<body>
    <div class="registration-container">
        <h1>Rejestracja Klienta</h1>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php elseif (!empty($success)): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php endif; ?>
        <?php if (empty($success)): // Formularz tylko jeśli nie zakończyło się sukcesem ?>
        <form class="registration-form" action="register-client.php" method="POST">
            <fieldset>
                <legend>Dane osobowe</legend>
                <label for="email">* E-mail:</label>
                <input type="email" id="email" name="email" required>
                <label for="first-name">* Imię:</label>
                <input type="text" id="first-name" name="first_name" required>
                <label for="last-name">* Nazwisko:</label>
                <input type="text" id="last-name" name="last_name" required>
                <label for="phone">* Telefon:</label>
                <input type="tel" id="phone" name="phone" placeholder="Np. 123456789" required>
                <label for="password">* Hasło:</label>
                <input type="password" id="password" name="password" placeholder="Minimum 6 znaków" required>
                <label for="confirm-password">* Powtórz hasło:</label>
                <input type="password" id="confirm-password" name="confirm_password" required>
            </fieldset>
            <fieldset>
                <legend>Dane adresowe</legend>
                <label for="street">* Ulica i nr domu:</label>
                <input type="text" id="street" name="street" placeholder="Np. Mickiewicza 6/24" required>
                <label for="postal-code">* Kod pocztowy:</label>
                <input type="text" id="postal-code" name="postal_code" placeholder="xx-xxx" required>
                <label for="city">* Miasto:</label>
                <input type="text" id="city" name="city" required>
                <label for="voivodeship">* Województwo:</label>
                <select id="voivodeship" name="voivodeship" required>
                    <option value="">Wybierz...</option>
                    <option value="dolnośląskie">Dolnośląskie</option>
                    <option value="kujawsko-pomorskie">Kujawsko-pomorskie</option>
                    <option value="lubelskie">Lubelskie</option>
                    <option value="lubuskie">Lubuskie</option>
                    <option value="łódzkie">Łódzkie</option>
                    <option value="małopolskie">Małopolskie</option>
                    <option value="mazowieckie">Mazowieckie</option>
                    <option value="opolskie">Opolskie</option>
                    <option value="podkarpackie">Podkarpackie</option>
                    <option value="podlaskie">Podlaskie</option>
                    <option value="pomorskie">Pomorskie</option>
                    <option value="śląskie">Śląskie</option>
                    <option value="świętokrzyskie">Świętokrzyskie</option>
                    <option value="warmińsko-mazurskie">Warmińsko-mazurskie</option>
                    <option value="wielkopolskie">Wielkopolskie</option>
                    <option value="zachodniopomorskie">Zachodniopomorskie</option>
                </select>
                <label for="country">Kraj:</label>
                <select id="country" name="country" required>
                    <option value="Polska" selected>Polska</option>
                    <option value="Niemcy">Niemcy</option>
                    <option value="Czechy">Czechy</option>
                    <option value="Słowacja">Słowacja</option>
                </select>
            </fieldset>
            <button type="submit" class="register-button">Zarejestruj się</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>