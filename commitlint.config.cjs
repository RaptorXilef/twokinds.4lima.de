/**
 * Path: commitlint.config.cjs
 * @since 0.1.0
 * @description Erzwingt Conventional Commits 1.0.0.
 */
module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        // 0 = deaktiviert, 1 = Warnung, 2 = Fehler
        'body-max-line-length': [0, 'always'],
        'footer-max-line-length': [0, 'always'],

        'type-enum': [
            2,
            'always',
            [
                'feat', // 🚀 Features
                'fix', // 🐛 Bug Fixes
                'perf', // ⚡ Performance
                'refactor', // ⚙️ Refactoring
                'build', // 🏗️ Build System
                'ci', // 👷 CI/CD Configuration
                'style', // 💎 Styling
                'test', // 🧪 Tests
                'docs', // 📚 Dokumentation
                'chore', // 🧹 Chore / Maintenance
                'revert', // Rollbacks
            ],
        ],
        // Erlaubt Satzanfang-Großschreibung oder komplette Kleinschreibung
        // "subject-case": [2, "always", ["sentence-case", "lower-case"]],
        'subject-case': [0], // Regel deaktiviert, um Eigennamen wie ESLint/Biome zu erlauben
        'subject-empty': [2, 'never'],
        'type-empty': [2, 'never'],
    },
};
