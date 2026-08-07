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

const ALWAYS_IGNORE_PATHS = ['public/assets','public/dev'];

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
let globalKeepDocBlocks = false; // Toggle für DocBlocks

// Version aus package.json lesen
let version = 'unknown';
try {
    const pkg = JSON.parse(fs.readFileSync(path.join(basePath, 'package.json'), 'utf-8'));
    version = pkg.version;
} catch (_e) {
    console.warn(`${c.yellow}⚠️ package.json nicht gefunden oder fehlerhaft.${c.reset}`);
}

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
        filter: /\.(js|php|phtml|scss)$/,
        ext: '.txt',
        exclDirs: [],
        exclFiles: [],
    },
};

// --- 3. Token-Optimierungs-Logik ---

function optimizeTokens(content, fileExtension) {
    const ext = fileExtension.toLowerCase();
    const isPhpOrPhtml = ext === '.php' || ext === '.phtml';
    const isJsOrScss = ext === '.js' || ext === '.scss';

    // =========================================================================
    // 1. SCHUTZMECHANISMEN (Strings & sensible Blöcke in den Tresor)
    // =========================================================================
    const stringMap = new Map();
    let stringId = 0;

    // Zieht alle Strings (", ', `) ab und ersetzt sie durch einen Platzhalter.
    let optimizedContent = content.replace(/(["'`])(?:\\.|(?!\1)[^\\])*\1/g, (match) => {
        const id = `___STR_PLACEHOLDER_${stringId++}___`;
        stringMap.set(id, match);
        return id;
    });

    const blockMap = new Map();
    let blockId = 0;

    // Schützt <script>, <style>, <pre> und <textarea> vor der Zeilenzerstörung
    if (isPhpOrPhtml) {
        optimizedContent = optimizedContent.replace(
            /<(script|style|pre|textarea)[\s\S]*?>[\s\S]*?<\/\1>/gi,
            (match) => {
                const id = `___BLOCK_PLACEHOLDER_${blockId++}___`;
                blockMap.set(id, match);
                return id;
            }
        );
    }

    // =========================================================================
    // 2. KOMMENTARE ENTFERNEN (und DocBlocks verarbeiten)
    // =========================================================================
    if (isJsOrScss || isPhpOrPhtml) {
        // Wenn aktiviert, behalte DocBlocks (/** ... */) die ein '@' enthalten
        if (globalKeepDocBlocks) {
            optimizedContent = optimizedContent.replace(/\/\*\*[\s\S]*?\*\//g, (match) => {
                // Nur wenn ein Annotation-Tag existiert ab in den schützenden Tresor
                if (match.includes('@')) {
                    const id = `___BLOCK_PLACEHOLDER_${blockId++}___`;
                    blockMap.set(id, match);
                    return id;
                }
                // Ansonsten lassen wir es stehen, damit der nächste Schritt es löscht
                return match;
            });
        }

        // Multi-line Kommentare /* ... */ (löscht alles was nicht im Tresor ist)
        optimizedContent = optimizedContent.replace(/\/\*[\s\S]*?\*\//g, '');

        // Single-line Kommentare // ...
        // FIX: (?<!\\) ignoriert Regex escaped slashes wie \/\/
        optimizedContent = optimizedContent.replace(/(?<!\\)\/\/(?:(?!\?>).)*(?=\?>|$)/gm, '');

        if (isPhpOrPhtml) {
            // HTML-Kommentare sicher entfernen
            optimizedContent = optimizedContent.replace(/<!--[\s\S]*?-->/g, '');

            // SQL / CSS-ähnliche Kommentare (-- )
            optimizedContent = optimizedContent.replace(/(?<!!)--\s.*$/gm, '');

            // # Kommentare NUR in reinen .php Dateien löschen.
            // In .phtml zerstören sie sonst CSS-IDs (z.B. <style> #id { ... } </style>)
            if (ext === '.php') {
                optimizedContent = optimizedContent.replace(
                    /(^|[^"'])#(?!\[)(?:(?!\?>).)*(?=\?>|$)/gm,
                    (_match, prefix) => {
                        return prefix;
                    }
                );
            }
        }
    }

    // =========================================================================
    // 3. OPERATOR-PADDING (Der "=" Fix für HTML-Attribute)
    // =========================================================================
    const operatorRegex = /(?<!<\?)\s*(===|!==|<=|>=|=>|==|!=|\+=|-=|=)\s*/g;

    if (ext === '.js' || ext === '.scss') {
        // JS und SCSS können global padded werden
        optimizedContent = optimizedContent.replace(operatorRegex, ' $1 ');
    } else if (ext === '.php' || ext === '.phtml') {
        // In PHP/PHTML wird das Padding NUR NOCH innerhalb von <?php ... ?> angewendet!
        // HTML Attribute (href=) bleiben somit zu 100% unangetastet.
        optimizedContent = optimizedContent.replace(
            /(<\?[pP][hH][pP]|<\?=)([\s\S]*?)(?:\?>|$)/g,
            (_match, openTag, phpCode) => {
                return openTag + phpCode.replace(operatorRegex, ' $1 ');
            }
        );
    }

    // =========================================================================
    // 4. ZEILEN & WHITESPACE MINIMIEREN
    // =========================================================================
    const lines = optimizedContent.split(/\r?\n/);
    const optimizedLines = [];

    if (ext === '.phtml') {
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (line.length === 0) continue;

            if (optimizedLines.length > 0) {
                const lastLine = optimizedLines[optimizedLines.length - 1];
                if (
                    !line.startsWith('<') ||
                    (!lastLine.endsWith('>') && !lastLine.endsWith('?>'))
                ) {
                    optimizedLines[optimizedLines.length - 1] += ` ${line}`;
                    continue;
                }
            }
            optimizedLines.push(line);
        }
    } else {
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (line.length === 0) continue;

            if (ext === '.php' && (/^<\?php/i.test(line) || /^declare\s*\(/i.test(line))) {
                optimizedLines.push(line);
                continue;
            }

            if (
                optimizedLines.length > 0 &&
                !(ext === '.php' && /^<\?php/i.test(optimizedLines[optimizedLines.length - 1]))
            ) {
                const lastLine = optimizedLines[optimizedLines.length - 1];

                // \) und % am Ende sowie \- am Anfang für CSS-Funktionen (url, rgba) und Prozentwerte
                if (/[a-zA-Z0-9_\])%]$/.test(lastLine) && /^[a-zA-Z0-9_$-]/.test(line)) {
                    optimizedLines[optimizedLines.length - 1] += ` ${line}`;
                } else {
                    optimizedLines[optimizedLines.length - 1] += line;
                }
            } else {
                optimizedLines.push(line);
            }
        }
    }

    let joinedResult = optimizedLines.join('\n');

    // =========================================================================
    // 5. TRESOR WIEDERHERSTELLEN (Blöcke & Strings)
    // =========================================================================
    blockMap.forEach((originalBlock, placeholderKey) => {
        joinedResult = joinedResult.split(placeholderKey).join(originalBlock);
    });

    stringMap.forEach((originalString, placeholderKey) => {
        joinedResult = joinedResult.split(placeholderKey).join(originalString);
    });

    return joinedResult;
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

// Generiert einen sauberen Zeitstempel-Ordnernamen (YYYY-MM-DD_hh-mm-ss)
function getTimestampString() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}_${pad(d.getHours())}-${pad(d.getMinutes())}-${pad(d.getSeconds())}`;
}

/**
 * Funktion für den neuen Menüpunkt 6 (Gleiche Ordnerstruktur spiegeln)
 */
function startStructureMirror() {
    const timestampDirName = getTimestampString();

    // Namenszusatz hinzufügen, wenn DocBlocks aktiviert sind
    const docSuffix = globalKeepDocBlocks ? '_docblock' : '';
    const targetDirName = `${timestampDirName}_minimized${docSuffix}`;
    const targetDir = path.join(debugFolder, targetDirName);

    console.log(`\n${c.cyan}🚀 Starte Erstellung der gespiegelten Token-Struktur...`);
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
            let optimizedContent = optimizeTokens(rawContent, file.ext);

            let commentPrefix = `// Path: ${file.relPath}\n`;
            if (file.ext.toLowerCase() === '.phtml') {
                commentPrefix = `<!-- Path: ${file.relPath} -->\n`;
            }

            if (file.ext.toLowerCase() === '.php' && /^\s*<\?php/i.test(optimizedContent)) {
                optimizedContent = optimizedContent.replace(
                    /^\s*<\?php/i,
                    (match) => `${match}\n${commentPrefix.trim()}`
                );
            } else {
                optimizedContent = commentPrefix + optimizedContent;
            }

            const fileOutputDir = path.join(targetDir, path.dirname(file.relPath));
            const fileOutputPath = path.join(targetDir, file.relPath);

            if (!fs.existsSync(fileOutputDir)) fs.mkdirSync(fileOutputDir, { recursive: true });

            fs.writeFileSync(fileOutputPath, optimizedContent, 'utf-8');
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

    // Namenszusatz hinzufügen, wenn DocBlocks aktiviert sind
    const docSuffix = globalKeepDocBlocks ? '_docblock' : '';
    const outputName = `${conf.name}_${timestamp}_minimized${docSuffix}${conf.ext}`;
    const outputPath = path.join(debugFolder, outputName);

    if (!fs.existsSync(debugFolder)) fs.mkdirSync(debugFolder, { recursive: true });

    if (!silent)
        console.log(
            `\n${c.cyan}🚀 Starte token-optimierte Sammlung: ${c.bright}${conf.name}${c.reset}...`
        );

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
            const optimizedContent = optimizeTokens(rawContent, file.ext);

            combinedContent += `// ========== START FILE: [${file.relPath}] ==========\n`;
            combinedContent += `${optimizedContent}\n`;
            combinedContent += `// ========== END FILE: [${file.relPath}] ==========\n\n`;
            if (!silent) console.log(`${c.gray} + [Optimiert] ${file.relPath}${c.reset}`);
        } catch (_e) {
            if (!silent) console.log(`${c.gray} ! Überspringe (Binär?): ${file.relPath}${c.reset}`);
        }
    }

    fs.writeFileSync(outputPath, combinedContent, 'utf-8');
    const displayPath = path.relative(basePath, outputPath);
    console.log(
        `${c.green}✅ Erfolg: ${c.bright}${displayPath}${c.reset} (${foundFiles.length} Dateien optimiert).`
    );
}

