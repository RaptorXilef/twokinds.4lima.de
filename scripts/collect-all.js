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
    '.old-5.0.0-alpha.23',
];

const ALWAYS_IGNORE_PATHS = ['public/assets', 'public/dev'];

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
        ext: '.md',
        exclDirs: ['public/assets'],
        exclFiles: ['svgo.config', 'purgecss.config', 'eslint.config', 'commitlint.config'],
    },
    PHP: {
        name: 'PhpCode',
        filter: /\.php$/,
        ext: '.md',
        exclDirs: ['tests'],
        exclFiles: ['php-cs-fixer.dist', 'rector.php'],
    },
    PHTML: {
        name: 'PhtmlCode',
        filter: /\.phtml$/,
        ext: '.md',
        exclDirs: [],
        exclFiles: [],
    },
    SCSS: {
        name: 'ScssCode',
        filter: /\.scss$/,
        ext: '.md',
        exclDirs: [],
        exclFiles: [],
    },
    PROJECT: {
        name: 'ProjektZusammenfassung',
        filter: /\.(js|php|phtml|scss)$/,
        ext: '.md',
        exclDirs: [],
        exclFiles: [],
    },
};

// --- 3. Formatierungs-Logik (Ohne Token-Minimierung) ---

function formatContent(content) {
    // Teilt den Inhalt in einzelne Zeilen auf
    const lines = content.split(/\r?\n/);
    const formattedLines = [];

    for (let i = 0; i < lines.length; i++) {
        // trim() entfernt führende UND abschließende Leerzeichen (wie z.B. unsichtbare Spaces am Zeilenende).
        // Der Code selbst (Kommentare, Operatoren, etc.) bleibt völlig unangetastet.
        const line = lines[i].trim();

        // Wir behalten leere Zeilen bei, filtern sie aber auf absolut "leer" (0 Zeichen)
        formattedLines.push(line);
    }

    return formattedLines.join('\n');
}

// =============================================================================
// FILE SYSTEM & CLI LOGIC
// =============================================================================

function getFiles(dir, filter, exclDirs, exclFiles, includeRoot, currentFiles = []) {
    const files = fs.readdirSync(dir);

    for (const file of files) {
        const fullPath = path.join(dir, file);
        const relPath = path.relative(basePath, fullPath);
        const stat = fs.statSync(fullPath);

        if (stat.isDirectory()) {
            const normalizedRelPath = relPath.replace(/\\/g, '/').toLowerCase();

            const isExcluded =
                ALWAYS_IGNORE_DIRS.some(
                    (d) => file.toLowerCase().includes(d.toLowerCase()) || file.startsWith('.')
                ) ||
                ALWAYS_IGNORE_PATHS.some((p) => normalizedRelPath.includes(p.toLowerCase())) ||
                exclDirs.some((d) => normalizedRelPath.includes(d.toLowerCase()));

            if (!isExcluded) {
                getFiles(fullPath, filter, exclDirs, exclFiles, includeRoot, currentFiles);
            }
        } else {
            const isRootFile = path.dirname(fullPath) === basePath;
            if (!includeRoot && isRootFile) continue;

            const matchesFilter = filter.test(file);
            const isExcludedFile =
                ALWAYS_IGNORE_FILES.some((f) => file.toLowerCase().includes(f.toLowerCase())) ||
                exclFiles.some((f) => file.toLowerCase().includes(f.toLowerCase()));

            if (matchesFilter && !isExcludedFile) {
                currentFiles.push({ fullPath, relPath, ext: path.extname(file) });
            }
        }
    }
    return currentFiles;
}

function getTimestampString() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}_${pad(d.getHours())}-${pad(d.getMinutes())}-${pad(d.getSeconds())}`;
}

function startStructureMirror() {
    const timestampDirName = getTimestampString();
    const targetDirName = `${timestampDirName}_collected`;
    const targetDir = path.join(debugFolder, targetDirName);

    console.log(`\n${c.cyan}🚀 Starte Erstellung der gespiegelten RAW-Struktur...`);
    console.log(`${c.yellow}Target: .debug/${version}/${targetDirName}/${c.reset}`);

    const foundFiles = getFiles(basePath, /\.(js|php|phtml|scss)$/, [], [], globalIncludeRootFiles);

    if (foundFiles.length === 0) {
        console.log(`${c.red}❌ Keine Dateien gefunden.${c.reset}`);
        return;
    }

    let count = 0;
    for (const file of foundFiles) {
        try {
            const rawContent = fs.readFileSync(file.fullPath, 'utf-8');
            const formattedContent = formatContent(rawContent);

            const fileOutputDir = path.join(targetDir, path.dirname(file.relPath));
            const fileOutputPath = path.join(targetDir, file.relPath);

            if (!fs.existsSync(fileOutputDir)) fs.mkdirSync(fileOutputDir, { recursive: true });

            fs.writeFileSync(fileOutputPath, formattedContent, 'utf-8');
            count++;
            console.log(`${c.gray} + [Spiegeln] ${file.relPath}${c.reset}`);
        } catch (e) {
            console.log(`${c.red} ! Fehler bei Datei: ${file.relPath} (${e.message})${c.reset}`);
        }
    }

    console.log(
        `${c.green}✅ Erfolg: Struktur gespiegelt! ${c.bright}${count} Dateien${c.reset} exportiert nach ${c.yellow}.debug/${version}/${targetDirName}/${c.reset}`
    );
}

function startFileCollection(configKey, silent = false) {
    const conf = configs[configKey];
    const timestamp = getTimestampString();

    const outputName = `${conf.name}_${timestamp}_collected${conf.ext}`;
    const outputPath = path.join(debugFolder, outputName);

    if (!fs.existsSync(debugFolder)) fs.mkdirSync(debugFolder, { recursive: true });

    if (!silent)
        console.log(`\n${c.cyan}🚀 Starte RAW-Sammlung: ${c.bright}${conf.name}${c.reset}...`);

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
            const rawContent = fs.readFileSync(file.fullPath, 'utf-8');
            const formattedContent = formatContent(rawContent);

            const extName = file.ext.toLowerCase().replace('.', '');

            // Map Dateiendung zu Markdown-Sprache
            const langMap = {
                js: 'javascript',
                php: 'php',
                phtml: 'phtml',
                scss: 'scss',
            };
            const lang = langMap[extName] || extName;

            // Sorge für saubere Forward-Slashes im Markdown-Pfad
            const mdPath = file.relPath.replace(/\\/g, '/');

            combinedContent += `### /${mdPath}\n`;
            combinedContent += `\`\`\`${lang}\n`;
            combinedContent += `${formattedContent}\n`;
            combinedContent += `\`\`\`\n\n`;

            if (!silent) console.log(`${c.gray} + [Gesammelt] ${file.relPath}${c.reset}`);
        } catch (_e) {
            if (!silent) console.log(`${c.gray} ! Überspringe (Binär?): ${file.relPath}${c.reset}`);
        }
    }

    fs.writeFileSync(outputPath, combinedContent, 'utf-8');
    const displayPath = path.relative(basePath, outputPath);
    console.log(
        `${c.green}✅ Erfolg: ${c.bright}${displayPath}${c.reset} (${foundFiles.length} Dateien gesammelt).`
    );
}

