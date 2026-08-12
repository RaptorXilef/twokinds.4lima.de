<p align="center">
  <a href="https://twokinds.4lima.de">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/RaptorXilef/twokinds.4lima.de/refs/heads/main/public/assets/images/theme/banners/night.webp">
      <img src="https://raw.githubusercontent.com/RaptorXilef/twokinds.4lima.de/refs/heads/main/public/assets/images/theme/banners/night.webp" alt="Twokinds auf Deutsch - Banner">
    </picture>
  </a>
</p>

<h1 align="center">Twokinds auf Deutsch - Das Webcomic-Portal</h1>

<p align="center">
  <a href="https://github.com/RaptorXilef/twokinds.4lima.de/releases/latest"><img src="https://img.shields.io/github/release-date/RaptorXilef/twokinds.4lima.de?label=Release%20Tag&style=flat-square" alt="GitHub Release Date"></a>
  <a href="https://github.com/RaptorXilef/twokinds.4lima.de/releases"><img src="https://img.shields.io/github/v/release/RaptorXilef/twokinds.4lima.de?sort=semver&style=flat-square" alt="Version"></a>
  <a href="https://github.com/RaptorXilef/twokinds.4lima.de/commits/main/"><img src="https://img.shields.io/github/last-commit/RaptorXilef/twokinds.4lima.de/main?label=Letzter%20Commit&style=flat-square" alt="Letzter Commit"></a>
  <a href="https://github.com/RaptorXilef/twokinds.4lima.de/commits/main/"><img src="https://img.shields.io/github/commits-since/RaptorXilef/twokinds.4lima.de/latest?label=Commits%20seit%20letztem%20Release" alt="GitHub commits since latest release"></a>
  <a href="https://github.com/RaptorXilef/twokinds.4lima.de/issues"><img src="https://img.shields.io/github/issues/RaptorXilef/twokinds.4lima.de.svg?style=flat-square" alt="Issues"></a>
</p>

<p align="center">
  <i>Ein hochmodernes, maßgeschneidertes Content Management System (CMS) zur Verwaltung und Anzeige der deutschen Übersetzung des Webcomics "TwoKinds" von Tom Fischbach.</i>
</p>

<hr><br>

> 🚀 **Major Release v6 Live!** Das Projekt wurde vollständig auf eine robuste OOP/DDD-Architektur mit PHPStan Level 10 gehoben.
> Lies die vollständige [v6 Release-Ankündigung & Feature-Übersicht](./docs/release-notes-v6.md) für alle Details.

<hr>

## 📖 Inhaltsverzeichnis

