// @ts-check

import eslint from '@eslint/js';
import eslintConfigPrettier from 'eslint-config-prettier';
import globals from 'globals';

export default [
    // =================================================================
    // OBJEKT 1: GLOBALE IGNORES
    // Muss an erster Stelle stehen.
    // =================================================================
    {
        ignores: [
            'public/assets/', // Ignoriert alle generierten Assets
            'vendor/', // Ignoriert den PHP-Vendor-Ordner
            'node_modules/', // Ignoriert den Node-Ordner (doppelt hält besser)
            '.vscode/', // Ignoriert VSCode-Einstellungen
            '.github/', // Ignoriert GitHub-Workflows
            '.git/', // Ignoriert GitHub
            '_Notizen/', // Ignoriert Meine Code-Notizen
            '.cache/',
            '.build/',
        ],
    },

    // =================================================================
    // OBJEKT 2: Standard-Regeln von ESLint
    // =================================================================
    eslint.configs.recommended,

    // =================================================================
    // OBJEKT 3: Deaktiviert Formatierungsregeln (Prettier-Konflikte)
    // =================================================================
    eslintConfigPrettier,

    // =================================================================
    // OBJEKT 4: Meine globalen Einstellungen & Regeln
    // =================================================================
    {
        // Globale Einstellungen
        linterOptions: {
            reportUnusedDisableDirectives: true,
        },
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                // Hier werden die Umgebungen mittels Spread-Syntax (...) geladen
                ...globals.browser, // Definiert 'document', 'window', 'DataTransfer' usw. korrek
                ...globals.node, // Definiert 'process', 'module' usw. korrekt
            },
        },
        rules: {
            // Eigene ESLint-Regeln hinzufügen
            // 'off' entfernt den Fehler für console.error in deiner main.js
            // Alternativ: 'warn' lassen, wenn du es sehen willst, aber den Build nicht abbrechen möchtest.
            'no-console': 'off',
            'no-unused-vars': ['warn', { vars: 'all', args: 'none' }],
        },
    },
];
