import conventionalCommits from 'conventional-changelog-conventionalcommits';

/**
 * Path: changelog-config.js
 * @since 0.1.0
 * @description Zentrales ESM-Konfigurations-Modul für das Changelog-Format.
 */

const config = await conventionalCommits({
    types: [
        { type: 'feat', section: '🚀 Features' },
        { type: 'fix', section: '🐛 Bug Fixes' },
        { type: 'perf', section: '⚡ Performance' },
        { type: 'refactor', section: '⚙️ Refactoring' },
        { type: 'build', section: '🏗️ Build System' },
        { type: 'ci', section: '👷 CI/CD Configuration' },
        { type: 'style', section: '💎 Styling' },
        { type: 'test', section: '🧪 Tests' },
        { type: 'docs', section: '📚 Dokumentation' },
        { type: 'chore', section: '🧹 Chore / Maintenance' },
        { type: 'revert', section: '⏪ Reverts' },
    ],
});

const exportConfig = config.conventionalChangelog || config;

if (exportConfig.parserOpts) {
    /**
     * DER ULTIMATIVE REGEX:
     * ^[^a-zA-Z]* -> Ignoriere ALLES am Anfang, was kein Buchstabe ist (Backticks, etc.)
     * ([a-zA-Z]+)  -> Erfasse den Typ (nur Buchstaben)
     * (?:\(([^)]+)\))? -> Erfasse den Scope (alles in Klammern)
     * (!?):?       -> Optionales Breaking-Change Ausrufezeichen und der Doppelpunkt
     * [\s`]* -> Ignoriere Leerzeichen oder Backticks nach dem Doppelpunkt
     * (.*?)        -> Der eigentliche Betreff
     * [\s`]*$      -> Ignoriere Backticks oder Leerzeichen am Ende
     */
    exportConfig.parserOpts.headerPattern =
        /^[^a-zA-Z]*([a-zA-Z]+)(?:\(([^)]+)\))?!?:?[\s`]*(.*?)[`\s]*$/;
    exportConfig.parserOpts.headerCorrespondence = ['type', 'scope', 'subject'];
}

if (exportConfig.writerOpts) {
    exportConfig.writerOpts.commitGroupsSort = (a, b) => {
        const order = [
            '🚀 Features',
            '🐛 Bug Fixes',
            '⚡ Performance',
            '⚙️ Refactoring',
            '🏗️ Build System',
            '👷 CI/CD Configuration',
            '💎 Styling',
            '🧪 Tests',
            '📚 Dokumentation',
            '🧹 Chore / Maintenance',
            '⏪ Reverts',
        ];
        const idxA = order.indexOf(a.title);
        const idxB = order.indexOf(b.title);
        return (idxA > -1 ? idxA : 99) - (idxB > -1 ? idxB : 99);
    };
}

export default exportConfig;
