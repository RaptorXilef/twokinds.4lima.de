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
    const api = new FrontendApi();

    new ThemeManager();
    new ImageFallback();
    new AccordionManager();
    new AuthManager(api);
    new ReportModal(api);
    new ComicReader(api);
    new CookieConsentManager();
    new ArchiveManager();
    new BookmarksManager(api);
    new AuthForms(api);
    new ProfileManager(api);
    new CharacterFilter();
    new EmailProtector();

    console.info('[Frontend] ES6 Core Architektur erfolgreich hochgefahren.');
});
