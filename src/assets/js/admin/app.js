import { Api } from './Api.js';
import { ChapterEditor } from './ChapterEditor.js';
import { CharacterEditor } from './CharacterEditor.js';
import { ComicEditor } from './ComicEditor.js';
import { CropperManager } from './CropperManager.js';
import { DataTable } from './DataTable.js';
import { GlobalUI } from './GlobalUI.js';
import { GroupEditor } from './GroupEditor.js';
import { MassUploadManager } from './MassUploadManager.js';
import { MediaGallery } from './MediaGallery.js';
import { ModalManager } from './ModalManager.js';
import { NewsletterManager } from './NewsletterManager.js';
import { NotificationService } from './NotificationService.js';
import { ReportManager } from './ReportManager.js';
import { SystemManager } from './SystemManager.js';
import { TabManager } from './TabManager.js';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Core Services & UI initialisieren
    const api = new Api();
    const notifications = new NotificationService();
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

    // 3. Editor Module initialisieren (Jetzt MIT notifications!)
    const comicEditor = new ComicEditor(api, modalManager, notifications);
    new CharacterEditor(api, modalManager, notifications);
    new ChapterEditor(api, modalManager, notifications);
    new GroupEditor(api, notifications);

    new ReportManager(api, modalManager, comicEditor, notifications);
    new SystemManager(api, modalManager, notifications);

    // 4. Tool Module initialisieren
    new MassUploadManager(api, notifications);
    new CropperManager(api, notifications);
    new MediaGallery(api, modalManager, notifications);
    new NewsletterManager(api, notifications);

    // 5. System Logout
    document.getElementById('admin-logout-btn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        await api.post('admin_logout');
        window.location.reload();
    });

    console.info('[AdminApp] ES6 Architektur erfolgreich hochgefahren.');
});
