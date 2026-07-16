<?php

declare(strict_types=1);

$srcDir     = 'src/';
$outputDir  = '.debug';
$outputFile = $outputDir . '/ai_context_map.txt';

if (! \is_dir($outputDir)) {
    \mkdir($outputDir, 0o755, true);
}

$output      = "Project Context Map (Full DocBlocks)\n======================================\n";
$ignoreFiles = ['Container.php', 'app.php'];

$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcDir));

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php' || \in_array($file->getFilename(), $ignoreFiles, true)) {
        continue;
    }

    $content = \file_get_contents($file->getPathname());
    $tokens  = \token_get_all($content);

    $output .= "\n--- File: " . $file->getFilename() . " ---\n";

    $lastDocComment = '';
    $count          = \count($tokens);

    for ($i = 0; $i < $count; ++$i) {
        $token = $tokens[$i];

        // Gesamten DocBlock erfassen
        if (\is_array($token) && $token[0] === \T_DOC_COMMENT) {
            // Bereinigt den DocBlock von den Sternchen und Zeilenumbrüchen
            $rawDoc   = $token[1];
            $lines    = \explode("\n", $rawDoc);
            $cleanDoc = [];
            foreach ($lines as $line) {
                $line = \trim($line, "/* \t\r");
                if (! empty($line)) {
                    $cleanDoc[] = $line;
                }
            }
            // Nimmt die erste Zeile des Kommentars ohne Sternchen
            $lastDocComment = \implode("\n", $cleanDoc);
        }

        // 1. Erfasse 'use' Statements
        if (\is_array($token) && $token[0] === \T_USE) {
            $useStmt = '';
            for ($j = $i + 1; $j < $count && $tokens[$j] !== ';'; ++$j) {
                if (\is_array($tokens[$j])) {
                    $useStmt .= $tokens[$j][1];
                } else {
                    $useStmt .= $tokens[$j];
                }
            }
            $output .= 'Use: ' . \trim($useStmt) . "\n";
            $lastDocComment = ''; // Reset DocBlock
        }

        // 2. Erfasse Klassennamen
        if (\is_array($token) && $token[0] === \T_CLASS) {
            if (isset($tokens[$i + 2]) && \is_array($tokens[$i + 2])) {
                $className = $tokens[$i + 2][1];
                $output .= "\nClass: $className\n";
                if (! empty($lastDocComment)) {
                    $output .= '   Doc: ' . \str_replace("\n", "\n        ", $lastDocComment) . "\n";
                }
            }
            $lastDocComment = ''; // Reset DocBlock
        }

        // 3. Erfasse Methodennamen
        if (\is_array($token) && $token[0] === \T_FUNCTION) {
            if (isset($tokens[$i + 2]) && \is_array($tokens[$i + 2]) && $tokens[$i + 2][0] === \T_STRING) {
                $methodName = $tokens[$i + 2][1];
                $output .= "  -> Method: $methodName()\n";

                if (! empty($lastDocComment)) {
                    $output .= '     Doc: ' . \str_replace("\n", "\n          ", $lastDocComment) . "\n";
                }
            }
            $lastDocComment = ''; // Reset DocBlock
        }
    }
}

\file_put_contents(
    $outputFile,
    $output,
    \LOCK_EX,
);
echo "Kontext-Datei '$outputFile' erfolgreich mit vollständigen DocBlocks erstellt.\n";
