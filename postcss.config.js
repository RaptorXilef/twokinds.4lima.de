/**
 * Beschreibung: Konfiguration für PostCSS zur Verarbeitung von CSS (Autoprefixer & Minifizierung).
 * @file postcss.config.js
 * @since 5.0.0
 * - chore(config): PostCSS initial aufgesetzt mit autoprefixer und cssnano.
 *
 * Variablen & Plugins:
 * - autoprefixer: Fügt Vendor-Präfixe für Browser-Kompatibilität hinzu.
 * - cssnano: Optimiert und minifiziert das CSS.
 */

module.exports = {
    map: false, // Deaktiviert Source Maps für den finalen Build-Schritt
    plugins: [
        require('autoprefixer'),
        require('cssnano')({
            preset: 'default',
        }),
    ],
};
