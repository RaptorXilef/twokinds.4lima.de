import { FrontendApi } from './core/FrontendApi.js';
import { ThemeManager } from './ui/ThemeManager.js';
import { ImageFallback } from './ui/ImageFallback.js';
import { AuthManager } from './modules/AuthManager.js';
import { ReportModal } from './modules/ReportModal.js';
import { ComicReader } from './modules/ComicReader.js';
import { CookieConsentManager } from './modules/CookieConsentManager.js';

document.addEventListener('DOMContentLoaded', () => {
    const api = new FrontendApi();

    new ThemeManager();
    new ImageFallback();
    new AuthManager(api);
    new ReportModal(api);
    new ComicReader(api);
    new CookieConsentManager();

    console.info('[Frontend] ES6 Core Architektur erfolgreich hochgefahren.');
});

