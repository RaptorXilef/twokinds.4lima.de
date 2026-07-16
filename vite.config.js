import path from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        // Erlaubt den Zugriff von anderen Domains (deiner .local Seite)
        cors: true,
        strictPort: true,
        port: 5173,
        // Wir zwingen HMR, die IP oder localhost stabil zu nutzen
        hmr: {
            host: 'localhost',
        },
    },
    build: {
        // Hier landen die fertigen Dateien für die Website
        outDir: 'public/dist',
        emptyOutDir: true,
        manifest: true, // Erzeugt eine manifest.json für PHP
        rollupOptions: {
            input: {
                main: path.resolve(__dirname, 'src/assets/js/main.js'),
                styles: path.resolve(__dirname, 'src/assets/scss/main.scss'),
            },
        },
    },
});
