import { Api } from './Api.js';
import { CharacterEditor } from './CharacterEditor.js';
import { ComicEditor } from './ComicEditor.js';
import { ModalManager } from './ModalManager.js';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Core Services initialisieren
    const api = new Api();
    const modalManager = new ModalManager();

    // 2. Editor Module initialisieren und Dependencies injizieren
    new ComicEditor(api, modalManager);
    new CharacterEditor(api, modalManager);

    // Platzhalter für die nächsten Module:
    // new ChapterEditor(api, modalManager);
    // new GroupEditor(api, modalManager);
    // new ReportManager(api, modalManager);

    console.log('[AdminApp] ES6 Module erfolgreich geladen.');
});
