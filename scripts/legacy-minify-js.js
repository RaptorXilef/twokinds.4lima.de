import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const config = [
    // Alte, globale Dateien (werden zu .min.js)
    {
        src: 'src/assets/js',
        dest: 'public/assets/js',
        extension: '.min.js',
        isModule: false,
    },
    // Unsere neuen ES6-Module (behalten .js, damit die imports funktionieren!)
    {
        src: 'src/assets/js/admin',
        dest: 'public/assets/js/admin',
        extension: '.js',
        isModule: true,
    },
];

console.log('🚀 Starte JS-Minifizierung (inklusive ES6-Module)...');

for (const entry of config) {
    if (!fs.existsSync(entry.dest)) {
        fs.mkdirSync(entry.dest, { recursive: true });
    }

    if (!fs.existsSync(entry.src)) {
        console.warn(`⚠️ Warnung: Quellverzeichnis ${entry.src} nicht gefunden. Überspringe...`);
        continue;
    }

    const files = fs
        .readdirSync(entry.src)
        // Wir ignorieren Dateien, die bereits .min.js heißen, um doppeltes Minifizieren zu verhindern
        .filter((f) => f.endsWith('.js') && !f.endsWith('.min.js'));

    for (const file of files) {
        const input = path.join(entry.src, file);
        const baseName = path.parse(file).name;
        const output = path.join(entry.dest, `${baseName}${entry.extension}`);
        const mapName = `${baseName}${entry.extension}.map`;

        console.log(`  - Minifiziere: ${entry.src}/${file} -> ${output}`);

        try {
            // Das --module Flag sagt Terser, dass er Imports/Exports nicht kaputt machen darf
            const moduleFlag = entry.isModule ? '--module' : '';

            // Terser Aufruf mit Source-Maps
            execSync(
                `npx terser "${input}" ${moduleFlag} --compress --mangle --source-map "filename='${mapName}',url='${mapName}'" --output "${output}"`
            );
        } catch (error) {
            console.error(`  ❌ Fehler bei ${file}:`, error.message);
        }
    }
}

console.log('✅ Minifizierung erfolgreich abgeschlossen.');