function showHelp() {
    console.log(`\n${c.bright}HILFE & CLI ARGUMENTE (TOKEN OPTIMIERT)${c.reset}`);
    console.log(`${c.gray}------------------------------------------------------------${c.reset}`);
    console.table([
        { Argument: '--js', Beschreibung: 'Sammelt & optimiert nur JavaScript Dateien' },
        { Argument: '--php', Beschreibung: 'Sammelt & optimiert nur PHP Dateien' },
        { Argument: '--phtml', Beschreibung: 'Sammelt & optimiert nur PHTML Dateien' },
        { Argument: '--scss', Beschreibung: 'Sammelt & optimiert nur SCSS Dateien' },
        { Argument: '--project', Beschreibung: 'Projektweite Zusammenfassung (*.txt)' },
        {
            Argument: '--mirror',
            Beschreibung: 'Spiegelt die gesamte optimierte Ordnerstruktur (Punkt 6)',
        },
        { Argument: '--all', Beschreibung: 'Führt Punkt 1-4 automatisch aus' },
        { Argument: '--root', Beschreibung: 'Bezieht Dateien im Root-Verzeichnis mit ein' },
        {
            Argument: '--docblocks',
            Beschreibung: 'Behält DocBlocks mit @-Tags bei (hängt _docblock an den Dateinamen)',
        },
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
    if (args.includes('--docblocks')) globalKeepDocBlocks = true; // CLI

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

        const docBlockStatus = globalKeepDocBlocks
            ? `${c.green}${c.bright}AN${c.reset}`
            : `${c.red}${c.bright}AUS${c.reset}`;

        console.clear();
        console.log(`${c.cyan}===============================================`);
        console.log(`${c.cyan}    ${c.bright}DATEI-ZUSAMMENFASSUNG (TOKEN OPTIMIERT)${c.reset}`);
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
        console.log(
            `${c.bright} 6)${c.reset} ${c.green}PROJEKT-STRUKTUR SPIEGELN${c.reset} (Einzeldateien in Verzeichnissen)`
        );
        console.log(`${c.gray}-----------------------------------------------${c.reset}`);
        console.log(`${c.bright} T)${c.reset} Toggle Root-Files: [${rootStatus}]`);
        console.log(
            `${c.bright} D)${c.reset} Toggle DocBlocks (@param, etc.): [${docBlockStatus}]`
        );
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
            if (choice === 'D') {
                globalKeepDocBlocks = !globalKeepDocBlocks;
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
