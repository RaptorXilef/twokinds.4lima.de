import { existsSync } from 'node:fs';
import fs from 'node:fs/promises';
import path from 'node:path';
import { minify } from 'terser';

/**
 * Asynchrone Hilfsfunktion: Findet alle JS-Dateien rekursiv in einem Verzeichnis
 * @param {string} dir Das zu durchsuchende Verzeichnis
 * @param {string[]} fileList Array, in dem die Pfade gesammelt werden
 * @returns {Promise<string[]>}
 */
async function walkDir(dir, fileList = []) {
    if (!existsSync(dir)) return fileList;

    const files = await fs.readdir(dir);
    for (const file of files) {
        const filePath = path.join(dir, file);
        const stat = await fs.stat(filePath);

        if (stat.isDirectory()) {
            await walkDir(filePath, fileList);
        } else if (filePath.endsWith('.js') && !filePath.endsWith('.min.js')) {
            fileList.push(filePath);
        }
    }
    return fileList;
}

const config = [
    // Unsere neuen ES6-Module (Inklusive aller Unterordner wie core/, ui/ etc.)
    {
        srcBase: 'src/assets/js/admin',
        destBase: 'public/assets/js/admin',
        isModule: true,
    },
    // Der Frontend-Modul-Ordner
    {
        srcBase: 'src/assets/js/frontend',
        destBase: 'public/assets/js/frontend',
        isModule: true,
    },
    // Der übergreifende Shared-Ordner
    {
        srcBase: 'src/assets/js/shared',
        destBase: 'public/assets/js/shared',
        isModule: true,
    },
    {
        srcBase: 'src/assets/js',
        destBase: 'public/assets/js',
        isModule: false,
        // Schließt alle drei Modul-Ordner aus der globalen Verarbeitung aus
        excludeDirs: ['src/assets/js/admin', 'src/assets/js/frontend', 'src/assets/js/shared'],
    },
];

/**
 * Der eigentliche Minification-Worker
 */
async function processFile(file, entry) {
    const relativePath = path.relative(entry.srcBase, file);
    const outputFilePath = path.join(entry.destBase, relativePath);
    const outputDir = path.dirname(outputFilePath);

    // Ziel-Ordner asynchron anlegen
    await fs.mkdir(outputDir, { recursive: true });

    const mapName = `${path.basename(outputFilePath)}.map`;

    try {
        // 1. Datei asynchron einlesen
        const code = await fs.readFile(file, 'utf8');

        // 2. Im RAM über Terser API minifizieren (Macht npx & execSync obsolet!)
        const result = await minify(code, {
            module: entry.isModule,
            compress: true,
            mangle: true,
            sourceMap: {
                filename: mapName,
                url: mapName,
            },
        });

        // 3. Datei und Source-Map asynchron schreiben
        await fs.writeFile(outputFilePath, result.code);
        if (result.map) {
            await fs.writeFile(`${outputFilePath}.map`, result.map);
        }

        console.log(`  ✅ Minifiziert: ${relativePath}`);
    } catch (error) {
        console.error(`  ❌ Fehler bei ${file}:`, error);
    }
}

async function runBuilder() {
    console.log('🚀 Starte JS-Minifizierung (Nativ, Asynchron & Parallel)...');
    console.time('⏱️ Build-Dauer');

    const tasks = []; // Hier sammeln wir alle Verarbeitungs-Aufträge

    for (const entry of config) {
        if (!existsSync(entry.srcBase)) {
            console.warn(`⚠️ Warnung: Quellverzeichnis ${entry.srcBase} nicht gefunden.`);
            continue;
        }

        const allFiles = await walkDir(entry.srcBase);

        for (const file of allFiles) {
            let isExcluded = false;
            if (entry.excludeDirs) {
                for (const exDir of entry.excludeDirs) {
                    if (file.startsWith(path.normalize(exDir))) isExcluded = true;
                }
            }
            if (isExcluded) continue;
            // Wir fügen den Vorgang als unerfülltes Promise in unsere Task-Liste ein
            tasks.push(processFile(file, entry));
        }
    }

    // MAGIE: Wir führen alle gesammelten Tasks GLEICHZEITIG aus!
    await Promise.all(tasks);

    console.log(`🎉 Erfolgreich ${tasks.length} Dateien verarbeitet.`);
    console.timeEnd('⏱️ Build-Dauer');
}

// Start!
runBuilder();
