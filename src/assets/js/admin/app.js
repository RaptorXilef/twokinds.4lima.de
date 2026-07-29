import { Api } from './Api.js';
import { ChapterEditor } from './ChapterEditor.js';
import { CharacterEditor } from './CharacterEditor.js';
import { ComicEditor } from './ComicEditor.js';
import { GroupEditor } from './GroupEditor.js';
import { ModalManager } from './ModalManager.js';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Core Services initialisieren
    const api = new Api();
    const modalManager = new ModalManager();

    // 2. Editor Module initialisieren
    new ComicEditor(api, modalManager);
    new CharacterEditor(api, modalManager);
    new ChapterEditor(api, modalManager);
    new GroupEditor(api); // (Braucht kein modalManager)

    // Platzhalter für die letzten Module:
    // new ReportManager(api, modalManager);
    // new SystemManager(api, modalManager); // Für User & Rollen

    console.log('[AdminApp] ES6 Module erfolgreich geladen.');
});
