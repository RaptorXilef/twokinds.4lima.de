export class ArchiveManager {
    constructor() {
        this.chapters = document.querySelectorAll('.chapter');
        this.STORAGE_KEY = 'archiveExpansion';

        // Nur initialisieren, wenn wir auch wirklich auf der Archiv-Seite sind
        if (this.chapters.length > 0) {
            this.init();
        }
    }

    init() {
        // Alle Inhalts-Bereiche initial ausblenden
        document.querySelectorAll('.collapsible-content').forEach((el) => {
            el.style.display = 'none';
        });

        this.bindEvents();
        this.restoreState();
    }

    bindEvents() {
        this.chapters.forEach((chapter) => {
            const header = chapter.querySelector('h2');
            if (header) {
                header.addEventListener('click', () => this.toggleChapter(chapter));
            }
        });
    }

    restoreState() {
        let expandedIds = [];
        try {
            const stored = JSON.parse(localStorage.getItem(this.STORAGE_KEY));
            if (stored && stored.expireTime > Date.now()) {
                expandedIds = stored.expandedChapters || [];
            } else {
                localStorage.removeItem(this.STORAGE_KEY);
            }
        } catch (err) {
            console.warn(
                '[ArchiveManager] Konnte Archiv-Status nicht wiederherstellen (Defektes JSON oder Storage blockiert):',
                err
            );
            try {
                localStorage.removeItem(this.STORAGE_KEY);
            } catch (_err) {
                // Ignore if storage is totally blocked
            }
        }

        // Wenn es keinen Speicherstand gibt -> Erstes Kapitel aufklappen
        if (expandedIds.length === 0) {
            const firstChapter = this.chapters[0];
            if (firstChapter) this.toggleChapter(firstChapter, true, true);
        } else {
            // Ansonsten alle vorher offenen Kapitel wiederherstellen
            expandedIds.forEach((id) => {
                const chapter = document.querySelector(`.chapter[data-ch-id='${id}']`);
                if (chapter) this.toggleChapter(chapter, true, true);
            });
        }
    }

    toggleChapter(chapter, noAnimation = false, noStore = false) {
        const content = chapter.querySelector('.collapsible-content');
        const linkContainer = chapter.querySelector('.chapter-links');
        const arrow = chapter.querySelector('.arrow-down, .arrow-left');

        if (!content || !linkContainer || !arrow) return;

        const isExpanded = content.style.display !== 'none';

        if (!isExpanded) {
            // Aufklappen
            chapter.classList.add('expanded');
            arrow.style.transition = noAnimation ? 'none' : 'transform 0.3s ease-out';
            arrow.style.transform = 'rotate(0deg)';
            content.style.display = 'block';

            // Lazy-Loading der Bilder (Nur wenn es noch nicht geladen wurde)
            if (!linkContainer.dataset.loaded) {
                linkContainer.dataset.loaded = 'true';
                linkContainer.querySelectorAll('img').forEach((img) => {
                    img.addEventListener('load', () => img.closest('a').classList.add('loaded'));
                    img.addEventListener('error', () => {
                        img.src = `${window.location.origin}/assets/images/system/placeholder.webp`;
                        img.closest('a').classList.add('loaded');
                    });
                    if (img.dataset.src) img.src = img.dataset.src;
                });
            }
        } else {
            // Zuklappen
            chapter.classList.remove('expanded');
            arrow.style.transition = noAnimation ? 'none' : 'transform 0.3s ease-out';
            arrow.style.transform = 'rotate(-90deg)';
            content.style.display = 'none';
        }

        // Status im LocalStorage sichern
        if (!noStore) this.saveState();
    }

    saveState() {
        const expanded = Array.from(document.querySelectorAll('.chapter.expanded')).map(
            (c) => c.dataset.chId
        );
        const data = {
            expireTime: Date.now() + 600000,
            expandedChapters: expanded,
        };
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(data));
        } catch (err) {
            console.error(
                '[ArchiveManager] Fehler beim Speichern im LocalStorage (Speicher voll oder blockiert?):',
                err
            );
        }
    }
}
