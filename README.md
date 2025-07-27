<p align="center">
  <a href="https://twokinds.4lima.de"><img src="https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/assets/img/github/twokinds.4lima.de.png" alt="Projekt Logo"></a>
</p>

<h1 align="center">Twokinds Deutsch – Comic-Webseite</h1>

<p align="center">
  <!-- [Version Badge (Beispiel, bitte anpassen)](https://shields.io/badges/git-hub-release) -->
  <a href="https://github.com/RaptorXilef/twokinds.4lima.de/releases/latest"><img src="https://img.shields.io/github/release-date/RaptorXilef/twokinds.4lima.de?label=Release%20Tag" alt="GitHub Release Date"> <img src="https://img.shields.io/github/v/release/RaptorXilef/twokinds.4lima.de?sort=semver" alt="Version"></a>
  <a href="https://github.com/RaptorXilef/twokinds.4lima.de/tags"><img src="https://img.shields.io/github/v/tag/RaptorXilef/twokinds.4lima.de?sort=semver" alt="BetaVersion"></a>
  <a href="https://github.com/RaptorXilef/twokinds.4lima.de/commits/main/"><img src="https://img.shields.io/github/commits-since/RaptorXilef/twokinds.4lima.de/latest?label=Commits%20seit%20letztem%20Release" alt="GitHub commits since latest release"> <img src="https://img.shields.io/github/last-commit/RaptorXilef/twokinds.4lima.de/main?label=Letzter%20Commit" alt="Letzter Commit"></a></a>
  </p>
  <p align="center">  
  <a href="https://github.com/RaptorXilef/twokinds.4lima.de/issues"><img src="https://img.shields.io/github/issues/RaptorXilef/twokinds.4lima.de.svg" alt="Issues"></a>
  <a href="https://github.com/RaptorXilef/twokinds.4lima.de/pulls"><img src="https://img.shields.io/github/issues-pr/RaptorXilef/twokinds.4lima.de.svg" alt="Pull Requests"></a>
  <!--<img src="https://img.shields.io/github/checks-status/RaptorXilef/twokinds.4lima.de/main" alt="Checks Status">-->
  <br><a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de"><img src="https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png" alt="CC BY-NC-SA 4.0"></a></br>
  </p>
  <br><br>


---
### Wichtiger Hinweis: 

Zum aktuellen Zeitpunkt ist das gesammte Projekt noch an die Domain twokinds.4lima.de gebunden. Auf anderen Hosts läuft das Projekt nicht. Dies soll aber in zukünftigen Updates geändert werden.

---

<br>
<br>

## Inhaltsverzeichnis

- [Über das Projekt](#über-das-projekt)
- [Funktionen](#funktionen)
- [Installation](#installation)
- [Nutzung](#nutzung)
- [Projektstruktur](#projektstruktur)
- [Anpassung](#anpassung)
- [Beitrag leisten](#beitrag-leisten)
- [Lizenz](#lizenz)
- [Kontakt](#kontakt)

## Über das Projekt

Dieses Projekt ist eine PHP-basierte Webseite zur Anzeige und Verwaltung der deutschen Übersetzungen des Webcomics "[Twokinds](https://twokinds.keenspot.com/)". Es wurde entwickelt, um eine benutzerfreundliche Oberfläche für das Lesen des Comics zu bieten, inklusive Navigationsfunktionen, Lesezeichenverwaltung und einem RSS-Feed für neue Comic-Seiten. Das Design ist an das Original von Tom Fischbach angepasst, mit Fokus auf einfache Wartung und Erweiterbarkeit.

Grundsätzlich ist dieses Repository als Entwicklungsumgebung und Backup der Webseite twokinds.4lima.de zu sehen. Es steht jedoch jedem frei, diese Daten, nach den untenstehenden Lizenzbedingungen zu nutzen.

## Funktionen

- **Dynamische Comic-Anzeige:** Lädt Comic-Metadaten (Titel, Transkript) und Bilder dynamisch aus JSON-Dateien.
- **Responsive Navigation:** Pfeiltasten- und J/K-Tasten-Navigation für Comic-Seiten.
- **Lesezeichen-Funktion:** Speichert und verwaltet Lesezeichen lokal im Browser (localStorage).
- **Import/Export von Lesezeichen:** Möglichkeit, Lesezeichen als JSON-Datei zu importieren und exportieren.
- **RSS-Feed Generator:** Admin-Bereich zum Generieren eines RSS-Feeds für die neuesten Comic-Seiten.
- **Themenwechsel:** Umschaltfunktion zwischen hellem und dunklem Design.
- **SEO-Optimierung:** Dynamische Seitentitel, Beschreibungen und Open Graph/Twitter Card Tags.
- **Fehlerbehandlung:** Anzeige von Platzhalterbildern bei fehlenden Comic-Bildern und Fehlermeldungen für JSON-Dateien.
- **Adminbereich:** Verschiedene Tools und Generatoren zum erstellen neuer Comicseiten und bearbeiten der angezeiten Titel und Transcripte.

## Installation

Um das Projekt lokal einzurichten, folge diesen Schritten:

1.  **Webserver mit PHP:** Stelle sicher, dass du einen Webserver (z.B. Apache, Nginx) mit PHP (Version 7.4 oder höher empfohlen) installiert hast.
2.  **Projekt klonen:**
    ```bash
    git clone [https://github.com/RaptorXilef/twokinds.4lima.de.git](https://github.com/RaptorXilef/twokinds.4lima.de.git)
    cd twokinds.4lima.de
    ```
3.  **Dateien platzieren:** Platziere alle Projektdateien im Root-Verzeichnis deines Webservers (z.B. `htdocs` für Apache).
4.  **Konfigurationsdateien:**
    -   Stelle sicher, dass die Dateien `src/config/comic_var.json`, `src/config/rss_config.json`, `src/config/archive_chapters.json` und `src/config/sitemap.json` existieren und korrekt formatiert sind. Beispiel-Dateien sollten im Repository vorhanden sein.
    -   Passe die `<Name>.json` bei Bedarf an deine Daten oder Sprache an.
5.  **Berechtigungen:** Stelle sicher, dass der Webserver Schreibberechtigungen für das Root-Verzeichnis hat, damit die `<Name>.xml` und `<Name>.php` generiert werden können.

## Nutzung

Nach der Installation kannst du die Webseite über deinen Browser aufrufen (z.B. `http://localhost/`).

-   **Comic-Seiten:** Navigiere zu den Comic-Seiten über die URL-Struktur (z.B. `http://localhost/comic/YYYYMMDD.php`).
-   **Lesezeichen:** Besuche `http://localhost/lesezeichen.php`, um deine gespeicherten Lesezeichen zu verwalten.
-   **Adminbereich:** Gehe zu `http://localhost/admin`, um die dortigen Tools zu nutzen. Beim ersten Aufruf des Adminbereichs kannst du ein Nutzernamen und Passwort festlegen.

## Projektstruktur (Beispiele)

```
.
├── admin/                     # Admin-Bereich für Tools wie RSS-Generator
│   ├── js/
│   │   └── generator_rss.js   # JavaScript für den RSS-Generator
│   └── rss_generator.php      # PHP-Skript zum Generieren des RSS-Feeds
├── assets/                    # Statische Assets wie Icons und Bilder
│   └── icons/
├── comic/                     # Enthält die einzelnen Comic-PHP-Dateien (z.B. 20250604.php)
├── src/
│   ├── components/            # Wiederverwendbare PHP-Komponenten
│   │   ├── comic_page_renderer.php # Zentraler Renderer für Comic-Seiten
│   │   ├── get_comic_image_path.php # Hilfsfunktion für Bildpfade
│   │   ├── load_comic_data.php # Lädt Comic-Daten aus JSON
│   │   └── nav_link_helper.php # Hilfsfunktion für Navigationslinks
│   ├── config/                # Konfigurationsdateien (JSON)
│   │   ├── comic_var.json     # Metadaten aller Comics
│   │   └── rss_config.json    # Konfiguration für den RSS-Feed
│   └── layout/                # Layout-bezogene Dateien (Header, Footer, CSS, JS)
│       ├── css/
│       │   ├── main.css       # Haupt-CSS-Datei
│       │   └── main_dark.css  # CSS für dunkles Theme
│       ├── js/
│       │   └── comic.js       # Haupt-JavaScript für Comic-Logik und Lesezeichen
│       ├── footer.php         # Gemeinsamer Footer
│       ├── header.php         # Gemeinsamer Header
│       └── comic_navigation.php # Navigationsleiste für Comics
├── index.php                  # Startseite
├── lesezeichen.php            # Seite zur Lesezeichenverwaltung
├── rss.xml                    # Generierter RSS-Feed (wird von admin/rss_generator.php erstellt)
└── README.md                  # Diese Datei
```


## Anpassung

-   **Comic-Daten:** Bearbeite `admin/data_editor_comic.php` oder manuell `src/config/comic_var.json`, um neue Comic-Seiten hinzuzufügen oder bestehende Metadaten zu ändern. Jede Comic-Seite sollte eine entsprechende PHP-Datei im `comic/`-Verzeichnis haben (z.B. `20250604.php`), die den `comic_page_renderer.php` inkludiert.
-   **RSS-Feed:** `admin/generator_rss.php` oder passe `src/config/rss_config.json` manuell an, um den Titel, die Beschreibung, den Autor und die maximale Anzahl der RSS-Einträge zu konfigurieren.
-   **Design:** Modifiziere `src/layout/css/main.css` und `src/layout/css/main_dark.css` für Designänderungen (wenn aktiviert) Standartmäßig laden die Originalen css von https://twokinds.keenspot.com.
-   **JavaScript-Logik:** `src/layout/js/comic.js` enthält die clientseitige Logik für Lesezeichen und Navigation.

## Beitrag leisten

Beiträge sind herzlich willkommen! Wenn du Fehler findest oder Verbesserungen vorschlagen möchtest, kannst du:

1.  Ein Issue eröffnen, um Fehler zu melden oder neue Funktionen vorzuschlagen.
2.  Einen Pull Request erstellen mit deinen Änderungen. Bitte folge dabei den bestehenden Code-Konventionen.

## Lizenz

Dieser Code steht unter der [Creative Commons Namensnennung-Nicht kommerziell-Share Alike 4.0 International](https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de). [![CC BY-NC 4.0](https://licensebuttons.net/l/by-nc-sa/4.0/80x15.png)](https://creativecommons.org/licenses/by-nc/4.0/deed.de)

Dies bedeutet, dass du den Code teilen und adaptieren darfst, solange du die Namensnennung beibehältst und ihn nicht für kommerzielle Zwecke nutzt.

Hinweis: Die meisten Logos, Bilder und zugehörigen Zeichen sind urheberrechtlich geschützt; 2023 Thomas J. Fischbach. Website-Design von Thomas J. Fischbach & Brandon J. Dusseau.
Die bilder laufen unter der Lizenz CC BY-NC-SA 3.0 US  -  [Creative Commons Attribution-NonCommercial-ShareAlike 3.0 United States](https://creativecommons.org/licenses/by-nc-sa/3.0/us/)
Siehe: [TwoKinds-Lizenz](https://twokinds.keenspot.com/license/)

## Kontakt

Bei Fragen oder Anregungen kannst du gern ein Issue öffnen: 



  ![GitHub language count](https://img.shields.io/github/languages/count/RaptorXilef/twokinds.4lima.de)
  ![GitHub top language](https://img.shields.io/github/languages/top/RaptorXilef/twokinds.4lima.de)
  ![GitHub Downloads (all assets, all releases)](https://img.shields.io/github/downloads/RaptorXilef/twokinds.4lima.de/total)
  ![GitHub commit activity](https://img.shields.io/github/commit-activity/w/RaptorXilef/twokinds.4lima.de)
  ![GitHub Created At](https://img.shields.io/github/created-at/RaptorXilef/twokinds.4lima.de)
