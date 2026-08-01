import { ThemeManager } from '../shared/ui/ThemeManager.js';
import { FrontendApi } from './core/FrontendApi.js';
import { ArchiveManager } from './modules/ArchiveManager.js';
import { AuthForms } from './modules/AuthForms.js';
import { AuthManager } from './modules/AuthManager.js';
import { BookmarksManager } from './modules/BookmarksManager.js';
import { CharacterFilter } from './modules/CharacterFilter.js';
import { ComicReader } from './modules/ComicReader.js';
import { CookieConsentManager } from './modules/CookieConsentManager.js';
import { EmailProtector } from './modules/EmailProtector.js';
import { ProfileManager } from './modules/ProfileManager.js';
import { ReportModal } from './modules/ReportModal.js';
import { AccordionManager } from './ui/AccordionManager.js';
import { ImageFallback } from './ui/ImageFallback.js';

document.addEventListener('DOMContentLoaded', () => {
    let api;
    try {
        api = new FrontendApi();
    } catch (err) {
        console.error('[Frontend] Kritischer Fehler: API konnte nicht initialisiert werden.', err);
    }

    // Hilfsfunktion: Fängt Abstürze einzelner Module ab, ohne das ganze Frontend lahmzulegen
    const safeInit = (name, initFn) => {
        try {
            initFn();
        } catch (err) {
            console.error(`[Frontend] Fehler beim Laden des Moduls: ${name}`, err);
        }
    };

    safeInit('ThemeManager', () => new ThemeManager());
    safeInit('ImageFallback', () => new ImageFallback());
    safeInit('AccordionManager', () => new AccordionManager());
    safeInit('CookieConsentManager', () => new CookieConsentManager());
    safeInit('ArchiveManager', () => new ArchiveManager());
    safeInit('CharacterFilter', () => new CharacterFilter());
    safeInit('EmailProtector', () => new EmailProtector());

    // API-abhängige Module nur laden, wenn die API existiert
    if (api) {
        safeInit('AuthManager', () => new AuthManager(api));
        safeInit('ReportModal', () => new ReportModal(api));
        safeInit('ComicReader', () => new ComicReader(api));
        safeInit('BookmarksManager', () => new BookmarksManager(api));
        safeInit('AuthForms', () => new AuthForms(api));
        safeInit('ProfileManager', () => new ProfileManager(api));
    }

    console.info('[Frontend] ES6 Core Architektur erfolgreich hochgefahren (Maximal gehärtet).');
});
