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
import { ReportManager } from './ReportManager.js';
import { SystemManager } from './SystemManager.js';
import { TabManager } from './TabManager.js';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Core Services & UI initialisieren
    const api = new Api();
    const modalManager = new ModalManager();

    new GlobalUI();
    new TabManager();

    // 2. Tabellen (Paginierung & Suche) initialisieren
    new DataTable({
        tableBodySelector: '.comic-editor-table tbody',
        searchInputId: 'comic-search',
        perPageSelectId: 'comic-per-page',
        paginationContainerId: 'comic-pagination',
    });

    // 3. Editor Module initialisieren
    new ComicEditor(api, modalManager);
    new CharacterEditor(api, modalManager);
    new ChapterEditor(api, modalManager);
    new GroupEditor(api);
    new ReportManager(api, modalManager);
    new SystemManager(api, modalManager);

    // 4. Tool Module initialisieren
    new MassUploadManager(api);
    new CropperManager(api);
    new MediaGallery(api, modalManager);
    new NewsletterManager(api);

    // 5. System Logout
    document.getElementById('admin-logout-btn')?.addEventListener('click', async (e) => {
        e.preventDefault();
        await api.post('admin_logout');
        window.location.reload();
    });

    console.info('[AdminApp] ES6 Architektur erfolgreich hochgefahren. (0% Legacy Code)');
});
