import { MobileMenu } from '../frontend/ui/MobileMenu.js';
import { SessionTimer } from '../shared/modules/SessionTimer.js';
import { ThemeManager } from '../shared/ui/ThemeManager.js';
import { Api } from './core/Api.js';
import { ErrorHandlerService } from './core/ErrorHandlerService.js';
import { FormService } from './core/FormService.js';
import { NotificationService } from './core/NotificationService.js';
import { UnsavedTracker } from './core/UnsavedTracker.js';
import { ChapterEditor } from './modules/ChapterEditor.js';
import { CharacterEditor } from './modules/CharacterEditor.js';
import { ComicEditor } from './modules/ComicEditor.js';
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
    // Flash-Messages prüfen und absichern
    const flash = sessionStorage.getItem('admin_flash_msg');
    if (flash) {
        try {
            const f = JSON.parse(flash);
            notifications.show(f.msg, f.type);
        } catch (err) {
            console.warn('[AdminApp] Flash-Message konnte nicht geparst werden:', err);
        }
        sessionStorage.removeItem('admin_flash_msg');
    }

    const tracker = new UnsavedTracker(); // Löst window.isDirty ab
    const api = new Api();
    const formService = new FormService(api, notifications, tracker); // Der neue Form-Manager
    const modalManager = new ModalManager();

    const globalUI = new GlobalUI(tracker);

    try {
        new MobileMenu(); // FIX: Hamburger Menü Logik laden!
        new TabManager(api);
        new ThemeManager();
        new SessionTimer(api, 'admin-session-timer', { notifications });
    } catch (err) {
        console.error('[AdminApp] Fehler bei der Core-UI Initialisierung:', err);
    }

    // Variablen für Editoren definieren, damit sie später im tabLoaded-Scope erreichbar sind
    let comicEditor = null;
    let reportManager = null;

    // 2. Main Editors (Safeguard um die globale Initialisierung)
    try {
        comicEditor = new ComicEditor(api, modalManager, notifications, formService, tracker);
        // Tracker hinzugefügt
        reportManager = new ReportManager(api, modalManager, comicEditor, notifications, tracker);
        // Tracker hinzugefügt
        new SystemManager(api, modalManager, notifications, tracker);
        new ChapterEditor(api, modalManager, notifications, formService);
        new CharacterEditor(api, modalManager, notifications, formService, tracker);
    } catch (err) {
        console.error('[AdminApp] Kritischer Fehler bei der globalen Modul-Initialisierung:', err);
        notifications.show(
            'Ein Basis-Modul konnte nicht geladen werden. Funktionen könnten eingeschränkt sein.',
            'error'
        );
    }

    // Tab-spezifische Instanz (Sortable.js braucht das DOM beim Laden)
    let groupEditor = null;

    // 3. TabLoaded Event (Feuert, wenn AJAX HTML in eine Section eingefügt hat)
    document.addEventListener('tabLoaded', async (e) => {
        const tab = e.detail.tab;
        const container = document.getElementById(tab);

        // SCHUTZ 1: Existiert der Container im DOM überhaupt noch?
        if (!container) {
            console.warn(
                `[AdminApp] DOM-Element für Tab '${tab}' nicht gefunden! Event abgebrochen.`
            );
            return;
        }

        // SCHUTZ 2: Gesamte Modul-Initialisierung absichern
        try {
            if (tab === 'section-comics') {
                new DataTable({
                    tableBodySelector: '.comic-editor-table tbody',
                    searchInputId: 'comic-search',
                    perPageSelectId: 'comic-per-page',
                    paginationContainerId: 'comic-pagination',
                });
            }

            if (tab === 'section-reports' && reportManager) {
                reportManager.initTableLogic();
            }

            if (tab === 'section-groups') {
                if (!groupEditor) {
                    const { GroupEditor } = await import('./modules/GroupEditor.js');
                    groupEditor = new GroupEditor(api, notifications, tracker);
                }
            }

            if (tab === 'section-upload') {
                const { MassUploadManager } = await import('./modules/MassUploadManager.js');
                new MassUploadManager(api, notifications, tracker);
            }

            if (tab === 'section-media') {
                const { MediaGallery } = await import('./modules/MediaGallery.js');
                new MediaGallery(api, modalManager, notifications, tracker);
            }

            if (tab === 'section-mails') {
                const { MailManager } = await import('./modules/MailManager.js');
                new MailManager(api, modalManager, notifications, tracker);
            }

            if (tab === 'section-backup') {
                const { BackupManager } = await import('./modules/BackupManager.js');
                new BackupManager(api, modalManager, notifications);
            }

            // Highlighting nach dem Rendern erneut triggern
            setTimeout(() => globalUI.handleRowHighlighting(), 100);
        } catch (err) {
            console.error(`[AdminApp] Modul für Tab '${tab}' konnte nicht geladen werden:`, err);
            notifications.show(
                'Ein Modul konnte nicht geladen werden. Prüfe deine Verbindung und lade die Seite neu.',
                'error'
            );
        }
    });

    // 4. Lazy Loading für isolierte Hintergrund-Module
    // Diese werden erst heruntergeladen und ausgeführt, NACHDEM das DOM voll da ist
    // und der Browser gerade "Zeit" hat (Idle). Das nennt sich "Safe Lazy-Loading".
    requestIdleCallback(
        async () => {
            try {
                // Importiert die Klassen erst jetzt dynamisch über das Netzwerk
                const { CropperManager } = await import('./modules/CropperManager.js');
                const { NewsletterManager } = await import('./modules/NewsletterManager.js');

                // Instanziieren, sobald der Download fertig ist
                new CropperManager(api, notifications);
                new NewsletterManager(api, notifications);
                console.info('[AdminApp] Lazy-Load Module nachträglich geladen.');
            } catch (err) {
                console.error(
                    '[AdminApp] Fehler beim verzögerten Laden der Hintergrund-Module:',
                    err
                );
            }
        },
        { timeout: 2000 }
    ); // Zwingt den Browser es spätestens nach 2s zu laden

    // 5. System Logout
    const btnLogout = document.getElementById('admin-logout-btn');
    if (btnLogout) {
        btnLogout.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await api.post('admin_logout');
                // Wir zwingen den Browser zur Login-Seite, EGAL was die API antwortet!
                window.location.href = `${api.baseUrl}/admin/login`;
            } catch (err) {
                console.error('[AdminApp] Logout fehlgeschlagen:', err);
                notifications.show('Abmeldung fehlgeschlagen. Server nicht erreichbar.', 'error');
            }
        });
    }

    console.info('[AdminApp] AJAX Architektur erfolgreich hochgefahren.');
});
