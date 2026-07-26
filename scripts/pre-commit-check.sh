#!/bin/bash

# Farben für bessere Sichtbarkeit
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🔍 Starte Qualitäts-Check..."

# Wir führen lint-staged aus (prüft nur die Dateien, die du committen willst)
# Running lint-staged (checks only the files staged for commit)
npx lint-staged

# Check: Hat lint-staged Fehler gefunden? (Exit-Code ungleich 0)
# Check: Did lint-staged find any errors? (Exit code not equal to 0)
if [ $? -ne 0 ]; then
    echo -e "\n${YELLOW}⚠️  ACHTUNG / WARNING: Es wurden Fehler gefunden! / Errors or style violations found!${NC}"
    echo "Möchtest du trotzdem committen? / Do you want to commit anyway? (y/n)"

    # Damit die Eingabe unter Windows/Git-Bash funktioniert:
    # Ensuring input works on Windows/Git-Bash:
    exec < /dev/tty
    read -p "> " confirm

    if [ "$confirm" != "y" ]; then
        echo "❌ Commit abgebrochen / aborted."
        echo "Fix die Fehler oder nutze --no-verify / Fix the errors or use --no-verify."
        exit 1
    fi
    echo "🚀 Okay, 'YOLO-Commit' wird durchgeführt / proceeding..."
fi

exit 0
