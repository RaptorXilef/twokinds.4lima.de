import fs from 'node:fs';
import path from 'node:path';
import readline from 'node:readline';
import { fileURLToPath } from 'node:url';

// =============================================================================
// SCHNELLE KONFIGURATION (Hier einfach Ordner/Dateien ergänzen)
// =============================================================================

const ALWAYS_IGNORE_DIRS = [
    'backup',
    'alt',
    'notizen',
    'notes',
    'vendor',
    'node_modules',
    '_Commits',
    'debug',
    'scripts',
    '.git',
    '.cache',
    '.build',
];

const ALWAYS_IGNORE_FILES = [
    '.lock',
    '-lock.json',
    '.DS_Store',
    'min.js',
    'min.css',
    '_dev.local.php',
    '.local.*',
];

// =============================================================================

const c = {
    reset: '\x1b[0m',
    bright: '\x1b[1m',
    dim: '\x1b[2m',
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    magenta: '\x1b[35m',
    cyan: '\x1b[36m',
    gray: '\x1b[90m',
};

// --- 1. Grundkonfiguration ---
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const basePath = path.resolve(__dirname, '..');

let globalIncludeRootFiles = false;

// Version aus package.json lesen
let version = 'unknown';
try {
    const pkg = JSON.parse(fs.readFileSync(path.join(basePath, 'package.json'), 'utf-8'));
    version = pkg.version;
} catch (_e) {
    console.warn(`${c.yellow}⚠️ package.json nicht gefunden oder fehlerhaft.${c.reset}`);
}

// Dynamischer Zielordner basierend auf der Version
const debugFolder = path.join(basePath, '.debug', version);

// --- 2. Filter-Konfigurationen ---
const configs = {
    JS: {
        name: 'JsCode',
        filter: /\.js$/,
        ext: '.js',
        exclDirs: ['public/assets'],
        exclFiles: ['svgo.config', 'purgecss.config', 'eslint.config', 'commitlint.config'],
    },
    PHP: {
        name: 'PhpCode',
        filter: /\.php$/,
        ext: '.php',
        exclDirs: ['tests'],
        exclFiles: ['php-cs-fixer.dist', 'rector.php'],
    },
    PHTML: {
        name: 'PhtmlCode',
        filter: /\.phtml$/,
        ext: '.phtml',
        exclDirs: [],
        exclFiles: [],
    },
    SCSS: {
        name: 'ScssCode',
        filter: /\.scss$/,
        ext: '.scss',
        exclDirs: [],
        exclFiles: [],
    },
    PROJECT: {
        name: 'ProjektZusammenfassung',
        // Greift exakt die Typen der Punkte 1-4 ab
        filter: /\.(js|php|phtml|scss)$/,
        ext: '.txt',
        exclDirs: [],
        exclFiles: [],
    },
};

// --- 3. Kern-Logik ---

/**
 * Durchsucht rekursiv Verzeichnisse
 */
function getFiles(dir, filter, exclDirs, exclFiles, includeRoot, currentFiles = []) {
    const files = fs.readdirSync(dir);

    for (const file of files) {
        const fullPath = path.join(dir, file);
        const relPath = path.relative(basePath, fullPath);
        const stat = fs.statSync(fullPath);

        if (stat.isDirectory()) {
            // Check gegen globale und lokale Ausschlussverzeichnisse
            const isExcluded =
                ALWAYS_IGNORE_DIRS.some(
                    (d) => file.toLowerCase().includes(d.toLowerCase()) || file.startsWith('.')
                ) || exclDirs.some((d) => relPath.toLowerCase().includes(d.toLowerCase()));

            if (!isExcluded) {
                getFiles(fullPath, filter, exclDirs, exclFiles, includeRoot, currentFiles);
            }
        } else {
            // Datei-Validierung
            const isRootFile = path.dirname(fullPath) === basePath;
            if (!includeRoot && isRootFile) continue;

            const matchesFilter = filter.test(file);
            const isExcludedFile =
                ALWAYS_IGNORE_FILES.some((f) => file.toLowerCase().includes(f.toLowerCase())) ||
                exclFiles.some((f) => file.toLowerCase().includes(f.toLowerCase()));

            if (matchesFilter && !isExcludedFile) {
                currentFiles.push({ fullPath, relPath });
            }
        }
    }
    return currentFiles;
}

function startFileCollection(configKey, silent = false) {
    const conf = configs[configKey];
    const timestamp = new Date().toISOString().replace(/[:T]/g, '-').split('.')[0];
    const outputName = `${conf.name}_${timestamp}${conf.ext}`;
    const outputPath = path.join(debugFolder, outputName);

    // Erstelle den versionierten Ordner
    if (!fs.existsSync(debugFolder)) fs.mkdirSync(debugFolder, { recursive: true });

    if (!silent) console.log(`\n${c.cyan}🚀 Starte Sammlung: ${c.bright}${conf.name}${c.reset}...`);

    const foundFiles = getFiles(
        basePath,
        conf.filter,
        conf.exclDirs,
        conf.exclFiles,
        globalIncludeRootFiles
    );

    if (foundFiles.length === 0) {
        if (!silent) console.log(`${c.red}❌ Keine Dateien gefunden.${c.reset}`);
        return;
    }

    let combinedContent = '';
    for (const file of foundFiles) {
        try {
            const content = fs.readFileSync(file.fullPath, 'utf-8');
            combinedContent += `// ========== START FILE: [${file.relPath}] ==========\n`;
            combinedContent += `${content}\n`;
            combinedContent += `// ========== END FILE: [${file.relPath}] ==========\n\n`;
            if (!silent) console.log(`${c.gray} + ${file.relPath}${c.reset}`);
        } catch (_e) {
            if (!silent) console.log(`${c.gray} ! Überspringe (Binär?): ${file.relPath}${c.reset}`);
        }
    }

    fs.writeFileSync(outputPath, combinedContent, 'utf-8');
    // Pfad relativ zum Root für die Anzeige kürzen
    const displayPath = path.relative(basePath, outputPath);
    console.log(
        `${c.green}✅ Erfolg: ${c.bright}${displayPath}${c.reset} (${foundFiles.length} Dateien).`
    );
}

