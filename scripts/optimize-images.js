import fs from 'node:fs/promises';
import path from 'node:path';
import { glob } from 'glob';
import sharp from 'sharp';

const INPUT_DIR = 'src/assets/img';
const OUTPUT_DIR = 'public/assets/img';

async function optimize() {
    // Alle gängigen Bildformate finden (jetzt inkl. .gif)
    const files = await glob(`${INPUT_DIR}/**/*.{jpg,jpeg,png,tiff,webp,gif}`);

    console.log(`🚀 Starte intelligente Optimierung von ${files.length} Bildern...`);

    const promises = files.map(async (file) => {
        // 1. Pfad-Logik für Unterordner
        const relativePath = path.relative(INPUT_DIR, path.dirname(file)); // z.B. "meinordner"
        const fileName = path.basename(file, path.extname(file));
        const outputFolder = path.join(OUTPUT_DIR, relativePath);
        const outputPath = path.join(outputFolder, `${fileName}.webp`);

        // Sicherstellen, dass der spezifische Unterordner existiert
        await fs.mkdir(outputFolder, { recursive: true });

        // 2. Sharp-Logik mit Animations-Support
        return sharp(file, { animated: true }) // Erlaubt Multi-Frame Bilder (GIF/WebP)
            .webp({
                quality: 75,
                effort: 6, // Höchste Kompressionsstufe (langsamer, aber kleiner)
                smartSubsample: true,
                // mixed: true erlaubt Sharp, innerhalb einer Animation
                // zwischen verlustfrei und verlustbehaftet zu wechseln - extrem effizient!
                mixed: true,
                // Sharp erkennt automatisch, ob ein Alpha-Kanal (Transparenz)
                // nötig ist oder nicht.
            })
            .toFile(outputPath);
    });

    await Promise.all(promises);
    console.log(
        '✅ Alle Bilder nach WebP (75%) konvertiert und optimiert! Struktur beibehalten & Animationen gerettet!'
    );
}

optimize().catch(console.error);
