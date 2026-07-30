/**
 * Factory Funktion: Erstellt ein reaktives State-Objekt.
 * (Umgeht den no-constructor-return Linter-Fehler)
 */
/**
 * Erstellt ein reaktives State-Objekt mit optionalem LocalStorage Auto-Save.
 * @param {Object} initialState Die Startwerte
 * @param {Function} onUpdate Callback, das bei jeder Änderung gefeuert wird: (property, newValue, state)
 * @param {string|null} cacheKey Einzigartiger Key für den LocalStorage Draft
 */
export function createReactiveState(initialState, onUpdate, cacheKey = null, tracker = null) {
    // Versuche alte Drafts aus dem Cache wiederherzustellen
    if (cacheKey) {
        const cached = localStorage.getItem(cacheKey);
        if (cached) {
            try {
                Object.assign(initialState, JSON.parse(cached));
            } catch (e) {
                console.warn('Konnte Cache nicht lesen', e);
            }
        }
    }

    // Hilfsfunktion, um den Cache nach erfolgreichem API-Speichern zu leeren
    initialState.clearCache = () => {
        if (cacheKey) localStorage.removeItem(cacheKey);
    };

    return new Proxy(initialState, {
        set: (target, property, value) => {
            if (target[property] !== value) {
                target[property] = value;

                if (tracker && property !== 'clearCache') {
                    tracker.markDirty();
                }

                // Bei jeder Änderung speichern wir den State in den LocalStorage (außer es ist die clearCache Methode)
                if (cacheKey && property !== 'clearCache') {
                    // Funktionsobjekte können nicht stringifiziert werden, daher filtern
                    const pureData = Object.fromEntries(
                        Object.entries(target).filter(([_, v]) => typeof v !== 'function')
                    );
                    localStorage.setItem(cacheKey, JSON.stringify(pureData));
                }

                if (onUpdate) {
                    onUpdate(property, value, target);
                }
            }
            return true;
        },
    });
}