function showHelp() {
    console.log(`\n${c.bright}HILFE & CLI ARGUMENTE${c.reset}`);
    console.log(`${c.gray}------------------------------------------------------------${c.reset}`);
    console.table([
        { Argument: '--js', Beschreibung: 'Sammelt nur JavaScript Dateien' },
        { Argument: '--php', Beschreibung: 'Sammelt nur PHP Dateien' },
        { Argument: '--phtml', Beschreibung: 'Sammelt nur PHTML Dateien' },
        { Argument: '--scss', Beschreibung: 'Sammelt nur SCSS Dateien' },
        { Argument: '--project', Beschreibung: 'Projektweite Zusammenfassung (*.txt)' },
        { Argument: '--all', Beschreibung: 'Führt Punkt 1-4 automatisch aus' },
        { Argument: '--root', Beschreibung: 'Bezieht Dateien im Root-Verzeichnis mit ein' },
        { Argument: '--help', Beschreibung: 'Zeigt diese Hilfe an' },
    ]);
    console.log(`${c.gray}Info: Im CI-Modus (mit Argumenten) läuft das Skript stumm.${c.reset}\n`);
}

// --- 4. CLI & Menü Handling ---
const args = process.argv.slice(2);

if (args.length > 0) {
    // --- STUMMER MODUS (CLI) ---
    if (args.includes('--help') || args.includes('-h')) {
        showHelp();
        process.exit(0);
    }
    if (args.includes('--root')) globalIncludeRootFiles = true;

    if (args.includes('--all')) {
        ['JS', 'PHP', 'PHTML', 'SCSS'].forEach((k) => {
            startFileCollection(k, true);
        });
    } else {
        if (args.includes('--js')) startFileCollection('JS', true);
        if (args.includes('--php')) startFileCollection('PHP', true);
        if (args.includes('--phtml')) startFileCollection('PHTML', true);
        if (args.includes('--scss')) startFileCollection('SCSS', true);
        if (args.includes('--project')) startFileCollection('PROJECT', true);
    }
    process.exit(0);
} else {
    // --- INTERAKTIVER MODUS ---
    const rl = readline.createInterface({
        input: process.stdin,
        output: process.stdout,
    });

    const showMenu = () => {
        const rootStatus = globalIncludeRootFiles
            ? `${c.green}${c.bright}AN${c.reset}`
            : `${c.red}${c.bright}AUS${c.reset}`;

        console.clear();
        console.log(`${c.cyan}===============================================`);
        console.log(
            `${c.cyan}    ${c.bright}DATEI-ZUSAMMENFASSUNG${c.reset} ${c.dim}(NodeJS CLI)${c.reset}`
        );
        console.log(`${c.cyan}    Root: ${c.gray}${basePath}${c.reset}`);
        console.log(`${c.cyan}    Ziel: ${c.yellow}.debug/${version}/${c.reset}`);
        console.log(`${c.cyan}===============================================${c.reset}`);
        console.log(`${c.bright} 1)${c.reset} JavaScript (*.js)`);
        console.log(`${c.bright} 2)${c.reset} PHP (*.php)`);
        console.log(`${c.bright} 3)${c.reset} PHTML (*.phtml)`);
        console.log(`${c.bright} 4)${c.reset} SCSS (*.scss)`);
        console.log(
            `${c.bright} 5)${c.reset} ${c.magenta}PROJEKT-ZUSAMMENFASSUNG${c.reset} (*.txt)`
        );
        console.log(`${c.gray}-----------------------------------------------${c.reset}`);
        console.log(`${c.bright} T)${c.reset} Toggle Root-Files: [${rootStatus}]`);
        console.log(`${c.bright} A)${c.reset} ${c.yellow}ALLE nacheinander (1-4)${c.reset}`);
        console.log(`${c.bright} H)${c.reset} Hilfe / CI Info`);
        console.log(`${c.bright} Q)${c.reset} Beenden`);
        console.log(`${c.gray}-----------------------------------------------${c.reset}`);

        rl.question(`${c.bright}Wähle eine Option: ${c.reset}`, (answer) => {
            const choice = answer.toUpperCase();

            if (choice === 'Q') process.exit();
            if (choice === 'H') {
                showHelp();
                rl.question('Drücke Enter für Menü...', showMenu);
                return;
            }
            if (choice === 'T') {
                globalIncludeRootFiles = !globalIncludeRootFiles;
                showMenu();
                return;
            }
            if (choice === 'A') {
                ['JS', 'PHP', 'PHTML', 'SCSS'].forEach((k) => {
                    startFileCollection(k);
                });
                rl.question(`\n${c.gray}Fertig. Drücke Enter...${c.reset}`, showMenu);
                return;
            }

            const map = { 1: 'JS', 2: 'PHP', 3: 'PHTML', 4: 'SCSS', 5: 'PROJECT' };
            if (map[choice]) {
                startFileCollection(map[choice]);
                rl.question(`\n${c.gray}Fertig. Drücke Enter...${c.reset}`, showMenu);
            } else {
                console.log(`${c.red}Ungültige Auswahl!${c.reset}`);
                setTimeout(showMenu, 1000);
            }
        });
    };
    showMenu();
}
