import { ThemeManager } from '../shared/ui/ThemeManager.js';
import { FrontendApi } from './core/FrontendApi.js';
import { ArchiveManager } from './modules/ArchiveManager.js';
import { AuthManager } from './modules/AuthManager.js';
import { ComicReader } from './modules/ComicReader.js';
import { CookieConsentManager } from './modules/CookieConsentManager.js';
import { ReportModal } from './modules/ReportModal.js';
import { ImageFallback } from './ui/ImageFallback.js';

document.addEventListener('DOMContentLoaded', () => {
    const api = new FrontendApi();

    new ThemeManager();
    new ImageFallback();
    new AuthManager(api);
    new ReportModal(api);
    new ComicReader(api);
    new CookieConsentManager();
    new ArchiveManager();

    console.info('[Frontend] ES6 Core Architektur erfolgreich hochgefahren.');
});
