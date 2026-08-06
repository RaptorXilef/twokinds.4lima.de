# 🛡️ TwoKinds Webcomic - Backup & Recovery System

Diese Dokumentation beschreibt die Funktionsweise, Konfiguration und Wiederherstellung des internen Backup-Systems der TwoKinds Übersetzungs-Plattform.

---

## 1. Wie das Backup-System funktioniert

Das System führt bei jeder Sicherung (egal ob manuell über das Dashboard oder automatisch per Cronjob) die folgenden **vier Schritte** vollautomatisch aus:

1. **Daten-Extraktion:** Die gewünschten MySQL-Tabellen werden ausgelesen und in ein strukturiertes JSON-Format konvertiert.
2. **Kompression & Verschlüsselung:** Das JSON wird ressourcenschonend in eine `.zip`-Datei komprimiert. Ist ein Passwort konfiguriert, wird die Datei mit **AES-256** sicher verschlüsselt. Die Datei landet als primäres Arbeits-Backup im Ordner `var/backups/`.
3. **Off-Site Upload (FTP):** Wenn der FTP-Versand aktiviert ist, wird eine exakte Kopie der `.zip`-Datei sicher (FTPS) auf einen externen Server (z. B. eine heimische FritzBox) geladen.
4. **Retention (Aufräumen):** Das System prüft den lokalen Ordner `var/backups/` und löscht alle Sicherungen, die älter als die konfigurierten Vorhaltetage (z.B. 365 Tage) sind, um zu verhindern, dass die Festplatte voll läuft.

---

## 2. Konfiguration

Die Einstellungen für das Backup-System werden in der Datei `config/config.local.php` (für dein Live-System) verwaltet.

Hier ist ein Beispiel des Konfigurations-Blocks:

```php
'backup' => [
    // Nach wie vielen Tagen sollen lokale Backups gelöscht werden? (0 = niemals löschen)
    'retention_days' => 365,

    // Das Passwort für die AES-256 ZIP-Verschlüsselung.
    // WICHTIG: Wenn dies leer ('') ist, wird das Backup UNVERSCHLÜSSELT gespeichert!
    'zip_password'   => 'DeinGeheimesSuperPasswort',

    // Einstellungen für den externen FTP-Upload (z.B. FritzNAS)
    'ftp' => [
        'enabled' => true,                  // true = aktiviert, false = deaktiviert
        'host'    => '192.168.178.1',       // IP oder Domain des FTP-Servers
        'port'    => 21,                    // Standard FTP-Port
        'user'    => 'ftp_nutzername',      // FTP Benutzer
        'pass'    => 'ftp_passwort',        // FTP Passwort
        'path'    => '/var/backups/',       // Ziel-Ordner (muss mit / enden)
        'ssl'     => false                  // true für FTPS (bei externen Servern dringend empfohlen!)
    ]
],
```

## 3. Automatisierung per Cronjob

Damit du dich nicht manuell um Backups kümmern musst, sollte ein Cronjob im Webhosting eingerichtet werden (z.B. einmal täglich nachts um 03:00 Uhr).

Der Aufruf des Cronjobs triggert **alle 4 oben genannten Schritte**.

**Die URL für den Cronjob lautet:**`https://twokinds.deinedomain.de/api/cron_backup?token=DEIN_GEHEIMES_CRON_PASSWORT`

>
> **Tipp:** Das Token (`DEIN_GEHEIMES_CRON_PASSWORT`) wird als Sicherheitsmaßnahme in der Datei `config/secrets.php` oder `config/config.local.php` unter dem Schlüssel `cron_secret` festgelegt. So kann niemand Fremdes deinen Server zwingen, Backups zu erstellen.

## 4. Wiederherstellung (Restore)

Die Wiederherstellung erfolgt kinderleicht direkt über das **Admin-Dashboard** unter dem Tab **"Backups"**.

- Das System liest die Backups aus dem Ordner `var/backups/`.
- Die Passwort-Entschlüsselung passiert **direkt im Arbeitsspeicher (RAM)**. Die Dateien müssen nicht manuell entpackt werden und landen nicht unverschlüsselt auf dem Server.
- Alte Backups, die noch als reine `.json`-Datei (ohne ZIP) vorliegen, werden vom System weiterhin erkannt und unterstützt (Rückwärtskompatibilität).

### Die 3 Wiederherstellungs-Modi

1. **Migrieren (Standard):**

    - Geänderte Datensätze aus dem Backup überschreiben aktuelle Daten.
    - Fehlende Datensätze (die im Backup sind, aber nicht in der DB) werden hinzugefügt.
    - *Wichtig:* Daten, die aktuell in der Datenbank sind, aber im Backup fehlen, bleiben erhalten!
2. **Exakte Kopie (Überschreiben) - ACHTUNG:**

    - Stellt den exakten 1:1 Zustand des Backups her.
    - Alles, was aktuell in der Datenbank existiert, aber zum Zeitpunkt des Backups nicht da war, wird **unwiderruflich gelöscht**!
3. **Nur Ergänzen (Lücken füllen):**

    - Fügt nur Einträge in die Datenbank ein, die aktuell komplett fehlen.
    - Bestehende Einträge werden ignoriert und *nicht* überschrieben (ideal, um versehentlich gelöschte Nutzer zurückzuholen, ohne neuere Änderungen an Comics zu überschreiben).

## 5. Das externe FTP Backup (Desaster Recovery)

Die FTP-Sicherung ist ein reiner **"Fire-and-Forget"-Mechanismus**.Sollte dein Webserver (z. B. bei Lima-City) einen totalen Hardware-Ausfall erleiden oder dein Account gelöscht werden, hast du auf deinem FTP-Server (z. B. FritzNAS) alle Daten sicher als verschlüsselte ZIP-Dateien liegen.

Um diese im Notfall wiederherzustellen, musst du die ZIP-Dateien lediglich per Hand in den Ordner `var/backups/` der Neuinstallation schieben. Das Admin-Dashboard erkennt sie dann automatisch wieder.
