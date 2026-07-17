# TODO für archive2026-07-clean-architecture-overhaul-and-oop-standardization

- [x] **Phase 0: Fundament & Architektur-Skelett**
  - [x] Neuen Branch erstellen.
  - [x] Code-Qualitätstools (`deptrac.yaml`, `composer.json`, `package.json`) integrieren.
  - [x] Generische Core-Dateien (Container, Routing, Middlewares, Basis-Interfaces) 1:1 aus dem Profi-Projekt kopieren.

- [x] **Phase 1: Domain-Analyse & Datenmodellierung (Entities & DTOs)**
  - [x] Kern-Entitäten & Value Objects (`ComicPage`, `Character`, `Report` etc.) definiert.
  - [ ] Definition der Kern-Entitäten (Entities): `AdminUser`.
  - [ ] Erstellen der Value Objects (z.B. saubere Typisierung für Datumsformate, Comic-IDs, Bildpfade).
  - [ ] Definition der Eingabe-DTOs (z.B. `SaveComicDataRequest`, `SubmitReportRequest`).

- [x] **Phase 2: Datenhaltung & Infrastruktur (Repositories)**
  - [x] MySQL-Schema in `SchemaRegistry` entworfen inkl. `ip_hash` und `image_updated_at`.
  - [x] Interfaces definiert und MySQL-Repositories mittels `DynamicSqlTrait` (DRY-Prinzip) implementiert.
  - [ ] Schnittstellen (`Contracts/Storage/...`) für die Repositories definieren.

- [ ] **Phase 3: Core-Services & Geschäftslogik**

  - [x] `ComicService` & `ReportService` implementiert (inkl. nativem MySQL Rate-Limiting).
  - [ ] `CharacterService`: Logik zur Verwaltung, Gruppierung und referenziellen Integrität (Löschen eines Charakters entfernt ihn aus allen Gruppen).
  - [ ] `FeedService`: Ersetzt `generator_rss.php` und `generator_sitemap.php`. Baut sauberes XML direkt aus den DB-Daten.
  - [ ] `MediaService`: Ersetzt `upload_image.php`, `generator_thumbnail.php` und `generator_image_socialmedia.php`. Handhabt das Resizing und die HiRes/LowRes-Zuweisung.
  - *Hinweis: `build_image_cache.php` und `generator_comic.php` wurden restlos gestrichen!* (Bilder-Cache läuft über die DB-Spalte, physische PHP-Dateien pro Comic entfallen dank Front-Controller).

- [ ] **Phase 4: Application Layer (Routing & Actions)**
  - [ ] Front-Controller (`index.php`) und Action-Klassen für Frontend und API einrichten.

- [ ] **Phase 5: Frontend-Integration & Views**
  - [ ] Übernahme Assets und Umbau der `.php`-Ansichten zu sauberen `.phtml`-Templates.

- [ ] **Phase 6: Datenmigration (Der Umzug)**
- [ ] Migrations-Skript für den Umzug von JSON zu MySQL schreiben.

- [ ] **Phase 7: QA & Finishing**
  - [ ] Ausführen von PHPStan (Level Strict), PHP-CS-Fixer und Deptrac.
  - [ ] Testen aller Routen und Admin-Funktionen.
