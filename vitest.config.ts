import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        // Ermöglicht globale Test-Funktionen wie 'describe', 'it', 'expect'
        // Ohne dass man sie in jeder Datei importieren muss
        globals: true,
        // Wir nutzen jsdom, um eine Browser-Umgebung für JS/UI-Tests zu simulieren
        environment: 'jsdom',
        include: ['**/*.{test,spec}.{js,ts}'],

        // PFAD-FIX: Hier bändigen wir den HTML-Reporter
        reporters: ['default', ['html', { outputFile: './.build/reports/vitest/index.html' }]],

        coverage: {
            provider: 'v8', // Nutzt die V8-Engine für schnelle Coverage-Reports
            reporter: ['text', 'json', 'html'],
            // Auch die Coverage-Reports wandern in den zentralen Build-Ordner
            reportsDirectory: './.build/reports/vitest-coverage',
        },
    },
    // VITE-LOGIK: Hier bringen wir Vitest SCSS bei
    css: {
        preprocessorOptions: {
            scss: {
                // Hier gibst du den Pfad zu deiner globalen Variablen-Datei an
                // So sind $primary-color etc. in jedem Test/Komponente verfügbar
                additionalData: `@use "src/scss/abstracts/_variables.scss" as *;`,
            },
        },
    },
});
