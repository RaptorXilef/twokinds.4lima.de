/**
 * Beschreibung: Konfiguration für PurgeCSS.
 * @file purgecss.config.js
 * @since 5.0.0
 */

module.exports = {
    // Hier scannt PurgeCSS nach genutzten Klassen
    content: [
        './public/**/*.php',
        //'./resources/views/**/*.php', // Falls du Blade oder Twig nutzt
        './resources/js/**/*.js',
        './src/**/*.php',
        './templates/**/*.php',
    ],
    // CSS-Datei, die gesäubert werden soll
    css: ['./public/assets/css/main.min.css'],
    // WICHTIG: Klassen, die niemals gelöscht werden dürfen
    safelist: {
        standard: [
            'active',
            'show',
            'is-visible',
            'is-loading',
            '.theme-night',
            'theme-night',
        ],
        greedy: [/js-/, /swiper-/, /lightbox-/], // Behält alle Klassen, die "js-" enthalten
    },
    output: './public/assets/css/main.min.css',
};
