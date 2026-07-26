# Contributing to twokinds.4lima.de 🦖

Vielen Dank, dass du helfen möchtest, den twokinds.4lima.de noch besser zu machen!
Um die extrem hohe Code-Qualität ("Perfection") der Architektur zu erhalten, befolge bitte diesen Workflow:

## 🛠️ Development Setup

1. Clone das Repository lokal.
2. Führe `composer setup` aus (installiert PHP-Tools & Git-Hooks).
3. Führe `npm install` und `npm run setup` aus (Frontend-Assets).

## 📏 Coding Standards

Wir nutzen strikte Standards. Dein Code wird bei einem Pull Request via CI/CD geprüft und nur akzeptiert, wenn alle Checks grün sind:

* **PHP:** PER-CS via `composer analyze:cs`
* **Static Analysis:** PHPStan (Level 6+) via `composer analyze:phpstan`
* **JS/SCSS:** ESLint & Stylelint via `npm run analyze`
* **Architektur:** Beachte die Vorgaben in `docs/CODING_GUIDELINES.md` und `docs/ARCHITECTURE.md`.

## 🧪 Testing

Jeder neue Code benötigt entsprechende Tests:

* **Unit-Tests:** `vendor/bin/phpunit --testsuite Unit`
* **Mutation Testing:** `vendor/bin/infection` (Ziel ist eine durchgehend hohe MSI-Rate).

## 📝 Commits

Wir nutzen **Conventional Commits**.
*Beispiel:* `feat(core): add new panel rendering engine`

## ⚖️ Mitwirkenden-Lizenzvereinbarung (CLA)

Um sicherzustellen, dass das Projekt rechtlich sauber bleibt und der Code kommerziell verwaltet werden kann, bitten wir **alle** Mitwirkenden, unser Contributor License Agreement (CLA) zu akzeptieren.

Bitte lies dir die [CLA.md](./CLA.md) durch.

**Pflicht:** Um deinen Code einzureichen, musst du zwingend den Satz **"I accept the CLA"** in die Beschreibung deines Pull Requests schreiben.
