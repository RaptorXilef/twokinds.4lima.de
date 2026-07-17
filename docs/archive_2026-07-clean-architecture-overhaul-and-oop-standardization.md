# TODO für archive2026-07-clean-architecture-overhaul-and-oop-standardization

- [x] **Phase 0: Fundament & Architektur-Skelett (Aktueller Stand)**
  - [x] Neuen Branch erstellen.
  - [x] Code-Qualitätstools (`deptrac.yaml`, `composer.json`, `package.json`) integrieren.
  - [x] Generische Core-Dateien (Container, Routing, Middlewares, Basis-Interfaces) 1:1 aus dem Profi-Projekt kopieren.

- [x] **Phase 1: Domain-Analyse & Datenmodellierung (Entities & DTOs)**
  - [x] Definition der Kern-Entitäten (Entities): `ComicPage`, `Character`, `Report`, `AdminUser`.
  - [x] Erstellen der Value Objects (z.B. saubere Typisierung für Datumsformate, Comic-IDs, Bildpfade).
  - [x] Definition der Eingabe-DTOs (z.B. `SaveComicDataRequest`, `SubmitReportRequest`).

- [x] **Phase 2: Datenhaltung & Infrastruktur (Repositories)**
  - [x] Neues, relationales Datenbank-Schema (MySQL) für Comics, Charaktere und Fehlerberichte in der `SchemaRegistry` entwerfen.
  - [x] Schnittstellen (`Contracts/Storage/...`) für die Repositories definieren.
  - [x] Implementierung der MySQL-Repositories (sowie der optionalen JSON-Repositories als Fallback).

- [ ] **Phase 3: Core-Services & Geschäftslogik**
  - [x] `ComicService`: Logik zum Auslesen, Speichern und Verwalten der Transkripte.
  - [ ] `CharacterService`: Logik zur Verwaltung und Gruppierung der Charaktere.
  - [x] `ReportService`: Saubere Verarbeitung der eingesendeten Fehlerberichte inkl. Rate-Limiting.
  - [ ] `GeneratorService`: Refactoring der Admin-Tools (Sitemap, RSS, Thumbnail-Generierung, Bild-Upload-Logik) in entkoppelte Services ohne direkten HTML-Output.

- [ ] **Phase 4: Application Layer (Routing & Actions)**
  - [ ] Einrichtung des Front-Controllers (`index.php`) für dynamisches Routing.
  - [ ] Erstellen der Frontend-Actions (z.B. `ComicRenderAction`, `CharacterListRenderAction`).
  - [ ] Erstellen der API-Actions für den Admin-Bereich (z.B. `ApiSaveComicDataAction`).
  - [ ] Integration des Rechte- und Rollensystems für den Admin-Bereich.

- [ ] **Phase 5: Frontend-Integration & Views**
  - [ ] Übernahme der unveränderten SCSS/JS/Font-Assets in den neuen `public/assets/`-Ordner.
  - [ ] Umbau der alten `.php`-Ansichten in saubere `.phtml`-Templates, die ausschließlich vom `TemplateRenderer` mit Daten gefüttert werden.

- [ ] **Phase 6: Datenmigration (Der Umzug)**
  - [ ] Schreiben eines temporären Migrations-Skripts, das die alten `comic_var.json`, `charaktere.json` und die Struktur auslesbar in die neue MySQL-Datenbank überführt.

- [ ] **Phase 7: QA & Finishing**
  - [ ] Ausführen von PHPStan (Level Strict), PHP-CS-Fixer und Deptrac.
  - [ ] Testen aller Routen und Admin-Funktionen.
