- **Intelephense neu indizieren**

- **Befehl:** `Intelephense: Index workspace`
- *Tipp:* Wenn die Autovervollständigung komplett streikt, hilft oft auch `Intelephense: Clear Cache and Reload`.
- **Fenster neu starten**

- **Befehl:** `Developer: Reload Window` (Auf Deutsch: `Entwickler: Fenster neu laden`)
- *Tipp:* Das ist der schnellste Weg, um VSCode nach Konfigurationsänderungen oder bei einem Hänger neu zu starten, ohne das Programm komplett schließen zu müssen.
- **PHP Namespace Resolver**

- **Befehl:** `Namespace Resolver: Import` (Fügt den Namespace für die Klasse unter dem Cursor hinzu)
- *Weitere wichtige Befehle der Extension:*

    - `Namespace Resolver: Import All` (Alle fehlenden in der Datei importieren)
    - `Namespace Resolver: Sort` (Imports alphabetisch sortieren)
- **Extension Bisect starten**

- **Befehl:** `Help: Start Extension Bisect` (Auf Deutsch: `Hilfe: Erweiterungs-Bisektion starten`)
- *Erklärung:* Das beste Tool, um herauszufinden, welche deiner installierten Erweiterungen VSCode langsam macht oder Fehler verursacht (deaktiviert Erweiterungen schrittweise im Ausschlussverfahren).
- **Ähnliche hilfreiche Befehle für PHP/Entwickler**

- `Developer: Show Running Extensions` – Zeigt eine Liste aller aktiven Erweiterungen und wie viel Ladezeit/Ressourcen sie verbrauchen.
- `Format Document` – Formatiert dein PHP-Dokument automatisch sauber durch (Standard-Shortcut: `Umschalt` + `Alt` + `F`).
- `File: Compare Active File With...` – Vergleicht die aktuell geöffnete Datei mit einer anderen (Diff-Ansicht).
- `View: Close All Editors` – Schließt alle offenen Tabs auf einmal, um Platz zu machen.
- `Preferences: Open Keyboard Shortcuts` – Hier kannst du für alle oben genannten Befehle ganz einfach eigene Tastenkombinationen festlegen.
- **Zeilen alphabetisch sortieren**

- **Befehl:** `Sort Lines Ascending` (Auf Deutsch: `Zeilen aufsteigend sortieren`)
- *Tipp:* Markiere vorher die Zeilen (z. B. ein Array), die du sortieren möchtest. Das Gegenstück ist `Sort Lines Descending`.
- **Code drucken**

- **Befehl:** `Print` oder `Print: Print Document`
- *Hinweis:* VSCode hat von Haus aus keine native Druckfunktion. Dieser Befehl funktioniert, wenn du eine gängige Erweiterung wie "Print" (z. B. von pdconsec) installiert hast. Alternativ kannst du deinen Code markieren, `F1` drücken und `Print to HTML` nutzen, falls du Code als PDF speichern willst.