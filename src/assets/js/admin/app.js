import { Api } from './Api.js';
import { ChapterEditor } from './ChapterEditor.js';
import { CharacterEditor } from './CharacterEditor.js';
import { ComicEditor } from './ComicEditor.js';
import { GroupEditor } from './GroupEditor.js';
import { ModalManager } from './ModalManager.js';
import { ReportManager } from './ReportManager.js';
import { SystemManager } from './SystemManager.js';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Core Services initialisieren
    const api = new Api();
    const modalManager = new ModalManager();

    // 2. Editor Module initialisieren
    new ComicEditor(api, modalManager);
    new CharacterEditor(api, modalManager);
    new ChapterEditor(api, modalManager);
    new GroupEditor(api);
    new ReportManager(api, modalManager);
    new SystemManager(api, modalManager);

    console.log('[AdminApp] Alle ES6 Module erfolgreich geladen und bereit.');
});
