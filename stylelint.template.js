/**
 * Path: stylelint.config.js
 * @description Spezialisierte SCSS-Qualitätssicherung.
 */

export default {
    extends: [
        'stylelint-config-standard-scss',
        'stylelint-config-recess-order', // Erzwingt logische Reihenfolge (recess)
    ],
    plugins: ['stylelint-declaration-strict-value'],
    rules: {
        'alpha-value-notation': 'number',
        'no-empty-source': null,
        'scss/at-rule-no-unknown': true,

        // Begrenzt die Verschachtlung auf 3 Ebenen (dein Standard)
        'max-nesting-depth': [
            3,
            {
                ignorePseudoClasses: ['hover', 'focus', '&.theme-night', 'body.theme-night &'],
            },
        ],

        // Warnt vor der Nutzung von Variablen für Design-Tokens
        'scale-unlimited/declaration-strict-value': [
            ['/color/', 'font-family', 'font-size', 'font-weight', 'spacing'],
            {
                ignoreValues: [
                    '0',
                    'inherit',
                    'transparent',
                    'initial',
                    'none',
                    'currentColor',
                    'sans-serif',
                    'arial',
                    '/^\\d+(%|vw|vh)$/',
                ],
                disableFix: true,
                // HIER IST DER TRICK:
                severity: 'warning',
                message:
                    // biome-ignore lint/suspicious/noTemplateCurlyInString: Stylelint uses this as an internal placeholder
                    "Hinweis: Idealerweise nutzt du eine Variable für '${property}'. Hart-codierte Werte sind unerwünscht.",
            },
        ],

        // Erzwingt dein BEM Muster
        'selector-class-pattern': [
            '^([a-z][a-z0-9]*)(-[a-z0-9]+)*(__[a-z0-9]+(-[a-z0-9]+)*)?(--[a-z0-9]+(-[a-z0-9]+)*)?$',
            {
                message:
                    'Klassennamen müssen dem BEM-Muster entsprechen (z.B. .block__element--modifier)',
            },
        ],
    },
    ignoreFiles: [
        '**/_palette.scss', // Enthält die Definitionen der hart-codierten Werte
        'vendor/**/*.scss',
        'public/assets/**/*.css',
    ],
};
