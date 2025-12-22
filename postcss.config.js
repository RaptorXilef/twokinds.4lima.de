/**
 * Beschreibung: Konfiguration für PostCSS (Autoprefixer & Minifizierung).
 * @file postcss.config.js
 * @since 5.0.0
 */

module.exports = {
    map: false, // true Aktiviert Source Maps für PostCSS
    plugins: [
        require('autoprefixer'),
        require('cssnano')({
            preset: 'default',
        }),
    ],
};
