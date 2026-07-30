export class ReactiveState {
    /**
     * Erstellt ein reaktives State-Objekt mit optionalem LocalStorage Auto-Save.
     * @param {Object} initialState Die Startwerte
     * @param {Function} onUpdate Callback, das bei jeder Änderung gefeuert wird: (property, newValue, state)
     * @param {string|null} cacheKey Einzigartiger Key für den LocalStorage Draft
     */
    constructor(initialState, onUpdate, cacheKey = null) {
        this._cacheKey = cacheKey;

        // Versuche alte Drafts aus dem Cache wiederherzustellen
        if (this._cacheKey) {
            const cached = localStorage.getItem(this._cacheKey);
            if (cached) {
                try {
                    Object.assign(initialState, JSON.parse(cached));
                    console.info(
                        `[ReactiveState] Draft für '${this._cacheKey}' wiederhergestellt.`
                    );
                } catch (e) {
                    console.warn('Konnte Cache nicht lesen', e);
                }
            }
        }

        // Hilfsfunktion, um den Cache nach erfolgreichem API-Speichern zu leeren
        initialState.clearCache = () => {
            if (this._cacheKey) localStorage.removeItem(this._cacheKey);
        };

        return new Proxy(initialState, {
            set: (target, property, value) => {
                if (target[property] !== value) {
                    target[property] = value;

                    // Bei jeder Änderung speichern wir den State in den LocalStorage (außer es ist die clearCache Methode)
                    if (this._cacheKey && property !== 'clearCache') {
                        // Funktionsobjekte können nicht stringifiziert werden, daher filtern
                        const pureData = Object.fromEntries(
                            Object.entries(target).filter(([_, v]) => typeof v !== 'function')
                        );
                        localStorage.setItem(this._cacheKey, JSON.stringify(pureData));
                    }

                    if (onUpdate) {
                        onUpdate(property, value, target);
                    }
                }
                return true;
            },
        });
    }
}
