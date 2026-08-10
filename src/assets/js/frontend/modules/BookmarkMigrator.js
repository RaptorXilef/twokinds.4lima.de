/**
 * Führt die stille Migration von v5 Lesezeichen auf das v6 Format aus.
 */
export function runBookmarkMigration() {
    try {
        const oldDataRaw = localStorage.getItem('comicBookmarks');

        // Wenn es keine alten Daten gibt, sind wir fertig und brechen ab.
        if (!oldDataRaw) return;

        const oldData = JSON.parse(oldDataRaw);
        if (!Array.isArray(oldData)) return;

        // Wir laden die neue Map (falls der Nutzer in v6 schon was gespeichert hat, bevor das Skript lief)
        let newMap = {};
        try {
            newMap = JSON.parse(localStorage.getItem('comicBookmarksMap') || '{}');
        } catch (_e) {
            newMap = {};
        }

        let migratedCount = 0;

        // Altes Format war [ ["20260702", {id, page, permalink, thumb, added}], ... ]
        oldData.forEach((item) => {
            if (Array.isArray(item) && item.length === 2) {
                const val = item[1];
                if (val?.id) {
                    // Nur migrieren, wenn in der neuen Map noch nicht vorhanden
                    if (!newMap[val.id]) {
                        newMap[val.id] = {
                            id: val.id,
                            added: val.added || Date.now(),
                        };
                        migratedCount++;
                    }
                }
            }
        });

        // Wenn wir etwas konvertiert haben, oder die alte Liste leer war, Map speichern
        localStorage.setItem('comicBookmarksMap', JSON.stringify(newMap));

        // Alten Schlüssel löschen, damit dieses Skript beim nächsten Seitenaufruf sofort abbricht
        localStorage.removeItem('comicBookmarks');

        if (migratedCount > 0) {
            console.info(
                `[BookmarkMigrator] Erfolgreich ${migratedCount} alte Lesezeichen in das v6-Format überführt.`
            );
        }
    } catch (err) {
        console.error('[BookmarkMigrator] Fehler bei der Lesezeichen-Migration:', err);
    }
}