function showHelp() {
    console.log(`\n${c.bright}HILFE & CLI ARGUMENTE (RAW/COLLECTED)${c.reset}`);
    console.log(`${c.gray}------------------------------------------------------------${c.reset}`);
    console.table([
        { Argument: '--js', Beschreibung: 'Sammelt nur JavaScript Dateien' },
        { Argument: '--php', Beschreibung: 'Sammelt nur PHP Dateien' },
        { Argument: '--phtml', Beschreibung: 'Sammelt nur PHTML Dateien' },
        { Argument: '--scss', Beschreibung: 'Sammelt nur SCSS Dateien' },
        { Argument: '--project', Beschreibung: 'Projektweite Zusammenfassung (*.md)' },
        { Argument: '--mirror', Beschreibung: 'Spiegelt die gesamte Ordnerstruktur' },
        { Argument: '--all', Beschreibung: 'Führt Punkt 1-4 automatisch aus' },
        { Argument: '--root', Beschreibung: 'Bezieht Dateien im Root-Verzeichnis mit ein' },
        { Argument: '--help', Beschreibung: 'Zeigt diese Hilfe an' },
    ]);
    console.log(`${c.gray}Info: Im CI-Modus (mit Argumenten) läuft das Skript stumm.${c.reset}\n`);
}

// --- 4. CLI & Menü Handling ---
const args = process.argv.slice(2);

if (args.length > 0) {
    if (args.includes('--help') || args.includes('-h')) {
        showHelp();
        process.exit(0);
    }
    if (args.includes('--root')) globalIncludeRootFiles = true;

    if (args.includes('--all')) {
        for (const k of ['JS', 'PHP', 'PHTML', 'SCSS']) {
            startFileCollection(k, true);
        }
    } else {
        if (args.includes('--js')) startFileCollection('JS', true);
        if (args.includes('--php')) startFileCollection('PHP', true);
        if (args.includes('--phtml')) startFileCollection('PHTML', true);
        if (args.includes('--scss')) startFileCollection('SCSS', true);
        if (args.includes('--project')) startFileCollection('PROJECT', true);
        if (args.includes('--mirror')) startStructureMirror();
    }
    process.exit(0);
} else {
    const rl = readline.createInterface({ input: process.stdin, output: process.stdout });

    const showMenu = () => {
        const rootStatus = globalIncludeRootFiles
            ? `${c.green}${c.bright}AN${c.reset}`
            : `${c.red}${c.bright}AUS${c.reset}`;

        console.clear();
        console.log(`${c.cyan}===============================================`);
        console.log(`${c.cyan}    ${c.bright}DATEI-ZUSAMMENFASSUNG (RAW/COLLECTED)${c.reset}`);
        console.log(`${c.cyan}    Root: ${c.gray}${basePath}${c.reset}`);
        console.log(`${c.cyan}    Ziel: ${c.yellow}.debug/${version}/${c.reset}`);
        console.log(`${c.cyan}===============================================${c.reset}`);
        console.log(`${c.bright} 1)${c.reset} JavaScript (*.md)`);
        console.log(`${c.bright} 2)${c.reset} PHP (*.md)`);
        console.log(`${c.bright} 3)${c.reset} PHTML (*.md)`);
        console.log(`${c.bright} 4)${c.reset} SCSS (*.md)`);
        console.log(
            `${c.bright} 5)${c.reset} ${c.magenta}PROJEKT-ZUSAMMENFASSUNG${c.reset} (*.md)`
        );
        console.log(
            `${c.bright} 6)${c.reset} ${c.green}PROJEKT-STRUKTUR SPIEGELN${c.reset} (Einzeldateien in Verzeichnissen)`
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
                for (const k of ['JS', 'PHP', 'PHTML', 'SCSS']) {
                    startFileCollection(k);
                }
                rl.question(`\n${c.gray}Fertig. Drücke Enter...${c.reset}`, showMenu);
                return;
            }
            if (choice === '6') {
                startStructureMirror();
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
