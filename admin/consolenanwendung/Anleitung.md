# Anleitung: PHP Comic Scraper mit XAMPP ausführen

Diese Anleitung beschreibt, wie Sie das PHP-Skript zum Extrahieren von Comic-Informationen von `twokinds.keenspot.com` auf einem Windows 10 PC mit XAMPP über die Kommandozeile ausführen.

## 1. Voraussetzungen

Bevor Sie beginnen, stellen Sie sicher, dass die folgenden Voraussetzungen erfüllt sind:

- **XAMPP installiert:** Sie haben XAMPP auf Ihrem Windows 10 PC installiert.

- **PHP-Skript verfügbar:** Das PHP-Skript (`scrape_comics.php`) liegt in einem Verzeichnis auf Ihrem PC.

## 2. PHP-Skript speichern und Ordner erstellen

1. **Speichern Sie das PHP-Skript:**
   Legen Sie das PHP-Skript (`scrape_comics.php`) in einem Unterordner von XAMPP ab, z.B. unter:

```
C:\xampp\htdocs\mein_scraper\scrape_comics.php
```

_(Passen Sie den Pfad an, falls XAMPP bei Ihnen woanders installiert ist oder Sie einen anderen Ordner verwenden möchten.)_

2. **Erstellen Sie den Ausgabeordner:**
   Erstellen Sie im selben Verzeichnis, in dem `scrape_comics.php` liegt, einen Unterordner namens `scrape_comics/`. Dieser Ordner wird die generierten JSON- und Fehlerprotokoll-Dateien enthalten.
   Beispiel:

```
C:\xampp\htdocs\mein_scraper\scrape_comics\
```

## 3. `php.ini` anpassen

Es ist wichtig, die PHP-Konfiguration anzupassen, damit das Skript korrekt und ohne Timeouts ausgeführt werden kann.

1. **Öffnen Sie die `php.ini`:**

- Starten Sie das **XAMPP Control Panel**.

- Klicken Sie neben "Apache" auf den "Config"-Button und wählen Sie "PHP (php.ini)".

2. **Erweiterungen aktivieren:**

- Suchen Sie in der `php.ini` nach den folgenden Zeilen:

  ```
  ;extension=curl
  ;extension=dom

  ```

- Entfernen Sie das Semikolon (`;`) am Anfang dieser beiden Zeilen, um die Erweiterungen zu aktivieren:

  ```
  extension=curl
  extension=dom

  ```

3. **Ausführungszeit- und Speicherlimits erhöhen:**

- Suchen Sie nach den Zeilen:

  ```
  max_execution_time = 30
  memory_limit = 128M

  ```

- Ändern Sie diese auf höhere Werte, um sicherzustellen, dass das Skript bei langen Läufen nicht abbricht:

  ```
  max_execution_time = 3600 ; Setzt das Limit auf 1 Stunde (3600 Sekunden)
                            ; Sie können auch 0 für unbegrenzt setzen (max_execution_time = 0),
                            ; aber seien Sie vorsichtig bei sehr langen Läufen.
  memory_limit = 512M       ; Erhöht das Speicherlimit auf 512 Megabyte

  ```

4. **Speichern und XAMPP neu starten:**

- Speichern Sie die `php.ini`-Datei.

- Starten Sie den Apache-Dienst im XAMPP Control Panel neu (Stoppen und dann Starten).

## 4. Kommandozeile (CMD) öffnen

1. Drücken Sie die `Windows-Taste + R`, um den "Ausführen"-Dialog zu öffnen.

2. Geben Sie `cmd` ein und drücken Sie `Enter`. Es öffnet sich das schwarze Kommandozeilenfenster.

## 5. Zum PHP-Verzeichnis navigieren

Sie müssen zum Verzeichnis navigieren, in dem sich die `php.exe` von XAMPP befindet.

1. Geben Sie folgenden Befehl ein und drücken Sie `Enter`:

```
cd C:\xampp\php
```

_(Passen Sie den Pfad an, falls XAMPP bei Ihnen woanders installiert ist.)_

## 6. PHP-Skript ausführen

Jetzt können Sie das Skript ausführen, indem Sie die `php.exe` aufrufen und ihr den vollständigen Pfad zu Ihrem Skript übergeben.

1. Geben Sie folgenden Befehl ein und drücken Sie `Enter`:

```
php C:\xampp\htdocs\mein_scraper\scrape_comics.php
```

_(Stellen Sie sicher, dass der Pfad zu `scrape_comics.php` korrekt ist und dem Ort entspricht, an dem Sie die Datei gespeichert haben.)_

## 7. Interaktion im Konsolenfenster

Nachdem Sie den Befehl eingegeben und `Enter` gedrückt haben, wird das Skript im Konsolenfenster starten:

- Es wird Sie nach der **Startnummer** und der **Endnummer** der Comics fragen. Geben Sie die gewünschten Zahlen ein und bestätigen Sie jede Eingabe mit `Enter`.

- Während der Ausführung sehen Sie eine **Fortschrittsanzeige**, die den aktuellen Comic und den prozentualen Abschluss anzeigt.

- Sobald das Skript beendet ist, erhalten Sie Meldungen über die erfolgreich erstellten JSON- und eventuell die Fehlerprotokoll-Dateien. Diese finden Sie im zuvor erstellten `scrape_comics/`-Unterordner.

## 8. Wichtige Hinweise

- **Skript beenden:** Wenn Sie das Skript während der Ausführung manuell beenden möchten, drücken Sie `Strg + C` im Konsolenfenster.

- **Fehlerprotokoll:** Bei Fehlern (z.B. 404-Seiten oder anderen HTTP-Fehlern) wird eine `comic_var_ERROR_[Datum_Uhrzeit].log`-Datei im `scrape_comics/`-Ordner erstellt, die Details zu den übersprungenen Comics enthält.

- **Berechtigungen:** Stellen Sie sicher, dass der Benutzer, unter dem die Kommandozeile ausgeführt wird, Schreibrechte für den Ordner hat, in dem Sie das Skript und den `scrape_comics/`-Unterordner abgelegt haben.
