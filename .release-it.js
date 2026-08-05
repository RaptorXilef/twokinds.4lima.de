import conventionalCommits from 'conventional-changelog-conventionalcommits';

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

// Optionen sicher entpacken
const parserOpts = config.parserOpts || config.parser || {};
const writerOpts = config.writerOpts || config.writer || {};

// DER FIX: Wir extrahieren exakt die whatBump Funktion für das Plugin!
const whatBump = config.recommendedBumpOpts?.whatBump || config.whatBump;

// Deine Custom-Regex
parserOpts.headerPattern = /^[^a-zA-Z]*([a-zA-Z]+)(?:\(([^)]+)\))?!?:?[\s`]*(.*?)[`\s]*$/;
parserOpts.headerCorrespondence = ['type', 'scope', 'subject'];

// Deine Custom-Sortierung
writerOpts.commitGroupsSort = (a, b) => {
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

// Der <small>-Filter
if (typeof writerOpts.headerPartial === 'function') {
    const originalHeaderPartial = writerOpts.headerPartial;
    writerOpts.headerPartial = (context, options) => {
        const result = originalHeaderPartial(context, options);
        return result ? result.toString().replace(/<\/?small>/g, '') : '';
    };
}

export default {
    plugins: {
        '@release-it/conventional-changelog': {
            infile: 'CHANGELOG.md',
            parserOpts,
            writerOpts,
            // Wir übergeben die Option unter dem von Release-it geforderten Namen!
            whatBump,
        },
    },
    git: {
        // biome-ignore lint/suspicious/noTemplateCurlyInString: release-it needs this as a raw string for internal parsing
        commitMessage: 'chore(release): v${version}',
        // biome-ignore lint/suspicious/noTemplateCurlyInString: release-it needs this as a raw string for internal parsing
        tagName: 'v${version}',
        requireCleanWorkingDir: true,
    },
    github: { release: true, autoGenerate: true },
    npm: { publish: false },
};
