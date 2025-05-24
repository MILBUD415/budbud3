<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login-client.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Pobierz dane obecnego użytkownika
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $error = "Nie znaleziono użytkownika.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $voivodeship = trim($_POST['voivodeship'] ?? '');
    $country = trim($_POST['country'] ?? '');

    // Walidacja
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ Nieprawidłowy adres e-mail.";
    } else {
        // Sprawdź czy email nie jest już zajęty przez innego użytkownika
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = "❌ Użytkownik o podanym e-mailu już istnieje.";
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare(
            "UPDATE users SET email = ?, first_name = ?, last_name = ?, phone = ?, street = ?, postal_code = ?, city = ?, voivodeship = ?, country = ? WHERE id = ?"
        );
        $stmt->execute([
            $email, $firstName, $lastName, $phone, $street, $postalCode, $city, $voivodeship, $country, $user_id
        ]);
        $success = "✅ Dane zostały zaktualizowane.";
        // Odśwież dane użytkownika w zmiennej $user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zmień Dane - BudBud</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles-register.css">
</head>
<body>
    <div class="registration-container">
        <h1>Zmień Dane Konta</h1>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php elseif (!empty($success)): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php endif; ?>
        <form class="registration-form" action="zmien-dane.php" method="POST" autocomplete="off">
            <fieldset>
                <legend>Dane osobowe</legend>
                <label for="email">* E-mail:</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                <label for="first-name">* Imię:</label>
                <input type="text" id="first-name" name="first_name" required value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                <label for="last-name">* Nazwisko:</label>
                <input type="text" id="last-name" name="last_name" required value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                <label for="phone">* Telefon:</label>
                <input type="tel" id="phone" name="phone" placeholder="Np. 123456789" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </fieldset>
            <fieldset>
                <legend>Dane adresowe</legend>
                <label for="street">* Ulica i nr domu:</label>
                <input type="text" id="street" name="street" placeholder="Np. Mickiewicza 6/24" required value="<?= htmlspecialchars($user['street'] ?? '') ?>">
                <label for="postal-code">* Kod pocztowy:</label>
                <input type="text" id="postal-code" name="postal_code" placeholder="xx-xxx" required value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>">
                <label for="city">* Miasto:</label>
                <input type="text" id="city" name="city" required value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                <label for="voivodeship">* Województwo:</label>
                <select id="voivodeship" name="voivodeship" required>
                    <option value="">Wybierz...</option>
                    <option value="dolnośląskie" <?= ($user['voivodeship'] ?? '') === 'dolnośląskie' ? 'selected' : '' ?>>Dolnośląskie</option>
                    <option value="kujawsko-pomorskie" <?= ($user['voivodeship'] ?? '') === 'kujawsko-pomorskie' ? 'selected' : '' ?>>Kujawsko-pomorskie</option>
                    <option value="lubelskie" <?= ($user['voivodeship'] ?? '') === 'lubelskie' ? 'selected' : '' ?>>Lubelskie</option>
                    <option value="lubuskie" <?= ($user['voivodeship'] ?? '') === 'lubuskie' ? 'selected' : '' ?>>Lubuskie</option>
                    <option value="łódzkie" <?= ($user['voivodeship'] ?? '') === 'łódzkie' ? 'selected' : '' ?>>Łódzkie</option>
                    <option value="małopolskie" <?= ($user['voivodeship'] ?? '') === 'małopolskie' ? 'selected' : '' ?>>Małopolskie</option>
                    <option value="mazowieckie" <?= ($user['voivodeship'] ?? '') === 'mazowieckie' ? 'selected' : '' ?>>Mazowieckie</option>
                    <option value="opolskie" <?= ($user['voivodeship'] ?? '') === 'opolskie' ? 'selected' : '' ?>>Opolskie</option>
                    <option value="podkarpackie" <?= ($user['voivodeship'] ?? '') === 'podkarpackie' ? 'selected' : '' ?>>Podkarpackie</option>
                    <option value="podlaskie" <?= ($user['voivodeship'] ?? '') === 'podlaskie' ? 'selected' : '' ?>>Podlaskie</option>
                    <option value="pomorskie" <?= ($user['voivodeship'] ?? '') === 'pomorskie' ? 'selected' : '' ?>>Pomorskie</option>
                    <option value="śląskie" <?= ($user['voivodeship'] ?? '') === 'śląskie' ? 'selected' : '' ?>>Śląskie</option>
                    <option value="świętokrzyskie" <?= ($user['voivodeship'] ?? '') === 'świętokrzyskie' ? 'selected' : '' ?>>Świętokrzyskie</option>
                    <option value="warmińsko-mazurskie" <?= ($user['voivodeship'] ?? '') === 'warmińsko-mazurskie' ? 'selected' : '' ?>>Warmińsko-mazurskie</option>
                    <option value="wielkopolskie" <?= ($user['voivodeship'] ?? '') === 'wielkopolskie' ? 'selected' : '' ?>>Wielkopolskie</option>
                    <option value="zachodniopomorskie" <?= ($user['voivodeship'] ?? '') === 'zachodniopomorskie' ? 'selected' : '' ?>>Zachodniopomorskie</option>
                </select>
                <label for="country">Kraj:</label>
                <select id="country" name="country" required>
                    <option value="Polska" <?= ($user['country'] ?? '') === 'Polska' ? 'selected' : '' ?>>Polska</option>
                    <option value="Niemcy" <?= ($user['country'] ?? '') === 'Niemcy' ? 'selected' : '' ?>>Niemcy</option>
                    <option value="Czechy" <?= ($user['country'] ?? '') === 'Czechy' ? 'selected' : '' ?>>Czechy</option>
                    <option value="Słowacja" <?= ($user['country'] ?? '') === 'Słowacja' ? 'selected' : '' ?>>Słowacja</option>
                </select>
            </fieldset>
            <button type="submit" class="register-button">Zmień Dane</button>
        </form>
    </div>
</body>
</html>