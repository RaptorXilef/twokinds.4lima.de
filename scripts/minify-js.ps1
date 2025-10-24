# @file      ROOT/scripts/minify-js.ps1
# @package   twokinds.4lima.de
# @author    Felix M. (@RaptorXilef)
# @copyright 2025 Felix M.
# @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
# @link      https://github.com/RaptorXilef/twokinds.4lima.de
# @version   2.0.0
# @since     1.0.0 Initiale Erstellung
# @since     2.0.0 Das Build-Skript `minify-js.ps1` wurde überarbeitet, um alle `.js`-Dateien im Quellordner automatisch zu verarbeiten, statt manuell gepflegt zu werden.


# Definiere die Quell- und Zielpfade
$jsPath = "..\resources\js"
$minJsPath = "..\public\assets\js"

# Stelle sicher, dass der Zielordner existiert
if (-not (Test-Path -Path $minJsPath)) {
    New-Item -ItemType Directory -Path $minJsPath | Out-Null
}

# Hole alle .js-Dateien im Quellordner, die NICHT .min.js sind
$jsFiles = Get-ChildItem -Path $jsPath -Filter *.js | Where-Object { $_.Name -notlike "*.min.js" }

Write-Host "Starte JavaScript-Minifizierung..."

# Gehe jede gefundene Datei durch
foreach ($file in $jsFiles) {
    $baseName = $file.BaseName # z.B. "common"
    $jsFile = $file.FullName
    $minJsFile = Join-Path -Path $minJsPath -ChildPath ($baseName + ".min.js")
    $mapFile = $minJsFile + ".map"
    $mapFileName = $baseName + ".min.js.map" # Dateiname für die Source-Map-Direktive

    Write-Host "Minifiziere $($file.Name)..."
    
    # 1. Schritt: JS-Datei mit terser minifizieren und Source Map erstellen
    # Wir leiten die Source Map in eine separate Datei um und verweisen darauf
    npx terser $jsFile --source-map "filename='$mapFileName',url='$mapFileName'" --output $minJsFile
    
    # 2. Schritt: Prüfen, ob terser erfolgreich war (Exit-Code 0)
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Fehler beim Minifizieren von $($file.Name)."
    }
}

Write-Host "Minifizierung abgeschlossen."
pause
