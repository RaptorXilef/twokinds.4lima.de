export class ReactiveState {
    /**
     * Erstellt ein reaktives State-Objekt.
     * @param {Object} initialState Die Startwerte
     * @param {Function} onUpdate Callback, das bei jeder Änderung gefeuert wird: (property, newValue, state)
     */
    constructor(initialState, onUpdate) {
        return new Proxy(initialState, {
            set: (target, property, value) => {
                // Verhindere unnötige Updates, wenn der Wert gleich bleibt
                if (target[property] !== value) {
                    target[property] = value;
                    if (onUpdate) {
                        onUpdate(property, value, target);
                    }
                }
                return true;
            },
        });
    }
}
