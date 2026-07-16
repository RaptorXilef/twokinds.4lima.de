/**
 * Path: stylelint.config.js
 * Projektspezifische Anpassungen (Erweitert den Blueprint)
 */

import blueprintConfig from './stylelint.template.js';

export default {
    ...blueprintConfig, // Übernimmt alles vom Blueprint
    rules: {
        ...blueprintConfig.rules, // Übernimmt die Basis-Regeln

        // Hier deine projektspezifischen Overrides:
        // 'max-nesting-depth': [5, { ignorePseudoClasses: ['hover'] }], // Erlaubt mehr Tiefe im Projekt
        // 'no-empty-source': 'warn',
    },
    // Lokale Ignore-Files ergänzen
    ignoreFiles: [...blueprintConfig.ignoreFiles, 'src/legacy-trash/**/*.scss'],
};
