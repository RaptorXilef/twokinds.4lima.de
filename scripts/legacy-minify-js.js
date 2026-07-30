import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

/**
 * Hilfsfunktion: Findet alle JS-Dateien rekursiv in einem Verzeichnis
 * @param {string} dir Das zu durchsuchende Verzeichnis
 * @param {string[]} fileList Array, in dem die Pfade gesammelt werden
 * @returns {string[]}
 */
function walkDir(dir, fileList = []) {
    if (!fs.existsSync(dir)) return fileList;

    const files = fs.readdirSync(dir);
    for (const file of files) {
        const filePath = path.join(dir, file);
        if (fs.statSync(filePath).isDirectory()) {
            walkDir(filePath, fileList); // Rekursiver Aufruf für Unterordner
        } else if (filePath.endsWith('.js') && !filePath.endsWith('.min.js')) {
            fileList.push(filePath);
        }
    }
    return fileList;
}

const config = [
    // 1. Unsere neuen ES6-Module (Inklusive aller Unterordner wie core/, ui/ etc.)
    {
        srcBase: 'src/assets/js/admin',
        destBase: 'public/assets/js/admin',
        isModule: true,
    },
    // 2. Alte, globale Dateien (z.B. common.js, comic_reader.js)
    {
        srcBase: 'src/assets/js',
        destBase: 'public/assets/js',
        isModule: false,
        excludeDir: 'src/assets/js/admin', // Damit wir den Admin-Ordner nicht doppelt verarbeiten!
    },
];

console.log('🚀 Starte JS-Minifizierung (Rekursiv, keine .min.js Endungen)...');

for (const entry of config) {
    if (!fs.existsSync(entry.srcBase)) {
        console.warn(`⚠️ Warnung: Quellverzeichnis ${entry.srcBase} nicht gefunden. Überspringe...`);
        continue;
    }

    // Hole alle Dateien aus dem Ordner und seinen Unterordnern
    const allFiles = walkDir(entry.srcBase);

    for (const file of allFiles) {
        // Ignoriere Dateien, die in den excluded Unterordner fallen (z.B. admin)
        if (entry.excludeDir && file.startsWith(path.normalize(entry.excludeDir))) {
            continue;
        }

        // Berechne den relativen Pfad (z.B. "core/Api.js"), um die Struktur in "public" exakt nachzubilden
        const relativePath = path.relative(entry.srcBase, file);
        const outputFilePath = path.join(entry.destBase, relativePath);
        const outputDir = path.dirname(outputFilePath);

        // Erstelle den Ziel-Unterordner in "public", falls er noch nicht existiert
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }

        const mapName = `${path.basename(outputFilePath)}.map`;

        console.log(`  - Minifiziere: ${file} -> ${outputFilePath}`);

        try {
            // Das --module Flag schützt unsere Imports/Exports in den Admin-Dateien
            const moduleFlag = entry.isModule ? '--module' : '';

            // Terser Aufruf (ohne Umbenennung zu .min.js!)
            execSync(
                `npx terser "${file}" ${moduleFlag} --compress --mangle --source-map "filename='${mapName}',url='${mapName}'" --output "${outputFilePath}"`
            );
        } catch (error) {
            console.error(`  ❌ Fehler bei ${file}:`, error.message);
        }
    }
}

console.log('✅ Minifizierung erfolgreich abgeschlossen.');
