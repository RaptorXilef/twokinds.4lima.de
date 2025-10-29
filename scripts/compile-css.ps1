# @file      ROOT/scripts/compile-css.ps1
# @package   twokinds.4lima.de
# @author    Felix M. (@RaptorXilef)
# @copyright 2025 Felix M.
# @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
# @link      https://github.com/RaptorXilef/twokinds.4lima.de
# @version   2.1.0
# @since     1.0.0 Initiale Erstellung
# @since     2.0.0 Das Build-Skript `compile-css.ps1` wurde überarbeitet, um alle `.scss`-Dateien im Quellordner automatisch zu verarbeiten, statt manuell gepflegt zu werden.
# @since     2.1.0 Refaktorisierung zur Vermeidung von Codeduplizierung durch Einführung einer Funktion.

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

    # Hole alle .scss-Dateien im Quellordner
    $scssFiles = Get-ChildItem -Path $ScssPath -Filter *.scss

    if ($scssFiles.Count -eq 0) {
        Write-Host "Keine .scss-Dateien in $ScssPath gefunden."
        return
    }

    Write-Host "Starte SCSS-Kompilierung für $ScssPath..."

    # Gehe jede gefundene Datei durch
    foreach ($file in $scssFiles) {
        $baseName = $file.BaseName # z.B. "main" oder "main_dark"
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
            Write-Warning "Fehler beim Kompilieren von $($file.Name)."
        }
    }
}

# Verarbeite den Hauptordner
Invoke-CssCompilation -ScssPath "..\resources\scss" -CssPath "..\public\assets\css"

# Verarbeite den Admin-Ordner
Invoke-CssCompilation -ScssPath "..\resources\scss\admin" -CssPath "..\public\assets\css\admin"


Write-Host "Kompilierung abgeschlossen."
pause
