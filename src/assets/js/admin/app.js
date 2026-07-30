import { Api } from './Api.js';
import { ChapterEditor } from './ChapterEditor.js';
import { CharacterEditor } from './CharacterEditor.js';
import { ComicEditor } from './ComicEditor.js';
import { DataTable } from './DataTable.js';
import { ErrorHandlerService } from './ErrorHandlerService.js';
import { FormService } from './FormService.js';
import { GlobalUI } from './GlobalUI.js';
import { GroupEditor } from './GroupEditor.js';
import { ModalManager } from './ModalManager.js';
import { NotificationService } from './NotificationService.js';
import { ReportManager } from './ReportManager.js';
import { SystemManager } from './SystemManager.js';
import { TabManager } from './TabManager.js';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Core Services (Sofort laden, da essenziell)
    const notifications = new NotificationService();
    new ErrorHandlerService(notifications); // Fängt globale Fehler ab

    const api = new Api();
    const formService = new FormService(api, notifications); // Der neue Form-Manager
    const modalManager = new ModalManager();

    new GlobalUI();
    new TabManager();

    // 2. Tabellen (Paginierung & Suche) initialisieren (nur für Comics, Reports verwalten sich selbst)
    new DataTable({
        tableBodySelector: '.comic-editor-table tbody',
        searchInputId: 'comic-search',
        perPageSelectId: 'comic-per-page',
        paginationContainerId: 'comic-pagination',
    });

    // 3. Main Editors (Sofort laden für cross-tab dependencies wie Report->Comic)
    const comicEditor = new ComicEditor(api, modalManager, notifications, formService);
    new CharacterEditor(api, modalManager, notifications, formService);
    new ChapterEditor(api, modalManager, notifications, formService);
    new GroupEditor(api, notifications);
    new ReportManager(api, modalManager, comicEditor, notifications);
    new SystemManager(api, modalManager, notifications);

    // 4. Lazy Loading für schwere, isolierte Module
    // Diese werden erst heruntergeladen und ausgeführt, NACHDEM das DOM voll da ist
    // und der Browser gerade "Zeit" hat (Idle). Das nennt sich "Safe Lazy-Loading".
    requestIdleCallback(
        async () => {
            try {
                // Importiert die Klassen erst jetzt dynamisch über das Netzwerk
                const { MassUploadManager } = await import('./MassUploadManager.js');
                const { CropperManager } = await import('./CropperManager.js');
                const { MediaGallery } = await import('./MediaGallery.js');
                const { NewsletterManager } = await import('./NewsletterManager.js');

                // Instanziieren, sobald der Download fertig ist
                new MassUploadManager(api, notifications);
                new CropperManager(api, notifications);
                new MediaGallery(api, modalManager, notifications);
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

    console.info('[AdminApp] ES6 Core Architektur erfolgreich hochgefahren.');
});
