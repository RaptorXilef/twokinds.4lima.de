# @file      ROOT/scripts/minify-js.ps1
# @package   twokinds.4lima.de
# @author    Felix M. (@RaptorXilef)
# @copyright 2025 Felix M.
# @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
# @link      https://github.com/RaptorXilef/twokinds.4lima.de
# @version   2.1.0
# @since     1.0.0 Initiale Erstellung
# @since     2.0.0 Das Build-Skript `minify-js.ps1` wurde überarbeitet, um alle `.js`-Dateien im Quellordner automatisch zu verarbeiten, statt manuell gepflegt zu werden.
# @since     2.1.0 Refaktorisierung zur Vermeidung von Codeduplizierung durch Einführung einer Funktion.

# Funktion zur Verarbeitung eines JS-Pfades
function Invoke-JsMinification {
    param (
        [string]$JsPath,
        [string]$MinJsPath
    )

    # Stelle sicher, dass der Zielordner existiert
    if (-not (Test-Path -Path $MinJsPath)) {
        New-Item -ItemType Directory -Path $MinJsPath | Out-Null
    }

    # Hole alle .js-Dateien im Quellordner, die NICHT .min.js sind
    $jsFiles = Get-ChildItem -Path $JsPath -Filter *.js | Where-Object { $_.Name -notlike "*.min.js" }

    if ($jsFiles.Count -eq 0) {
        Write-Host "Keine .js-Dateien in $JsPath gefunden."
        return
    }

    Write-Host "Starte JavaScript-Minifizierung für $JsPath..."

    # Gehe jede gefundene Datei durch
    foreach ($file in $jsFiles) {
        $baseName = $file.BaseName # z.B. "common"
        $jsFile = $file.FullName
        $minJsFile = Join-Path -Path $MinJsPath -ChildPath ($baseName + ".min.js")
        # $mapFile = $minJsFile + ".map"
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
}

# Verarbeite den Hauptordner
Invoke-JsMinification -JsPath "..\resources\js" -MinJsPath "..\public\assets\js"

# Verarbeite den Admin-Ordner
Invoke-JsMinification -JsPath "..\resources\js\admin" -MinJsPath "..\public\assets\js\admin"


Write-Host "Minifizierung abgeschlossen."
pause