- [📖 Inhaltsverzeichnis](#-inhaltsverzeichnis)
- [🦊 Über das Projekt](#-über-das-projekt)
- [🏗 Architektur \& Tech-Stack](#-architektur--tech-stack)
- [✨ Feature-Highlights](#-feature-highlights)
  - [Für Nutzer (Frontend)](#für-nutzer-frontend)
  - [Für Administratoren (Backend)](#für-administratoren-backend)
- [💻 Entwicklung \& Installation](#-entwicklung--installation)
  - [Systemanforderungen](#systemanforderungen)
  - [Lokales Setup](#lokales-setup)
- [🧪 Code-Qualität \& Testing](#-code-qualität--testing)
- [](#)
- [🤝 Mitwirken (Contributing \& CLA)](#-mitwirken-contributing--cla)
- [📚 Dokumentation \& Historie](#-dokumentation--historie)
- [⚖️ Lizenz \& Urheberrecht](#️-lizenz--urheberrecht)
  - [Quellcode (Backend/Frontend-Logik)](#quellcode-backendfrontend-logik)
  - [Bildmaterial \& Original-Comic](#bildmaterial--original-comic)
- [📬 Kontakt](#-kontakt)

---

## 🦊 Über das Projekt

Dieses Repository enthält den vollständigen Quellcode der Plattform **[twokinds.4lima.de](https://twokinds.4lima.de)**. Das Projekt wurde von Grund auf neu entwickelt und dient dazu, den englischsprachigen Webcomic "TwoKinds" der deutschsprachigen Community in höchster Qualität zugänglich zu machen.

Das System ist weit mehr als eine einfache Bildergalerie: Es ist ein dediziertes, performantes CMS mit einem integrierten Wiki, einem Cloud-Lesezeichen-System, ausgeklügelten Newsletter-Warteschlangen und einem mächtigen Admin-Panel zur Bildverarbeitung.

---

## 🏗 Architektur & Tech-Stack

Die Codebase ist auf absolute Skalierbarkeit, Sicherheit und Performance ausgelegt.

- **Backend:** PHP 8.3+ mit strenger Typisierung.
- **Architektur:** Objektorientiert (OOP) nach Domain-Driven Design (DDD) Prinzipien.
- **Datenbank:** MySQL/MariaDB (via PDO) mit automatisierten Migrationen.
- **Frontend Assets:** JavaScript (ES6 Modules), SCSS, Vite / Parcel.
- **Qualitätssicherung:**
  - **PHPStan (Level 10)** - 0 Fehler Toleranz.
  - **Pest PHP** für Unit- und Feature-Testing.
  - **Biome, ESLint & Stylelint** für Frontend-Assets.
- **CI/CD:** Vollautomatisches Deployment und Asset-Kompilierung via GitHub Actions.

---

## ✨ Feature-Highlights

Das System bietet Funktionen, die speziell auf das Lesen und Verwalten von Webcomics zugeschnitten sind.

### Für Nutzer (Frontend)

- 🚀 **Extreme Performance:** Ladezeiten von 0,2-1 Sekunde. Bilder werden via Lazy-Loading und als stark komprimiertes WebP (nur ~200-400 KB pro Comicseite) ausgeliefert.
- 📱 **Mobile-First & Swipe-Gesten:** 100 % responsiv mit intuitiver Wischsteuerung am Smartphone.
- ☁️ **Cloud-Lesezeichen:** Angemeldete Nutzer können Lesezeichen plattformübergreifend speichern. Konflikte zwischen lokalen und Cloud-Daten werden intelligent aufgelöst.
- 📧 **3-Stufen-Newsletter:** Nutzer können wählen, ob sie bei einer fertigen Comic-Seite, bei Vorab-Transkripten (Spoiler!) oder bei der Behebung ihrer Fehlermeldungen benachrichtigt werden wollen.
- 👥 **Charakter-Wiki & Filter:** Eine tiefe Datenbank aller Charaktere mit Reference-Sheets, Swatches und einer dynamischen Filterfunktion (nach Rasse, Alter, Geschlecht etc.) ohne Seiten-Reload.
- 🛡 **Security & Privacy:** Absicherung durch CSRF-Tokens, Session-Timeouts (Auto-Logout bei Inaktivität) und passwortlose E-Mail-Verifizierung via Magic Links.
- 📝 **Crowdsourcing (Report-System):** Ein Frontend-Modal erlaubt Nutzern das direkte Melden von Tippfehlern per WYSIWYG-Editor und Screenshot-Upload.

### Für Administratoren (Backend)

- 🤖 **Automatisches Deployment:** Push auf `main` triggert über die `deploy.yml` einen vollständigen Build- und FTP-Deploy-Prozess.
- 🖼 **Massen-Upload & WebP-Magie:** Drag & Drop von hochauflösenden Dateien. Das System skaliert Bilder automatisch in Hires/Lowres, wandelt sie in WebP um und generiert Thumbnails.
- ✂️ **Social-Media Cropper:** Integriertes Tool zum manuellen und automatischen Zuschneiden von 1.91:1 Thumbnails (OpenGraph/Twitter Cards).
- ✉️ **Mail Queue & CronJobs:** Newsletter blockieren nicht den Server. Sie werden in eine Warteschlange (Queue) eingereiht und im Hintergrund per CronJob nach Priorität abgearbeitet.
- 🔐 **Rechte- & Rollensystem (RBAC):** Feingranulares Rollenmanagement für Redakteure, Übersetzer und Administratoren.
- 🕰 **Revisionen & Papierkorb:** Änderungen an Comicseiten werden versioniert (Undo-Funktion). Gelöschte Comics können aus einem Papierkorb wiederhergestellt werden.
- 💾 **DB Backup & Restore:** Automatisierte Datenbank-Backups mit der Möglichkeit, Exakt-Kopien oder fehlende Datensätze via Admin-Panel wiederherzustellen.

---

## 💻 Entwicklung & Installation

Das Repository dient als primäre Entwicklungsumgebung.

### Systemanforderungen

- PHP 8.3 oder höher (inkl. `ext-pdo`, `ext-gd`, `ext-zip`, `ext-curl`)
- Node.js >= 26.x & NPM >= 11.x
- MySQL oder MariaDB Datenbank
- Composer

### Lokales Setup

1. **Repository klonen**

   ```bash
   git clone https://github.com/RaptorXilef/twokinds.4lima.de.git
   cd twokinds.4lima.de
   ```

2. **Abhängigkeiten installieren**

   ```bash
   composer install
   npm install
   ```

3. **Konfiguration erstellen**
   Kopiere die benötigten Config-Dateien im `config/`-Verzeichnis (z. B. aus den `.example.php` oder `.default.php` Vorlagen) und benenne sie entsprechend (z. B. `config.local.php`, `secrets.php`). Trage dort deine lokalen Datenbank-Daten ein.

4. **Datenbank initiieren**
   Das System verfügt über Auto-Migrationen. Sobald die `config/storage.php` bzw. `config.local.php` konfiguriert ist, migriert das Backend das Schema beim Aufruf des Admin-Panels oder über die internen Setup-Skripte selbstständig.

5. **Assets kompilieren**

   ```bash
   npm run dev        # Startet den Watcher für die lokale Entwicklung
   # oder
   npm run legacy:build # Baut alle CSS/JS Assets für die Produktion
   ```

---

## 🧪 Code-Qualität & Testing

Dieses Projekt unterliegt strengen Qualitätskontrollen. Vor einem Commit oder Pull Request sollten folgende Befehle erfolgreich durchlaufen:

**PHP-Checks (via Composer):**

```bash
composer qa         # Führt PHPStan (Max Level), CodeSniffer, Mess Detector & Arch-Tests aus
composer test       # Führt die Pest PHP Test-Suite aus
composer fix        # Formatiert den Code automatisch via PHP-CS-Fixer und Rector
```

**Frontend-Checks (via NPM):**

```bash
npm run chk         # Prüft JS/CSS via Biome, Stylelint, CSpell und Markuplint
npm run fix         # Auto-Fixes für Frontend Assets
```

![GitHub commit activity](https://img.shields.io/github/commit-activity/w/RaptorXilef/twokinds.4lima.de)
---

## 🤝 Mitwirken (Contributing & CLA)

Beiträge (Pull Requests, Bug-Reports) sind herzlich willkommen! Da es sich jedoch um eine proprietäre Codebase handelt, gibt es eine wichtige Voraussetzung:

Um Code beizutragen, musst du dem **Contributor License Agreement (CLA)** zustimmen.
Füge dazu bei der Erstellung deines Pull Requests exakt folgenden Satz in die PR-Beschreibung ein:

> **"I accept the CLA"**

Weitere Details findest du in der [CLA.md](./CLA.md).

---

## 📚 Dokumentation & Historie

Weitere Informationen zur Entwicklung und den Neuerungen findest du in folgenden Dokumenten:

- 📄 **[v6 Release-Notizen & Features](./docs/release-notes-v6.md)** - Die vollständige Übersicht aller Frontend- und Backend-Neuerungen.
- 📋 **[Projekt-Changelog](./CHANGELOG.md)** - Die detaillierte Historie aller Änderungen und Commits.
- 🤝 **[Contributor License Agreement (CLA)](./CLA.md)** - Regeln für die Mitarbeit am Projekt.

---

## ⚖️ Lizenz & Urheberrecht

Bitte lies die beiliegende [LICENSE.md](./LICENSE.md) sorgfältig durch.

### Quellcode (Backend/Frontend-Logik)

**Copyright (c) 2026 Felix Maywald alias RaptorXilef. Alle Rechte vorbehalten.**
Der bereitgestellte Programmcode ist **proprietär** (Source-available) und steht *nicht* unter einer Open-Source-Lizenz. Eine Nutzung, Vervielfältigung, Verbreitung oder der Betrieb zu eigenen Zwecken bedarf der ausdrücklichen schriftlichen Genehmigung.

### Bildmaterial & Original-Comic

Der Webcomic "TwoKinds", seine Charaktere, Lore und Grafiken sind geistiges Eigentum von **Thomas J. Fischbach**.
Die Comic-Bilder stehen unter der [Creative Commons Attribution-NonCommercial-ShareAlike 3.0 United States (CC BY-NC-SA 3.0 US)](https://creativecommons.org/licenses/by-nc-sa/3.0/us/) Lizenz.
Weitere Informationen zur Lizenz des Originalmaterials findest du unter: [TwoKinds-License](https://twokinds.keenspot.com/license/)

---

## 📬 Kontakt

Bei technischen Fragen, Sicherheitslücken oder Anregungen zum Code:

- Eröffne ein Issue hier auf GitHub.
- Schreibe mir über ein Report auf **[twokinds.4lima.de](https://twokinds.4lima.de)**
- Kontaktiere mich via [Patreon](https://www.patreon.com/raptorxilef).

*Made with ❤️ for the TwoKinds Community.*

<!--
<a href="https://github.com/RaptorXilef/twokinds.4lima.de/pulls"><img src="https://img.shields.io/github/issues-pr/RaptorXilef/twokinds.4lima.de.svg" alt="Pull Requests"></a>

![GitHub language count](https://img.shields.io/github/languages/count/RaptorXilef/twokinds.4lima.de)
![GitHub top language](https://img.shields.io/github/languages/top/RaptorXilef/twokinds.4lima.de)
![GitHub Downloads (all assets, all releases)](https://img.shields.io/github/downloads/RaptorXilef/twokinds.4lima.de/total)
![GitHub commit activity](https://img.shields.io/github/commit-activity/w/RaptorXilef/twokinds.4lima.de)
![GitHub Created At](https://img.shields.io/github/created-at/RaptorXilef/twokinds.4lima.de)
-->
