import { ThemeManager } from '../shared/ui/ThemeManager.js';
import { Api } from './core/Api.js';
import { ErrorHandlerService } from './core/ErrorHandlerService.js';
import { FormService } from './core/FormService.js';
import { NotificationService } from './core/NotificationService.js';
import { UnsavedTracker } from './core/UnsavedTracker.js';
import { ChapterEditor } from './modules/ChapterEditor.js';
import { CharacterEditor } from './modules/CharacterEditor.js';
import { ComicEditor } from './modules/ComicEditor.js';
import { GroupEditor } from './modules/GroupEditor.js';
import { ReportManager } from './modules/ReportManager.js';
import { SystemManager } from './modules/SystemManager.js';
import { DataTable } from './ui/DataTable.js';
import { GlobalUI } from './ui/GlobalUI.js';
import { ModalManager } from './ui/ModalManager.js';
import { TabManager } from './ui/TabManager.js';

// Polyfill für requestIdleCallback
const requestIdleCallbackPolyfill =
    window.requestIdleCallback ||
    ((cb) => {
        const start = Date.now();
        return setTimeout(() => {
            cb({
                didTimeout: false,
                timeRemaining: () => Math.max(0, 50 - (Date.now() - start)),
            });
        }, 1);
    });
window.requestIdleCallback = requestIdleCallbackPolyfill;

document.addEventListener('DOMContentLoaded', () => {
    // 1. Core Services (Sofort laden, da essenziell)
    const notifications = new NotificationService();
    new ErrorHandlerService(notifications); // Fängt globale Fehler ab

    // PERF: Fange Nachrichten auf, die den "blitzschnellen Reload" überlebt haben!
    const flash = sessionStorage.getItem('admin_flash_msg');
    if (flash) {
        try {
            const f = JSON.parse(flash);
            notifications.show(f.msg, f.type);
        } catch (_err) {} // LINTER FIX: Unused variable
        sessionStorage.removeItem('admin_flash_msg');
    }

    const tracker = new UnsavedTracker(); // Löst window.isDirty ab
    const api = new Api();
    const formService = new FormService(api, notifications, tracker); // Der neue Form-Manager
    const modalManager = new ModalManager();

    const globalUI = new GlobalUI(tracker);
    new TabManager(api); // API in den TabManager geben!

    // NEU: ThemeManager starten
    new ThemeManager();

    // 2. Main Editors (Sofort laden für cross-tab dependencies wie Report->Comic)
    const comicEditor = new ComicEditor(api, modalManager, notifications, formService, tracker);
    new CharacterEditor(api, modalManager, notifications, formService, tracker);
    new ChapterEditor(api, modalManager, notifications, formService);
    new GroupEditor(api, notifications, tracker);
    const reportManager = new ReportManager(api, modalManager, comicEditor, notifications);
    new SystemManager(api, modalManager, notifications);

    // Initialer Check für statische Tabs (wie Charaktere)
    setTimeout(() => globalUI.handleRowHighlighting(), 300);

    // Re-Initialize Module, wenn ihr HTML-Inhalt nachträglich via AJAX reingeladen wurde
    // Wird abgefeuert, sobald ein Lazy-Tab fertig geladen wurde
    document.addEventListener('tabLoaded', (e) => {
        if (e.detail.tab === 'section-comics') {
            // 3. Tabellen (Paginierung & Suche) initialisieren (nur für Comics, Reports verwalten sich selbst)
            new DataTable({
                tableBodySelector: '.comic-editor-table tbody',
                searchInputId: 'comic-search',
                perPageSelectId: 'comic-per-page',
                paginationContainerId: 'comic-pagination',
            });
        }
        if (e.detail.tab === 'section-reports') {
            reportManager.initTableLogic();
        }

        // Kurz warten bis der Browser die Tabelle gezeichnet hat, DANN scrollen
        setTimeout(() => globalUI.handleRowHighlighting(), 100);
    });

    // 4. Lazy Loading für schwere, isolierte Module
    // Diese werden erst heruntergeladen und ausgeführt, NACHDEM das DOM voll da ist
    // und der Browser gerade "Zeit" hat (Idle). Das nennt sich "Safe Lazy-Loading".
    requestIdleCallback(
        async () => {
            try {
                // Importiert die Klassen erst jetzt dynamisch über das Netzwerk
                const { MassUploadManager } = await import('./modules/MassUploadManager.js');
                const { CropperManager } = await import('./modules/CropperManager.js');
                const { MediaGallery } = await import('./modules/MediaGallery.js');
                const { NewsletterManager } = await import('./modules/NewsletterManager.js');

                // Instanziieren, sobald der Download fertig ist
                new MassUploadManager(api, notifications, tracker);
                new CropperManager(api, notifications);
                new MediaGallery(api, modalManager, notifications, tracker);
                new NewsletterManager(api, notifications);

                console.info('[AdminApp] Lazy-Load Module nachträglich geladen.');
            } catch (e) {
                console.error('[AdminApp] Fehler beim Lazy Loading:', e);
            }
        },
        { timeout: 2000 }
    ); // Zwingt den Browser es spätestens nach 2s zu laden

    // 5. System Logout
    document.getElementById('admin-logout-btn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        await api.post('admin_logout');
        window.location.reload();
    });

    console.info('[AdminApp] Core Architektur erfolgreich hochgefahren.');
});
