/**
 * Beschreibung: Standard ESLint Konfiguration.
 * @file eslint.config.js
 * @since 1.1.0
 * - feat(lint): Standard-JS-Konfiguration hinzugefügt.
 */

// Wenn "type": "module" in package.json, dann identisch zu .mjs
// If "type": "module" in package.json, then identical to .mjs
import config from './eslint.config.mjs';
export default config;