function showLoginForm(type) {
    const container = document.querySelector('.container');
    container.innerHTML = ''; // Wyczyść zawartość kontenera

    // Tworzenie formularza logowania
    const loginForm = document.createElement('div');
    loginForm.classList.add('login-form');

    const title = document.createElement('h2');
    title.textContent = type === 'client' ? 'Logowanie dla Klienta' : 'Logowanie dla Wykonawców';
    loginForm.appendChild(title);

    const usernameInput = document.createElement('input');
    usernameInput.type = 'text';
    usernameInput.placeholder = 'Login';
    loginForm.appendChild(usernameInput);

    const passwordInput = document.createElement('input');
    passwordInput.type = 'password';
    passwordInput.placeholder = 'Hasło';
    loginForm.appendChild(passwordInput);

    const loginButton = document.createElement('button');
    loginButton.textContent = 'Zaloguj się';
    loginForm.appendChild(loginButton);

    // Dodanie formularza do kontenera
    container.appendChild(loginForm);
}