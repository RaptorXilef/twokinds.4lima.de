# Entwickler Notizen & Befehle

Diese Datei enthält nützliche Befehle und Workflows für die Entwicklung, die nicht in der `.gitignore` stehen sollten.

## Installation & Setup

Benötigte Tools installieren:

```bash
# SASS Compiler installieren
npm install -g sass

# CSS Cleaner installieren
npm install clean-css
```

## Build Prozess (CSS & JS)

### SASS zu CSS Kompilierung (alt) <!-- TODO: Updaten -->

Wir verwenden SASS um die CSS Dateien zu erstellen. Führe diese Befehle im Verzeichnis `src/layout/css` aus oder passe die Pfade entsprechend an.

**Hauptbefehl (Alles auf einmal):**

```bash
cd src/layout/css

# Main Styles
sass --no-source-map main.scss main.css && npx clean-css-cli --source-map -o main.min.css main.css

# Dark Mode Styles
sass --no-source-map main_dark.scss main_dark.css && npx clean-css-cli --source-map -o main_dark.min.css main_dark.css

# Cookie Banner
sass --no-source-map cookie_banner.scss cookie_banner.css && npx clean-css-cli --source-map -o cookie_banner.min.css cookie_banner.css

# Cookie Banner Dark
sass --no-source-map cookie_banner_dark.scss cookie_banner_dark.css && npx clean-css-cli --source-map -o cookie_banner_dark.min.css cookie_banner_dark.css
```

### JavaScript Minifizierung

Führe diese Befehle im Verzeichnis `src/layout/js` aus.

```bash
cd src/layout/js

npx terser archive.js --source-map "filename=archive.min.js.map" --output archive.min.js
npx terser charaktere.js --source-map "filename=charaktere.min.js.map" --output charaktere.min.js
npx terser comic.js --source-map "filename=comic.min.js.map" --output comic.min.js
npx terser common.js --source-map "filename=common.min.js.map" --output common.min.js
npx terser cookie_consent.js --source-map "filename=cookie_consent.min.js.map" --output cookie_consent.min.js
```

---

## Alternative Einzelbefehle (Debugging)

Falls Probleme auftreten, können die Schritte einzeln ausgeführt werden:

**Nur SASS kompilieren:**

```bash
cd src/layout/css
sass --no-source-map main.scss main.css
sass --no-source-map main_dark.scss main_dark.css
sass --no-source-map cookie_banner.scss cookie_banner.css
sass --no-source-map cookie_banner_dark.scss cookie_banner_dark.css
```

Nur CSS minifizieren:

```bash
cd src/layout/css
npx clean-css-cli --source-map -o main.min.css main.css
npx clean-css-cli --source-map -o main_dark.min.css main_dark.css
npx clean-css-cli --source-map -o cookie_banner.min.css cookie_banner.css
npx clean-css-cli --source-map -o cookie_banner_dark.min.css cookie_banner_dark.css
```

---

## Git Workflows: Lokale Änderungen ignorieren

Manchmal müssen Konfigurationsdateien lokal geändert werden, ohne dass diese Änderungen ins Repository gepusht werden sollen.

**Datei ignorieren (lokale Änderungen werden von Git ignoriert):**

```bash
git update-index --assume-unchanged src/config/comic_var.json
git update-index --assume-unchanged src/config/archive_chapters.json
```

Datei wieder aktivieren (Änderungen werden wieder von Git erkannt):

```bash
git update-index --no-assume-unchanged src/config/comic_var.json
git update-index --no-assume-unchanged src/config/archive_chapters.json
```
