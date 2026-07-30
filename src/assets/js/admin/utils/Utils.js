/**
 * Verhindert, dass eine Funktion zu oft in kurzer Zeit aufgerufen wird (z.B. bei Such-Inputs)
 * @param {Function} func Die auszuführende Funktion
 * @param {number} wait Verzögerung in Millisekunden
 */
export function debounce(func, wait = 250) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
