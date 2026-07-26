import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const config = [
    { src: 'src/assets/js', dest: 'public/assets/js' },
    // Hier man weitere Ordner hinzufügen, wie früher in PS1
    // { src: 'src/js/admin', dest: 'public/assets/js/admin' }
];

console.log('🚀 Starte Legacy-JS-Minifizierung...');

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
        .filter((f) => f.endsWith('.js') && !f.endsWith('.min.js'));

    for (const file of files) {
        const input = path.join(entry.src, file);
        const baseName = path.parse(file).name;
        const output = path.join(entry.dest, `${baseName}.min.js`);
        const mapName = `${baseName}.min.js.map`;

        console.log(`  - Minifiziere: ${file}`);

        try {
            // Terser Aufruf mit Source-Maps
            execSync(
                `npx terser "${input}" --compress --mangle --source-map "filename='${mapName}',url='${mapName}'" --output "${output}"`
            );
        } catch (error) {
            console.error(`  ❌ Fehler bei ${file}:`, error.message);
        }
    }
}

console.log('✅ Minifizierung abgeschlossen.');
