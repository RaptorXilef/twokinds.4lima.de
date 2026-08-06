import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

// Konfiguration: Wir lesen die von SASS generierten Dateien aus 'public'
// und legen die .min.css im selben Ordner ab.
const config = [
    { src: 'public/assets/css', dest: 'public/assets/css' },
    // Hier kannst du weitere Ordner hinzufügen
];

console.log('🚀 Starte Legacy-CSS-Minifizierung...');

for (const entry of config) {
    // Wandle die Pfade in absolute Pfade um, damit wir problemlos das
    // Arbeitsverzeichnis (cwd) für den execSync Aufruf ändern können.
    const absoluteSrc = path.resolve(entry.src);
    const absoluteDest = path.resolve(entry.dest);

    if (!fs.existsSync(absoluteDest)) {
        fs.mkdirSync(absoluteDest, { recursive: true });
    }

    if (!fs.existsSync(absoluteSrc)) {
        console.warn(`⚠️ Warnung: Quellverzeichnis ${entry.src} nicht gefunden. Überspringe...`);
        continue;
    }

    // Finde alle .css Dateien, aber ignoriere bereits minifizierte .min.css
    const files = fs
        .readdirSync(absoluteSrc)
        .filter((f) => f.endsWith('.css') && !f.endsWith('.min.css'));

    for (const file of files) {
        // file ist jetzt NUR noch der Dateiname (z.B. "main.css")
        const baseName = path.parse(file).name;
        const outputFile = `${baseName}.min.css`;

        // Der absolute Pfad, wo die minifizierte Datei landen soll
        const absoluteOutput = path.join(absoluteDest, outputFile);

        console.log(`  - Minifiziere: ${file}`);

        try {
            // FIX: Wir setzen cwd (Current Working Directory) auf den Quellordner.
            // Dadurch übergeben wir cleancss nur "main.css".
            // In die .map Datei wird so auch korrekt nur "main.css" geschrieben!
            execSync(`npx cleancss -O2 --source-map --output "${absoluteOutput}" "${file}"`, {
                cwd: absoluteSrc,
            });

            // Wir löschen die Originaldatei nicht mehr, damit die Source-Map
            // im Browser weiterhin funktioniert und keine 404 Fehler in der Konsole wirft!

            // Lösche die unminifizierte Originaldatei nach erfolgreichem Build
            // fs.unlinkSync(input);
            // console.log(`    🗑️ Originaldatei gelöscht: ${file}`);
        } catch (error) {
            console.error(`  ❌ Fehler bei ${file}:`, error.message);
        }
    }
}

console.log('✅ CSS-Minifizierung abgeschlossen.');
