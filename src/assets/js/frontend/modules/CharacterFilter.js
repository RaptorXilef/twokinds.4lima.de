export class CharacterFilter {
    constructor() {
        this.wrapper = document.getElementById('char-display-wrapper');

        // Nur initialisieren, wenn wir auf einer Seite mit Charakter-Grid sind
        if (!this.wrapper) return;

        this.initToggle();
        this.initFilters();
    }

    // --- Mini-Debounce ---
    debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    initToggle() {
        const toggleBtn = document.getElementById('toggle-char-view');
        const toggleText = document.getElementById('toggle-view-text');

        if (toggleBtn && this.wrapper) {
            toggleBtn.addEventListener('click', () => {
                const currentView = this.wrapper.getAttribute('data-active-view');
                const newView = currentView === 'tags' ? 'grouped' : 'tags';
                this.wrapper.setAttribute('data-active-view', newView);

                if (newView === 'tags') {
                    if (toggleText) toggleText.textContent = 'Gruppierte Ansicht';
                    toggleBtn.querySelector('i').className = 'fas fa-th-list';
                } else {
                    if (toggleText) toggleText.textContent = 'Alphabetische Ansicht';
                    toggleBtn.querySelector('i').className = 'fas fa-font';
                }
            });
        }
    }

    initFilters() {
        this.searchInput = document.getElementById('char-filter-search');
        this.emptyMsg = document.getElementById('char-filter-empty-msg');
        this.btnReset = document.getElementById('char-filter-reset');

        // Wenn keine Filterleiste vorhanden ist (z.B. auf Comicseiten), brechen wir hier ab
        if (!this.searchInput) return;

        this.filters = {
            search: this.searchInput,
            gender: document.getElementById('char-filter-gender'),
            ageMin: document.getElementById('char-filter-age-min'),
            ageMax: document.getElementById('char-filter-age-max'),
            keidranMin: document.getElementById('char-filter-keidran-min'),
            keidranMax: document.getElementById('char-filter-keidran-max'),
            species: document.getElementById('char-filter-species'),
            subspecies: document.getElementById('char-filter-subspecies'),
            rank: document.getElementById('char-filter-rank'),
            languages: document.getElementById('char-filter-languages'),
            haircolor: document.getElementById('char-filter-haircolor'),
            eyecolor: document.getElementById('char-filter-eyecolor'),
            furcolor: document.getElementById('char-filter-furcolor'),
            isDead: document.getElementById('char-filter-is-dead'),
        };

        // Debounce mit 250ms Verzögerung!
        const applyFiltersDebounced = this.debounce(() => this.applyFilters(), 250);

        Object.values(this.filters).forEach((input) => {
            if (input) {
                if (
                    input.tagName === 'INPUT' &&
                    (input.type === 'text' || input.type === 'number')
                ) {
                    input.addEventListener('input', applyFiltersDebounced);
                } else {
                    input.addEventListener('change', () => this.applyFilters());
                }
            }
        });
        if (this.btnReset) {
            this.btnReset.addEventListener('click', () => {
                Object.values(this.filters).forEach((input) => {
                    if (input) input.value = '';
                });
                this.applyFilters(); // Hier direkt ausführen ohne Debounce
            });
        }
    }
    checkAgeRange(charAgeStr, fMinStr, fMaxStr) {
        if (!fMinStr && !fMaxStr) return true;
        if (!charAgeStr) return false;
        const nums = charAgeStr.match(/\d+/g);
        if (!nums) return false;
        const numVals = nums.map((n) => parseInt(n, 10));
        const charMin = Math.min(...numVals);
        const charMax = Math.max(...numVals);
        const fMin = fMinStr ? parseInt(fMinStr, 10) : 0;
        const fMax = fMaxStr ? parseInt(fMaxStr, 10) : Infinity;
        return charMax >= fMin && charMin <= fMax;
    }
    applyFilters() {
        const sVal = this.filters.search?.value.toLowerCase().trim() || '';
        const gVal = this.filters.gender?.value || '';
        const aMin = this.filters.ageMin?.value || '';
        const aMax = this.filters.ageMax?.value || '';
        const kMin = this.filters.keidranMin?.value || '';
        const kMax = this.filters.keidranMax?.value || '';
        const spVal = this.filters.species?.value || '';
        const subVal = this.filters.subspecies?.value || '';
        const rVal = this.filters.rank?.value || '';
        const lVal = this.filters.languages?.value || '';
        const hcVal = this.filters.haircolor?.value || '';
        const ecVal = this.filters.eyecolor?.value || '';
        const fcVal = this.filters.furcolor?.value || '';
        const dVal = this.filters.isDead?.value || '';
        let globalMatchCount = 0;

        document.querySelectorAll('.character-item').forEach((item) => {
            let isMatch = true;

            if (sVal && !item.dataset.search.includes(sVal)) isMatch = false;
            if (gVal && !item.dataset.gender.includes(gVal)) isMatch = false;
            if (spVal && !item.dataset.species.includes(spVal)) isMatch = false;
            if (subVal && !item.dataset.subspecies.includes(subVal)) isMatch = false;
            if (rVal && !item.dataset.rank.includes(rVal)) isMatch = false;
            if (lVal && !item.dataset.languages.includes(lVal)) isMatch = false;
            if (hcVal && !item.dataset.haircolor.includes(hcVal)) isMatch = false;
            if (ecVal && !item.dataset.eyecolor.includes(ecVal)) isMatch = false;
            if (fcVal && !item.dataset.furcolor.includes(fcVal)) isMatch = false;
            if (dVal && item.dataset.isdead !== dVal) isMatch = false;
            if (aMin || aMax) {
                if (!this.checkAgeRange(item.dataset.age, aMin, aMax)) isMatch = false;
            }
            if (kMin || kMax) {
                if (!this.checkAgeRange(item.dataset.keidranage, kMin, kMax)) isMatch = false;
            }
            item.style.display = isMatch ? '' : 'none';
            if (isMatch) globalMatchCount++;
        });

        document.querySelectorAll('.character-group').forEach((group) => {
            const items = Array.from(group.querySelectorAll('.character-item'));
            const hasVisible = items.some((el) => el.style.display !== 'none');
            group.style.display = hasVisible ? '' : 'none';
        });

        if (this.emptyMsg) {
            this.emptyMsg.style.display = globalMatchCount === 0 ? 'block' : 'none';
        }
    }
}
