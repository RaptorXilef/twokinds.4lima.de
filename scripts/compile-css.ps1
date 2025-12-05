# @file      ROOT/scripts/compile-css.ps1
# @package   twokinds.4lima.de
# @author    Felix M. (@RaptorXilef)
# @copyright 2025 Felix M.
# @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
# @link      https://github.com/RaptorXilef/twokinds.4lima.de
# @version   5.0.0
# @since     2.0.0
#  - Initiale Erstellung
#  - Das Build-Skript `compile-css.ps1` wurde überarbeitet, um alle `.scss`-Dateien im Quellordner automatisch zu
#     verarbeiten, statt manuell gepflegt zu werden.
#  - Refaktorisierung zur Vermeidung von Codeduplizierung durch Einführung einer Funktion.
# @since     5.0.0
#  - (7-1 Refactoring) Das Skript wurde so angepasst, dass es nur noch SCSS-Dateien kompiliert, die NICHT mit einem
#     Unterstrich (_) beginnen.
#
# Funktion zur Verarbeitung eines SCSS-Pfades
function Invoke-CssCompilation {
    param (
        [string]$ScssPath,
        [string]$CssPath
    )

    # Stelle sicher, dass der Zielordner existiert
    if (-not (Test-Path -Path $CssPath)) {
        New-Item -ItemType Directory -Path $CssPath | Out-Null
    }

    # *** START ÄNDERUNG (7-1 Refactoring) ***
    # Hole alle .scss-Dateien, die NICHT mit einem Unterstrich (_) beginnen.
    # Get all .scss files that do NOT start with an underscore (_).
    $scssFiles = Get-ChildItem -Path $ScssPath -Filter "*.scss" | Where-Object { $_.Name -notlike '_*' }
    # *** ENDE ÄNDERUNG ***

    # Prüfen, ob Dateien gefunden wurden
    if ($scssFiles.Count -eq 0) {
        Write-Host "Keine .scss-Dateien (ohne _) in $ScssPath gefunden."
        return
    }

    Write-Host "Starte SCSS-Kompilierung für $ScssPath..."

    # Gehe jede gefundene Datei durch (sollte jetzt nur noch z.B. main.scss sein)
    foreach ($file in $scssFiles) {
        $baseName = $file.BaseName # z.B. "main"
        $scssFile = $file.FullName
        $cssFile = Join-Path -Path $CssPath -ChildPath ($baseName + ".css")
        $minCssFile = Join-Path -Path $CssPath -ChildPath ($baseName + ".min.css")

        Write-Host "Kompiliere $($file.Name)..."

        # 1. Schritt: SCSS zu CSS kompilieren
        sass --no-source-map $scssFile $cssFile

        # 2. Schritt: Prüfen, ob Sass erfolgreich war (Exit-Code 0)
        if ($LASTEXITCODE -eq 0) {
            Write-Host "Minifiziere $baseName.css..."

            # 3. Schritt: CSS minifzieren
            npx clean-css-cli --source-map -o $minCssFile $cssFile

            if ($LASTEXITCODE -ne 0) {
                Write-Warning "Fehler beim Minifizieren von $baseName.css."
            }
        } else {
            Write-Warning "Fehler beim Kompilieren von $($file.Name). Überspringe Minifizierung."
        }
    }
}

# Definiere Pfade (basierend auf der Skript-Position)
$ScriptPath = $PSScriptRoot
$RootPath = (Resolve-Path (Join-Path -Path $ScriptPath -ChildPath "..")).Path
$ResourcesScssPath = Join-Path -Path $RootPath -ChildPath "resources\scss"
$PublicCssPath = Join-Path -Path $RootPath -ChildPath "public\assets\css"

# Führe die Kompilierung für den Hauptpfad aus
Invoke-CssCompilation -ScssPath $ResourcesScssPath -CssPath $PublicCssPath

Write-Host "Kompilierung abgeschlossen."
pause
