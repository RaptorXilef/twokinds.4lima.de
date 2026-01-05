/**
 * Beschreibung: Konfiguration für PurgeCSS zur Eliminierung ungenutzter CSS-Regeln.
 * Description: Configuration for PurgeCSS to eliminate unused CSS rules.
 *
 * @file purgecss.config.js
 * @since 5.0.0
 * - feat(config): Initiales Setup der PurgeCSS-Konfiguration für PHP und JS.
 *
 * In dieser Datei verwendete Konfigurationsobjekte:
 * Used configuration objects in this file:
 * - content: (Array) Pfade zu Dateien, die nach verwendeten CSS-Klassen gescannt werden.
 * - css: (Array) Liste der CSS-Dateien, die gesäubert werden sollen.
 * - safelist: (Object) Klassen, die PurgeCSS niemals entfernen darf (z.B. dynamische Klassen).
 * - output: (String) Zielpfad für die bereinigte CSS-Datei.
 */

module.exports = {
    // Orte, an denen PurgeCSS nach genutzten Klassen sucht
    // Locations where PurgeCSS scans for used classes
    content: [
        './public/**/*.php',
        './resources/js/**/*.js',
        './src/**/*.php',
        './templates/**/*.php',
        './templates/**/*.phtml',
    ],

    // Die CSS-Datei, die gesäubert werden soll
    // The CSS file to be purged
    css: ['./public/assets/css/main.min.css'],

    // WICHTIG: Klassen, die niemals gelöscht werden dürfen
    // IMPORTANT: Classes that must never be removed
    safelist: {
        // Exakte Klassennamen
        // Exact class names
        standard: ['active', 'show', 'is-visible', 'is-loading', 'theme-night'],
        // Reguläre Ausdrücke (behält alles, was mit diesen Präfixen beginnt)
        // Regular expressions (keeps everything starting with these prefixes)
        greedy: [/js-/, /swiper-/, /lightbox-/, /summernote-/],
    },

    // Zielpfad der bereinigten Datei (überschreibt hier das Original)
    // Destination path of the purged file (overwrites original here)
    output: './public/assets/css/main.min.css',
};
