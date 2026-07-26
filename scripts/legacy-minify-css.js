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
    if (!fs.existsSync(entry.dest)) {
        fs.mkdirSync(entry.dest, { recursive: true });
    }

    if (!fs.existsSync(entry.src)) {
        console.warn(`⚠️ Warnung: Quellverzeichnis ${entry.src} nicht gefunden. Überspringe...`);
        continue;
    }

    // Finde alle .css Dateien, aber ignoriere bereits minifizierte .min.css
    const files = fs
        .readdirSync(entry.src)
        .filter((f) => f.endsWith('.css') && !f.endsWith('.min.css'));

    for (const file of files) {
        const input = path.join(entry.src, file);
        const baseName = path.parse(file).name;
        const output = path.join(entry.dest, `${baseName}.min.css`);

        console.log(`  - Minifiziere: ${file}`);

        try {
            // Aufruf von clean-css-cli mit Source-Maps und Optimierungs-Level 2
            execSync(`npx cleancss -O2 --source-map --output "${output}" "${input}"`);

            // NEU: Lösche die unminifizierte Originaldatei nach erfolgreichem Build
            fs.unlinkSync(input);
            console.log(`    🗑️ Originaldatei gelöscht: ${file}`);
        } catch (error) {
            console.error(`  ❌ Fehler bei ${file}:`, error.message);
        }
    }
}

console.log('✅ CSS-Minifizierung abgeschlossen.');
