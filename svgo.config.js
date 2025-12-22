/**
 * Beschreibung: Konfiguration für SVGO (SVG-Optimierung).
 * @file svgo.config.js
 * @since 5.0.0
 */

module.exports = {
    multipass: true, // Optimiert mehrfach für kleinstmögliche Dateigröße
    plugins: [
        {
            name: 'preset-default',
            params: {
                overrides: {
                    removeViewBox: false, // Wichtig für Responsive Design
                    cleanupIds: false, // IDs behalten für CSS-Ansprache
                },
            },
        },
        'removeDimensions', // Entfernt width/height (besser via CSS)
        'sortAttrs', // Sortiert Attribute für bessere Kompression
    ],
};
