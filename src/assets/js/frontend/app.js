import { FrontendApi } from './core/FrontendApi.js';
import { AuthManager } from './modules/AuthManager.js';
import { ReportModal } from './modules/ReportModal.js';
import { ImageFallback } from './ui/ImageFallback.js';
import { ThemeManager } from './ui/ThemeManager.js';

document.addEventListener('DOMContentLoaded', () => {
    const api = new FrontendApi();

    new ThemeManager();
    new ImageFallback();
    new AuthManager(api);
    new ReportModal(api);

    console.info('[Frontend] ES6 Core Architektur erfolgreich hochgefahren.');
});
