<?php

declare(strict_types=1);

namespace App\Scripts;

/**
 * Setup-Zentrale
 * Übernimmt die Initialisierung der Umgebung und Tool-Pflege.
 */
class Setup
{
    private const array DIRS = [
        '.cache/phpstan',
        '.cache/phpcs',
        '.cache/phpunit',
        '.cache/php-cs-fixer',
        'tools',
        '.build/reports',
    ];

    private const array TOOLS = [
        'tools/infection.phar' => 'https://github.com/infection/infection/releases/latest/download/infection.phar',
        'tools/phpcpd.phar'    => 'https://phar.phpunit.de/phpcpd.phar',
    ];

    public static function init(): void
    {
        self::initEnv();
        self::createDirectories();
        self::updateTools();
        echo "\n✅ Setup erfolgreich abgeschlossen.\n";
    }

    private static function initEnv(): void
    {
        echo "📄 Prüfe .env Datei...\n";

        if (\file_exists('.env')) {
            echo "   [INFO] .env existiert bereits.\n";

            return;
        }

        if (! \file_exists('.env.example')) {
            echo "   [HINWEIS] Keine .env.example gefunden. Bitte .env manuell erstellen.\n";

            return;
        }

        \copy('.env.example', '.env');
        echo "   [OK] .env aus .env.example erstellt.\n";
    }

    public static function createDirectories(): void
    {
        echo "📁 Erstelle Verzeichnisstruktur...\n";
        foreach (self::DIRS as $dir) {
            if (\is_dir($dir)) {
                continue;
            }

            if (! \mkdir($dir, 0o777, true)) {
                echo "   [FEHLER] Konnte $dir nicht erstellen!\n";

                continue;
            }

            echo "   [OK] $dir\n";
        }
    }

    public static function updateTools(): void
    {
        echo "🛠️  Aktualisiere PHAR-Tools...\n";
        foreach (self::TOOLS as $path => $url) {
            echo '   Lade ' . \basename($path) . '... ';

            if (\copy($url, $path)) {
                echo "Fertig.\n";

                continue;
            }

            echo "Fehlgeschlagen! (Prüfe Internetverbindung)\n";
        }
    }

    public static function clearBuild(): void
    {
        $path = 'public/dist';
        echo "🧹 Bereinige Build-Verzeichnis ($path)...\n";
        if (! \is_dir($path)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $fileinfo) {
            $todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
            $todo($fileinfo->getRealPath());
        }
        echo "   Fertig.\n";
    }

    /**
     * Verarbeitet CLI-Argumente
     *
     * @param array<int, string> $argv
     */
    public static function runConsole(array $argv): void
    {
        if (\php_sapi_name() !== 'cli' || ! isset($argv[1])) {
            return;
        }

        match ($argv[1]) {
            'init'  => self::init(),
            'clear' => self::clearBuild(),
            'up'    => self::updateTools(),
            default => print ("Unbekannter Befehl: {$argv[1]}\n")
        };
    }
}
