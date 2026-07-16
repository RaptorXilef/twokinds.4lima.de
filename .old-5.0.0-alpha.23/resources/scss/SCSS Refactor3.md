# 🏗️ Refactoring: Character Component & Architect's Dream System

Dieses Issue beschreibt den iterativen Umbau der Charakter-Komponente sowie die Etablierung des neuen SCSS-Design-Systems (7-1 Pattern).

## Phase 1: Semantische Bereinigung (Boy Scout Phase)
*Ziel: Konsistenz zwischen PHP, HTML und SCSS herstellen.*

- [ ] **Audit & Rename:** Alle Vorkommen von `charakter` (DE) zu `character` (EN) ändern.
- [ ] **BEM-Implementierung:** Klassen in `display_character.php` auf das neue Schema anpassen (`.c-character__name` etc.).
- [ ] **SCSS-Struktur:** Die Datei `_characters.scss` entsprechend der neuen BEM-Klassen umschreiben.
- [ ] **Code-Cleanup:** Redundante CSS-Regeln innerhalb der Komponente identifizieren und löschen.
- [ ] **PHP-Sync:** Sicherstellen, dass die ID-Logik in PHP weiterhin perfekt mit den neuen Klassen harmoniert.

## Phase 2: Aufbau der "Architect's Engine" (Abstracts)
*Ziel: Die mathematische und logische Basis im Ordner `abstracts/` schaffen.*

- [ ] **Functions:** `abstracts/_functions.scss` mit YIQ-Kontrastberechnung und `clr()`-Helper erstellen.
- [ ] **Palette:** `abstracts/_palette.scss` mit statischen Rohfarben definieren (z.B. `$pal-blue-500`).
- [ ] **Tokens:** `abstracts/_tokens.scss` erstellen und semantische Maps für `$tokens-light` und `$tokens-dark` definieren.
- [ ] **Generator:** `@mixin generate-theme-vars` in `abstracts/_generator.scss` implementieren.
- [ ] **Variables-Migration:** Vorhandene CSS-Variablen in das neue Token-System überführen.

## Phase 3: Struktur & Architektur (7-1 Pattern)
*Ziel: Physische Styles mit logischen Tokens verknüpfen und Verzeichnisse ordnen.*

- [ ] **Token-Anbindung:** In `_characters.scss` alle festen Farbwerte durch die `clr()`-Funktion ersetzen.
- [ ] **7-1 Verzeichnisstruktur:** Dateien in die korrekten Unterordner verschieben (`pages/`, `components/`, `layout/`).
- [ ] **Main-Import-Management:** `main.scss` nach dem ITCSS-Prinzip (Settings -> Tools -> Generic -> Elements -> Objects -> Components -> Trumps) sortieren.
- [ ] **Mixins vs. Hardcoded:** Wiederkehrende Layout-Muster (wie das Charakter-Grid) in Mixins auslagern.

## Phase 4: Theme-Integration & UX (The Polish)
*Ziel: Nahtlose Darkmode-Steuerung und flüssige Übergänge.*

- [ ] **Theme-Manager:** `js/theme-manager.js` (ES2024+) implementieren.
- [ ] **Persistence:** Speicherung der User-Präferenz im `localStorage` sicherstellen.
- [ ] **System-Detection:** Automatisches Auslesen von `prefers-color-scheme`.
- [ ] **Transitions:** Globale 2.2s Transition für Farbanpassungen in `_reset.scss` aktivieren.
- [ ] **Final QA:** Visuelle Prüfung der Charakter-Komponente in Light- & Darkmode sowie Barrierefreiheits-Check (Kontraste).

---
**Status:** 🏗️ In Arbeit
**Version:** 5.1.0-alpha

| Präfix | Bedeutung | Beschreibung | Beispiel |
| **`c-`** | **Component** | Ein eigenständiges, wiederverwendbares UI-Element. | `.c-character-card` |
| **`l-`** | **Layout** | Strukturelle Elemente ohne dekorativen Stil (nur Grid, Spalten, Container). | `.l-grid-main` |
| **`u-`** | **Utility** | "Helfer" für eine einzige Aufgabe. Haben oft ein `!important`. | `.u-text-center` |
| **`is-` / `has-`** | **State** | Zeigt einen temporären Zustand an (meist via JS gesteuert). | `.is-active`, `.has-error` |
| **`js-`** | **JavaScript** | Nur für JS-Hooks. **Darf niemals in SCSS gestylt werden!** | `.js-theme-toggle` |
| **`t-`** | **Theme** | Spezifische Overrides für Themes (Light/Dark). | `.t-dark-mode` |
| **`qa-`** | **Testing** | Für automatisierte Tests (Selenium/Cypress). | `.qa-submit-button` |
