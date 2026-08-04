# Sicherheit und Permission - Hinweis

## 1. Öffentliche Endpunkte (Public)

- **Beispiele:** Login, Registrieren, Passwort vergessen, `api_get_transcript`, `api_submit_report`.
- **Schutz:** Diese sind natürlich ohne Login erreichbar, aber durch den **RateLimiter** geschützt. Wer hier spammt, dessen IP wird blockiert. Bei den Reports schützt zusätzlich ein Honeypot (ein unsichtbares Feld) vor simplen Bots.

## 2. Frontend-Nutzer Endpunkte (Authenticated)

- **Beispiele:** Profil bearbeiten, Lesezeichen speichern (`api_toggle_bookmark`), Account löschen.
- **Schutz:** Die `AuthMiddleware` fängt diese Anfragen ab. Wer nicht eingeloggt ist, bekommt einen 401 Fehler. Ein normaler Leser (Rolle: `user`) hat auf diese Endpunkte Zugriff, kann aber den Admin-Bereich nicht manipulieren.

## 3. Administrator Endpunkte (RBAC - Role Based Access Control)

- **Beispiele:** Alle Lese-, Schreib- und Lösch-Aktionen der Admin-Oberfläche (`api_save_comic`, `api_delete_media`, `api_list_media`, etc.).
- **Schutz:** Doppelte Absicherung! Zuerst prüft die `AuthMiddleware`, ob man überhaupt eingeloggt ist. Danach prüft das Backend in *jedem einzelnen* Skript knallhart die spezielle Berechtigung (z.B. `media.delete`). Versucht ein normaler "Lesezeichen-Nutzer" diese URL aufzurufen, wird er durch den `hasPermission()` Check mit einem **403 Forbidden** abgewiesen.

## 4. Cron-Jobs (System)

- **Beispiele:** `api_process_mail_queue`
- **Schutz:** Erfordert keine Session, aber zwingend die exakte Übergabe des geheimen `cron_secret` Tokens in der URL, der sicher in deiner `secrets.php` hinterlegt ist.
