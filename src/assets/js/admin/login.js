import { ThemeManager } from '../shared/ui/ThemeManager.js';
import { Api } from './core/Api.js';

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('admin-login-form');
    const statusMsg = document.getElementById('login-status-message');
    const submitBtn = document.getElementById('login-submit-btn');

    // Nutzt unsere saubere API-Klasse (inkl. Auto-BaseUrl und CSRF)
    const api = new Api();

    // ThemeManager starten
    new ThemeManager();

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            submitBtn.disabled = true;
            submitBtn.textContent = 'Prüfe...';

            const formData = new window.FormData(loginForm);

            try {
                const res = await api.post('admin_login', formData);

                statusMsg.style.display = 'block';

                if (res.success) {
                    statusMsg.className = 'status-message status-green visible';
                    statusMsg.innerHTML = `<i class="fa-solid fa-check"></i> ${res.message || 'Eingeloggt!'}`;

                    setTimeout(() => {
                        // redirect vom Server auslesen und BaseUrl davor setzen
                        window.location.href = `${api.baseUrl}/${res.redirect}`;
                    }, 500);
                } else {
                    statusMsg.className = 'status-message status-red visible';
                    statusMsg.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${res.error}`;
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Anmelden';
                    document.getElementById('password').value = '';
                }
            } catch (_err) {
                statusMsg.style.display = 'block';
                statusMsg.className = 'status-message status-red visible';
                statusMsg.textContent = 'Server-Verbindungsfehler.';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Anmelden';
            }
        });
    }
});
