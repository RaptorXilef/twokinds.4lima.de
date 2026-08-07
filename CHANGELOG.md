# Changelog

## [6.14.1](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.14.0...v6.14.1) (2026-08-07)

### 🐛 Bug Fixes

* **infrastructure:** handle NULL values for strict types in Hydrator ([fa896b4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/fa896b4508c6fffb7d6f6647fecf138a824a95d7))

### ⚙️ Refactoring

* **global:** apply automated Rector code quality fixes ([a6a78ff](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a6a78ffe22120de0d7eb510a63e7e4e95d2b0312))
* **infrastructure:** apply Hydrator and Table constants to all remaining repositories ([675b90d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/675b90d954a510aabb2300578e94298e6e54f4c1))
* **infrastructure:** introduce DataMapper and Table constants ([871a6df](https://github.com/RaptorXilef/twokinds.4lima.de/commit/871a6df16bd63b04327aead113b143f94c882eb2))
* **infrastructure:** upgrade Hydrator and apply to more repositories ([a995a21](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a995a21a31219fa4e8875fa7ccb6c3b7af3d373a))

### 💎 Styling

* **code-style:** apply PHP CS Fixer formatting rules ([e4f77fd](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e4f77fd97c89ee5e94c4abed164fab7f3c2688e1))

## [6.14.0](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.13.5...v6.14.0) (2026-08-06)

### 🚀 Features

* **infrastructure:** add delete method to mail queue repository ([4e0d682](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4e0d682686372d101cb98708ae07267508cc5c46))

### 🐛 Bug Fixes

* **application:** resolve empty ajax tabs in admin dashboard ([e9d81e2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e9d81e28ef4685d53c8ef7ca816e49e92f02bb69))
* **config:** correct phpstan disallowed-calls path matching ([605ce01](https://github.com/RaptorXilef/twokinds.4lima.de/commit/605ce01f4d3b225b8ddab9a03fc4c7a7d556c53f))
* **core:** fix phpstan windows paths ([6a4d076](https://github.com/RaptorXilef/twokinds.4lima.de/commit/6a4d07600acb405c8fae462033419601525f3f4c))
* **infrastructure:** resolve Deptrac violation in GdMediaService ([d819a72](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d819a72349b519276617e61586e38b22cb329f6f))

### ⚙️ Refactoring

* **application:** establish strict HTTP response layer and eliminate superglobals ([c7f9316](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c7f93160668e0386923e18c5555cef1bc23eb570))
* **application:** fix direct HTTP calls and time functions ([99d38ff](https://github.com/RaptorXilef/twokinds.4lima.de/commit/99d38fff42212d1c127adfd98fd30bc120f7a396))
* **application:** remove PDO dependency from Actions ([487deb6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/487deb6267ddff649e8f4e172c0924eac303ae79))
* **architecture:** isolate routing cache, analytics and crop I/O ([9b2401a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9b2401a3fa989e8b37a0261182631444b02f369c))
* **core/application:** remove time() calls and fix session double-start ([6ee2547](https://github.com/RaptorXilef/twokinds.4lima.de/commit/6ee25471c945a04eb19d23a59c9e75be970aa8f1))

### 🏗️ Build System

* **phpstan:** fix path matching and add advanced architecture rules ([6c0b0cb](https://github.com/RaptorXilef/twokinds.4lima.de/commit/6c0b0cbb2ea6eab6bad3236818c42f82a6e5b69c))
* **qa:** refine phpstan architecture rules and clean up final view controllers ([31426ed](https://github.com/RaptorXilef/twokinds.4lima.de/commit/31426eda32b46450b0223d4c7840a416d4c22950))

## [6.13.5](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.13.4...v6.13.5) (2026-08-06)

### 🐛 Bug Fixes

* **qa:** make missing exclude path optional in phpstan config ([2d34705](https://github.com/RaptorXilef/twokinds.4lima.de/commit/2d34705ff53fd2066f6718224a7d74c5c0b8d984))

### ⚙️ Refactoring

* **application:** isolate remaining I/O and GD operations into infrastructure ([5c3cc92](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5c3cc9247c6e3f4df5cd38ec5142c66faf68861f))

## [6.13.4](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.13.3...v6.13.4) (2026-08-06)

### 🏗️ Build System

* **qa:** integrate phpstan-disallowed-calls to enforce architecture ([bf89b14](https://github.com/RaptorXilef/twokinds.4lima.de/commit/bf89b14401e7f78a8783ba3ae4b8f292a24d5530))

## [6.13.3](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.13.2...v6.13.3) (2026-08-06)

### 🐛 Bug Fixes

* /refactor(architecture): fix deptrac exceptions & extract file deletion ([2d60b56](https://github.com/RaptorXilef/twokinds.4lima.de/commit/2d60b56d9f41ea503042a79c883bc6f7970f3ba9))

### ⚙️ Refactoring

* **application:** abstract character media and mass upload processing ([54e105b](https://github.com/RaptorXilef/twokinds.4lima.de/commit/54e105b080116afc2beb56f03bd4f2a8614de2e2))
* **application:** abstract comic media upload and file handling ([65ef739](https://github.com/RaptorXilef/twokinds.4lima.de/commit/65ef739d480b7cdc530d7b11b163a71ed764c73c))
* **application:** decouple infrastructure logic from comic controllers ([7b1d925](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7b1d92520fb4c80c49b3b77421ae34f43fa35e8f))

## [6.13.2](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.13.1...v6.13.2) (2026-08-06)

### ⚙️ Refactoring

* **admin/modals:** split monolithic modals file into manageable partials ([7c2870d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7c2870dd996055645e5f09b8e41927916e50aecf))
* **templates:** structure views into pages, partials and layouts ([c802040](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c802040c713968ccb72a5a3fe662d8506b803eaa))

### 💎 Styling

* **templates:** fix refine UI directory structure ([1c7609a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/1c7609a06392980436797dd9bfc82fa0cd00659a))

## [6.13.1](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.13.0...v6.13.1) (2026-08-06)

### ⚙️ Refactoring

* **mail:** resolve DDD architecture violation in SendQueuedMailAction ([311373f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/311373f429b81bddda4e39cf361a1a593e8c6a10))

## [6.13.0](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.12.0...v6.13.0) (2026-08-06)

### 🚀 Features

* **backup:** implement AES-256 ZIP encryption, FTP off-site upload, and retention policies ([b98453b](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b98453b8e56771b48b7235253052d99b2a20f92d))
* **config:** add backup configuration block for retention, encryption and off-site FTP ([a7970ab](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a7970ab4fb3616a5c5d7775cae5bd16620bf95aa))

### 🐛 Bug Fixes

* **backup:** increase FTP connection timeout for sleeping NAS drives ([e9a14d3](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e9a14d3c2c3bd4e5dd6995910e3d4cc65bab099c))
* **backup:** resolve PDO clone error and improve restore modal UX ([9636f24](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9636f24092ade4be289e8376540f1ed91dee6eb0))
* **backup:** restore missing getPrimaryKeys method in SystemBackupService ([8160e32](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8160e324b5ad8f9ecfba0f65b3c548e4eb34dc42))
* **core:** resolve PHP 8 deprecation error regarding optional parameter order in ComicPage ([98289c4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/98289c4319bca89139a86800a6486f32080a7e53))
* **database:** update SchemaRegistry to include latest columns ([e4faf02](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e4faf0273867b546d77ae7d32dbd709a8d876574))
* **di:** provide complete InfrastructureServiceProvider to resolve BackupServiceInterface missing binding ([ef129b5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ef129b5c39cbe5e0dcf46ec89ceb588b99d5e3a8))

### ⚙️ Refactoring

* **backup:** move backup service to infrastructure and support legacy passwords ([4deef99](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4deef9942650ff1b894fde8befa0622f56f4f6d2))
* **comics:** rename helper_ids to user_ids for ubiquitous language consistency ([247339c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/247339c127e0c024cbc9dda2faab0e47bbc418a0))

### 📚 Dokumentation

* **backup:** add comprehensive documentation for the backup system ([ce736f6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ce736f6033a366c892b586e84ce9248aa8cd510b))

## [6.12.0](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.11.0...v6.12.0) (2026-08-06)

### 🚀 Features

* **admin:** add mail management templates and preview modal ([fe6026b](https://github.com/RaptorXilef/twokinds.4lima.de/commit/fe6026b04c3f06e30e008c4c1fd3bd4e2ef53a12))
* **admin:** add mail queue and log repositories, prepare preview API ([9c08a9f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9c08a9f346cd92a73c013fe7cc6c7087df207879))
* **admin:** implement mail manager js and API actions for sending/requeuing ([5254a9e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5254a9e58489b8f21183e0359153fc3d1b9e612f))

### 🐛 Bug Fixes

* **admin:** add missing mails section container to dashboard ([a816428](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a816428d41f9220286dd1a4567012d15517ed418))
* **admin:** add tab switching logic to MailManager ([4ffeb57](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4ffeb574bcabc6a3d875f175e55473da6f5fc74d))
* **admin:** generate fresh tokens on email requeue and prevent srcdoc map errors ([af5c96f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/af5c96f719e2c30ef785cee1b0c0fdafc25907a9))
* **admin:** resolve iframe quirks mode and silent mail queue logic error ([0f444b3](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0f444b3d8a33aa70e98b73ae2cd90a456162aa5d))
* **build:** correct sourcemap paths in css minifier script ([1899099](https://github.com/RaptorXilef/twokinds.4lima.de/commit/1899099b68beec563d4e00d42c1ad1d52bbf604a))

## [6.11.0](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.10.0...v6.11.0) (2026-08-05)

### 🚀 Features

* **admin:** display immutable user ID in report detail modal ([74b2dd5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/74b2dd56b9690bf34b30ed4d76e637c6303d010c))
* **reports:** streamline guest reporting and display credit preferences in admin modal ([fd69f3f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/fd69f3fca95410e50ece5b43bc7929d3ed658a3e))

### 🐛 Bug Fixes

* **admin:** resolve report modal layout issues and enforce report anonymization on user deletion ([baa7007](https://github.com/RaptorXilef/twokinds.4lima.de/commit/baa7007ff8d196a635035167343f3f17317176be))

### 💎 Styling

* **admin:** refactor templates to replace inline CSS with semantic SCSS utility classes ([c19a1e3](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c19a1e3df724489e997f27fd0fde8832ba2c23a2))

## [6.10.0](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.9.0...v6.10.0) (2026-08-05)

### 🚀 Features

* **admin:** integrate user avatars into reports and automate comic helper assignments ([3a7d41c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3a7d41c6da2f0c56417b430ea7fd074734200bea))
* **community:** implement user avatars, extended profiles, and helper references ([22d4e00](https://github.com/RaptorXilef/twokinds.4lima.de/commit/22d4e00fac8f4f4c84d6b202fdaaa53e70c4d6ae))
* **frontend:** implement public contributor profiles and comic page attributions ([5cedeae](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5cedeaee6992ea6c477f274c214f6d9410d5023f))

### 🐛 Bug Fixes

* **frontend:** resolve layout displacement, bio indentation, and textarea dimensions ([8d50816](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8d50816210b6e4981ddd9938cfc140d4e9298a5f))
* **profile:** implement robust drag-and-drop avatar upload zone ([d067a60](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d067a60717a99819b0b1716edd0779ba2f3ed45a))
* **ui/security:** resolve Cropper CSP block, repair report table layout, and fix comic helper assignment ([0fd5245](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0fd5245c149456251601dc8c21755c798e4dfbe2))

### ⚙️ Refactoring

* **frontend:** decouple user profiles from username and introduce immutable routing ([27c9304](https://github.com/RaptorXilef/twokinds.4lima.de/commit/27c9304810ed5135e6c089709d8158c15b086417))

### 💎 Styling

* **frontend:** decouple inline CSS from templates and unify contributor UI design ([3b35122](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3b35122ef4be260ba9c0bc9f9f4eaea297b62c07))
* **frontend:** resolve docblock type hinting warnings and align controller interfaces ([9d95fd9](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9d95fd9f12777e480401d686585b8492da3e7df1))
* **ui:** redesign report modal layout and fix avatar upload HTTP 500 errors ([fdbe3e7](https://github.com/RaptorXilef/twokinds.4lima.de/commit/fdbe3e7bcea6a181162082c16d1fb96ed85012b6))

## [6.9.0](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.8.0...v6.9.0) (2026-08-05)

### 🚀 Features

* **backup:** enforce automatic safety backups prior to database restoration ([c73dc2a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c73dc2a5b8ffc23cf663739ef61a8f088b856ae4))

### 🐛 Bug Fixes

* **routing:** restore missing Route attribute and refresh composer autoload map ([a2af0c2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a2af0c2aaf5fa89cca8ec8c3fc14f626eba45396))

### ⚙️ Refactoring

* **architecture:** reorganize action controllers into domain-specific modules ([b424352](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b424352bf711096489834ec0ddd8187f0ba37511))
* **routing:** implement domain-driven attribute routing with dynamic parameters ([cc13323](https://github.com/RaptorXilef/twokinds.4lima.de/commit/cc1332358c5f58daa1c90a067fea450e4f424c7c))
* **routing:** implement dynamic API routing and eliminate DRY violations ([d9deba1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d9deba1e75d93f938873bf590933d0e8f36c387c))

### 💎 Styling

* **admin:** fix BiomeJS linting warnings in BackupManager and cleanup obsolete router files ([e3a7627](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e3a76275436ae6f25f3c9c9c0199611609626b71))

## [6.8.0](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.7.2...v6.8.0) (2026-08-05)

### 🚀 Features

* **backup:** implement automated database backup and migration system ([91932dd](https://github.com/RaptorXilef/twokinds.4lima.de/commit/91932dd87705f7dcd077c404d8079483bc91349d))

## [6.7.2](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.7.1...v6.7.2) (2026-08-05)

### 🐛 Bug Fixes

* **stylelint:** resolve all SCSS errors and formatting violations ([51bde71](https://github.com/RaptorXilef/twokinds.4lima.de/commit/51bde7183dbff9d125ed6ed5a9f4810a69c55438))

### ⚙️ Refactoring

* **rector:** modernize code base and enforce PHP 8.2+ type safety ([142f537](https://github.com/RaptorXilef/twokinds.4lima.de/commit/142f537a84977dff7499d61fd30fc042c343bfec))
* **style:** replace string concatenation with template literals ([16574cb](https://github.com/RaptorXilef/twokinds.4lima.de/commit/16574cb5b56d8c818e4cfc59384ab673ded1283e))

### 💎 Styling

* **parser:** prefix unused regex callback parameter to satisfy linter ([5f008a5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5f008a5f292aa3d38728dcaf9c39604823706778))
* **php-cs-fixer:** apply code style fixes across PHP files ([7f067e7](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7f067e717707c09fbb3ba91133c476081271ab02))

## [6.7.1](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v6.7.0...v6.7.1) (2026-08-04)

### 🐛 Bug Fixes

* enforce strict kebab-case for character assets to prevent linux deployment errors ([de6c06e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/de6c06e092a8d30ab7e86cbc05b7a64ea8eb2513))
* **parser:** preserve whitespace after CSS functions and percentages ([52b7d06](https://github.com/RaptorXilef/twokinds.4lima.de/commit/52b7d063f2d8d63deb2710f36c049b43d1f64a37))
* resolve lingering path and syntax errors from asset restructuring ([52868a3](https://github.com/RaptorXilef/twokinds.4lima.de/commit/52868a3a1f66fb2ea2e4780261c45359293fa3d5))
* session timer throws type error on frontend expiration ([f138672](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f1386729b641ec07a8be357f349a3fb84e2a2d2c))

### ⚙️ Refactoring

* restructure asset tree and implement auto-slugging for uploads ([0b94619](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0b94619119797d37d315d472a45886c3767fc22d))

### 🏗️ Build System

* **deps:** bump undici in the npm_and_yarn group across 1 directory ([6cf721f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/6cf721f960b979808ff49235c65f90ea4243198b))
* migrate release-it pipeline to ES modules and update changelog generator dependencies ([d32b13c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d32b13c04711af774469b4d2c6c5c06699b1b99c))

## [6.7.0](https://github.com///compare/v6.6.0...v6.7.0) (2026-08-04)

### 🚀 Features

* implement maintenance mode flags and admin dev mode bypass ([b7551ae](https://github.com///commit/b7551aefeba879f56906788104e36f37519d8511))

### 🐛 Bug Fixes

* correct maintenance mode routing logic and logo path ([b2c00b1](https://github.com///commit/b2c00b12d6440c736694c893863ae5cce0451536))
* correctly route api calls during maintenance mode and return valid JSON ([c07fc7b](https://github.com///commit/c07fc7ba6f03507276f3ef33786ad0815ad89d06))
* resolve linter warnings and implement missing RBAC authorization checks ([0e1727e](https://github.com///commit/0e1727ee592dd31ce5502f98efe8964a53894267))
* secure administrative read-only API endpoints with RBAC checks ([5be2607](https://github.com///commit/5be260761191d72abb8a48a4334964de8843b280))

### ⚙️ Refactoring

* clean up duplicate logic, unclosed php tags, and dead DOM references ([ebf5965](https://github.com///commit/ebf5965a807c99da72fea98e8a13d906f8819219))

## [6.6.0](https://github.com///compare/v6.5.8...v6.6.0) (2026-08-03)

### 🚀 Features

* add password confirmation fields for registration and admin user creation ([3b200ea](https://github.com///commit/3b200ea870768cd97eee3b488e9dc006974902b9))
* **Admin-UI:** Implement auto-renewing session timer with activity tracking ([956aea4](https://github.com///commit/956aea4a22eb79011ea5e750abfa8a0f546046d4))
* **admin/groups:** introduce pulsating sticky save button and fix modal event conflicts ([71fbedc](https://github.com///commit/71fbedca983cfaaf1f8c3de88e9256fbae740e51))
* **core/assets:** implement dynamic ES6 Import Map to fix sub-module browser caching ([a826b59](https://github.com///commit/a826b594b6179a18b6bf4c9faf2e02982dcafddd))
* frontend and admin module unification, bugfixes, and account deletion ([372d609](https://github.com///commit/372d609a38244e0b0340b590e95d7e2565c36ff6))
* frontend UX improvements and critical admin security fix ([9b29f7a](https://github.com///commit/9b29f7a5cc119ac70aa8010232b95b27b216eb7b))
* **frontend/profile:** implement secure email change workflow ([97cede9](https://github.com///commit/97cede90e85a3dfefd46a5ca69fbee2570cbdb3f))

### 🐛 Bug Fixes

* **admin/core:** resolve rogue beforeunload unsaved changes prompt on entity creation ([a5bdf7b](https://github.com///commit/a5bdf7b9db9764b453914eae7680adb2f954b7f2))
* **admin/users:** fix 400 bad request on new user creation ([0f04df0](https://github.com///commit/0f04df07e9f0f149fb224c98593bd0b8f3beb79c))
* **core/assets:** resolve aggressive browser caching for CSS and JS assets ([385a55e](https://github.com///commit/385a55e0eeeba7ac47410c54e829ebdb962a051d))
* frontend session timer not counting down ([87d5f1d](https://github.com///commit/87d5f1da5f4f7a70fc573f9c2caa3da9d5a172c2))
* **frontend/bookmarks:** resolve missing sync methods causing JS exception and broken layout ([338d080](https://github.com///commit/338d080749868ba8b33cfa20cebc94c0fa9de114))
* **frontend/profile:** resolve JS scope reference error during account deletion ([069d1f6](https://github.com///commit/069d1f690b8dbac938e7137060ca66cdd594b79b))

### ⚙️ Refactoring

* **Architecture:** Extract header components and refine session timer ([0f88f0b](https://github.com///commit/0f88f0b92f4e26b5473ba6fb4be7a7d171e1a790))

## [6.5.8](https://github.com///compare/v6.5.7...v6.5.8) (2026-08-03)

### 🚀 Features

* **Admin-UI:** Replace DOM scraping with robust API fetch for transcript transfer ([61b14fa](https://github.com///commit/61b14fad784e4c5494f3f41e3e784660c2b6852e))
* **Frontend-UI:** Add GitHub and TwoKinds-Original to social menu ([bd866b3](https://github.com///commit/bd866b32bcb0f9629ab7617dee9eb9f72dd16cd3))
* **Frontend-UI:** Implement interactive character easter egg in footer ([af6db4f](https://github.com///commit/af6db4f7237db7a716bda9faf46457ac69975c44))
* **Frontend-UI:** Upgrade footer easter egg to dynamic crowd effect ([9862546](https://github.com///commit/9862546ec3753598040d3c2ad7a93a58c02aadb0))
* **Frontend:** Implement dynamic social menu and fix imprint email button ([1e5288b](https://github.com///commit/1e5288b0ec4da1278b6934f7ce42d4255a52a3c5))
* **Frontend:** Implement universal RSS clipboard copying and fix layout bugs ([c86cb08](https://github.com///commit/c86cb08ffd9711bc37076601f32bf04fdfb7cfab))

### 🐛 Bug Fixes

* **Admin-UI:** Resolve report transcript transfer bug and modal styling ([678b9ab](https://github.com///commit/678b9abaff949bc21654818c6f121f9604834787))
* **Frontend-UI:** Restore consistent 4-icon social menu layout ([3b5cf57](https://github.com///commit/3b5cf57e9dd728941c0c4d29fad72c966a2c26f2))
* **Frontend-UI:** Restore missing bookmark card layout and hover animations ([72184d2](https://github.com///commit/72184d236c340cd6f65a3a6e5f5b62bb7f666202))
* **Frontend:** Refine social icons sizing and text alignments ([e731229](https://github.com///commit/e731229f2211a9cbbc2b2a61d83925a337753b70))

### ⚙️ Refactoring

* **Frontend-UI:** Extract social menu styles and refine brand colors ([ea66273](https://github.com///commit/ea662733c7a39ab0b8bf8cdecfa3286ad473546d))
* **Frontend-UI:** Modernize social menu icons with FontAwesome ([3b9f9a8](https://github.com///commit/3b9f9a8e7d832af368936b2093d69b4157f730d5))
* **Frontend-UI:** Refine footer character animation and extract cookie banner ([ed5a613](https://github.com///commit/ed5a613f7a9082a2de386d57013f8c1e2fac7547))
* **Git:** Restructure and clean up .gitignore for improved readability ([aebb0f1](https://github.com///commit/aebb0f1fff3ed2ef7b7e5979648dfc7303fde542))

### 💎 Styling

* **Frontend-UI:** Optimize footer character assets and hover scaling ([8a01054](https://github.com///commit/8a0105440c022d497240530c70e04442fe61e097))

### 🧹 Chore / Maintenance

* **Git:** Update .gitignore rules for assets and remove legacy backup ([b8a07ae](https://github.com///commit/b8a07ae0709c8744a21229d53c04fdcf6d0bf6b8))

## [6.5.7](https://github.com///compare/v6.5.6...v6.5.7) (2026-08-03)

### 🐛 Bug Fixes

* **Frontend-UI:** Restore line-height and text alignment in legal/info pages ([4dd1007](https://github.com///commit/4dd1007a8f8932e51774b7f34555b8e74a6b4188))
* **Frontend-UI:** Restore line-heights and text alignments in legal pages ([2b35c57](https://github.com///commit/2b35c572336b5c8191345e167a4d72ff532588c4))
* **Frontend-UI:** Restore missing layout constraints and padding sizes ([f21c7f9](https://github.com///commit/f21c7f9e765628f26273f28ab437e163c70d7c6b))

### ⚙️ Refactoring

* **Frontend:** Finalize inline-style purging for content views ([4f3a0f6](https://github.com///commit/4f3a0f60d618c9e311db3adb55be48c8ec46e114))

## [6.5.6](https://github.com///compare/v6.5.5...v6.5.6) (2026-08-02)

### 🐛 Bug Fixes

* **Admin-UI:** Resolve squished date columns and refactor table widths ([acb58dc](https://github.com///commit/acb58dcd74da608b718c5f699b2aa0a22306cc7b))
* **Admin:** Resolve critical post-refactoring bugs ([7eadff3](https://github.com///commit/7eadff30d27a709d042edbb31edc040d244ab026))
* **Admin:** Resolve Cropper.js initialization error ([94c1924](https://github.com///commit/94c192481f19ca7d956593dada41f8418be7b3dd))
* **parser:** implement string-literal protection and prevent PHP short-tag corruption ([f3a9cdc](https://github.com///commit/f3a9cdc196b4ed0212d75fb30efea70854cf3fee))
* **parser:** prevent CSS ID destruction and preserve PHP closing tags ([4ca4801](https://github.com///commit/4ca4801319e9a81ecbda7804632040e649bb7391)), closes [#element-id](https://github.com///issues/element-id) [#meine-id](https://github.com///issues/meine-id)
* **parser:** resolve malformed HTML comments and refine token optimization ([beb551c](https://github.com///commit/beb551c8aac4248fa77bdf137f0398f788366bef))
* **parser:** scope operator padding to PHP tags and implement block protection ([8a07488](https://github.com///commit/8a07488c60ebdb37896076852105c366a2876625))

### ⚙️ Refactoring

* **Admin-UI:** Complete inline-style removal for user and role management ([fea7adc](https://github.com///commit/fea7adc007e014b87ee061fe75ea5affb1b7e5a5))
* **Admin-UI:** Eradicate inline styles from modals and establish SCSS utilities ([de97d88](https://github.com///commit/de97d88285236ca6fe9d2faba16a54ffc9f82be7))
* **Admin-UI:** Fix media grid layout and extract admin footer component ([eac4209](https://github.com///commit/eac42098f439f4e1efcffc20e3e9bbf5273c9947))
* **Admin-UI:** Purge final inline-styles and implement Admin footer ([fcd106a](https://github.com///commit/fcd106a9be5842fe81517bf150e47984f6127e98))
* **Admin-UI:** Purge inline styles from remaining dashboard sections ([887428f](https://github.com///commit/887428f325ef43bfeb9dfa27ede0602eb7ae8dd7))
* **Frontend:** Standardize authentication and profile views ([ecc795a](https://github.com///commit/ecc795aa8355a61dba0db284533d7c32211c973d))
* **SCSS:** Implement semantic CSS Custom Properties ([e2349e0](https://github.com///commit/e2349e0e17478db850d3639e8672732b010dc0b2))

### 🏗️ Build System

* **scss:** integrate Prettier for SCSS formatting alongside Biome and Stylelint ([14a7076](https://github.com///commit/14a70760abf0e0f136abf6dbec0f449bb1b38f42))

## [6.5.5](https://github.com///compare/v6.5.4...v6.5.5) (2026-08-01)

### 🐛 Bug Fixes

* **frontend:** handle full absolute URLs for external comic assets to prevent broken links ([e0adaf5](https://github.com///commit/e0adaf5dd85f4bebffb12ab973ea8cb360271fc3))
* **ui:** unify modal layouts across frontend and admin areas ([dc3bdff](https://github.com///commit/dc3bdff545c4f957b48996b2989b51f4925deaec))

### 🧹 Chore / Maintenance

* **deps:** bump external CDN libraries to latest versions to resolve console warnings ([befd980](https://github.com///commit/befd9806bc7374f1bc77fa412e1f7c3d94be2127))

## [6.5.4](https://github.com///compare/v6.5.3...v6.5.4) (2026-08-01)

### 🐛 Bug Fixes

* **admin:** guard JSON parsing of entity payloads on edit button click handlers ([f2af276](https://github.com///commit/f2af276fa21c8619760bd39a7b3f1e4316fbd6b0))

### ⚙️ Refactoring

* **admin:** harden local storage access and global UI initialization ([1a55a66](https://github.com///commit/1a55a669ed3dfa6daccbf588aa76a68e4e5cf140))
* **frontend:** implement Biome-compliant error logging for network and storage operations ([e5d8414](https://github.com///commit/e5d84143cdb8b1f0fc47af77fb2a8395d2515179))

### 💎 Styling

* resolve all remaining Biome linter warnings across frontend and admin JS ([5075026](https://github.com///commit/50750265e09ac39279d600c06ba23d1514a1e313))

## [6.5.3](https://github.com///compare/v6.5.2...v6.5.3) (2026-08-01)

### 🐛 Bug Fixes

* **frontend:** harden local storage access and optimize filter performance ([661fc1b](https://github.com///commit/661fc1bb04616d75f42be29c45c387a0573871ef))

### ⚙️ Refactoring

* **frontend:** implement robust error boundaries and Biome-compliant debug logging ([8649074](https://github.com///commit/86490746893721ffc8487084940f9da11cb61be4))

## [6.5.2](https://github.com///compare/v6.5.1...v6.5.2) (2026-08-01)

### 🐛 Bug Fixes

* **admin:** resolve ghost draft issue causing repetitive unsaved changes prompts ([e21ce95](https://github.com///commit/e21ce95300dd0ed3b09e988dc9eb97743f669b34))
* **admin:** scope auto-save drafts to specific entities to prevent cross-contamination ([44f6234](https://github.com///commit/44f6234dd34de85a8232a03fa8bdb4a932041829))

### ⚙️ Refactoring

* **admin:** enforce 100% event delegation for core editors to maximize robustness ([f87dc26](https://github.com///commit/f87dc26918fdece620eab1067d993cba9ce339ef))
* **admin:** harden app.js entry point against network latencies and missing DOM nodes ([b11f9f2](https://github.com///commit/b11f9f25e7f130524cfadf4270d0ebb13842b142))
* **admin:** standardize event delegation across all remaining admin modules ([40e9785](https://github.com///commit/40e9785652d57d5e2735d213cd1ad39e5a76e66a))

## [6.5.1](https://github.com///compare/v6.5.0...v6.5.1) (2026-08-01)

### 🐛 Bug Fixes

* **admin:** remove duplicate section wrapper in comics tab to fix visibility issue ([04d8492](https://github.com///commit/04d849210582c349e2d5e6498d33e3dc71543b92))

### ⚙️ Refactoring

* **admin:** extract duplicated pagination logic into standalone UI module ([1eda6f3](https://github.com///commit/1eda6f3ba51dab771a34c67c33413770ddd1a6b3))
* **admin:** migrate all dashboard tabs to 100% AJAX lazy-loading architecture ([7c79f02](https://github.com///commit/7c79f021e2bdd9a30b2d13ff5748558f83eb31d7))

## [6.5.0](https://github.com///compare/v6.4.13...v6.5.0) (2026-07-31)

### 🚀 Features

* **admin:** enhance table pagination with jump-to input and fast-travel buttons ([4c4bc26](https://github.com///commit/4c4bc2621c9cce836f6a50df5fda40f7cf9ecc5a))

## [6.4.13](https://github.com///compare/v6.4.12...v6.4.13) (2026-07-31)

### 🐛 Bug Fixes

* **admin:** change transcript diffing from line-by-line to word-by-word ([c455bf9](https://github.com///commit/c455bf9f3fdab2adac7db82b007cbeba1879f5df))
* **admin:** enable close button functionality in report modal ([63de34a](https://github.com///commit/63de34a9dfbe217bd850ec4dd02cc78a1869cd25))

## [6.4.12](https://github.com///compare/v6.4.11...v6.4.12) (2026-07-31)

### 🐛 Bug Fixes

* **admin/js:** resolve empty tabs issue and bind dynamic AJAX events correctly ([634d8e3](https://github.com///commit/634d8e31d0e05df1794ec9d5eeb0a9afc11a0ae0))
* **admin:** remove redundant section tags hiding AJAX tab content ([33d53dd](https://github.com///commit/33d53dd80f94ab6816058c33516e61c5043bd2c3))
* **admin:** resolve ajax 500 error and prepare email protection config ([7c3c591](https://github.com///commit/7c3c59141245adc40d8a0ead2382760939319152))

### ⚙️ Refactoring

* **frontend/js:** eliminate final inline scripts for accordion and email protection ([14263a2](https://github.com///commit/14263a27db66fe9d0d97ddd246656e7162864439))

## [6.4.11](https://github.com///compare/v6.4.10...v6.4.11) (2026-07-31)

### ⚙️ Refactoring

* **frontend/js:** centralize auth forms logic into ES6 AuthForms module ([7307d8c](https://github.com///commit/7307d8ccaff3bf5f2c5d52c061f54ffa993fcc28))
* **frontend/js:** extract bookmarks inline logic to ES6 BookmarksManager ([9ca03ac](https://github.com///commit/9ca03acd1316ddba0c382a297ed76edc6f62eed6))
* **frontend/js:** modularize profile management and character filtering ([8f7d0d1](https://github.com///commit/8f7d0d1e59ade2e0262e7dc6ef38a5a41b2867a5))

## [6.4.10](https://github.com///compare/v6.4.9...v6.4.10) (2026-07-30)

### ⚙️ Refactoring

* **frontend/js:** dismantle legacy common.js into modular ES6 architecture ([7c5f098](https://github.com///commit/7c5f0982a276abd5df50b404fbce8588db1764ec))
* **frontend/js:** migrate comic_reader.js to ES6 ComicReader module ([4b3f5bf](https://github.com///commit/4b3f5bf0f7c64d3e61d7a63dfd5cfc2198c66d6c))
* **frontend/js:** migrate cookie_consent.js to ES6 CookieConsentManager module ([1219f57](https://github.com///commit/1219f57fe0bec7970442c24a57c336bbc63a6fe3))
* **frontend/js:** modernize theme toggle to a binary Dark/Light mode with icons ([80ae230](https://github.com///commit/80ae23027b1316d2d873d34b59ab01966f55c827))
* **js:** establish shared module architecture to prevent cross-domain imports ([a24c862](https://github.com///commit/a24c862935d0ed054fcf34fd735fafea0d48485a))
* **js:** share ThemeManager across admin/frontend and migrate archive.js ([08126a7](https://github.com///commit/08126a773ccc3a16ed2c8205372f213d3264ce2e))

### 🏗️ Build System

* **scripts:** clean public JS directory before minification ([e985879](https://github.com///commit/e985879c9a871582b0a09dbe402a0e5fca6aaf57))

## [6.4.9](https://github.com///compare/v6.4.8...v6.4.9) (2026-07-30)

### 🏗️ Build System

* **scripts:** massively optimize JS minification using Terser native API and parallel processing ([ec5aa17](https://github.com///commit/ec5aa17f441025882f6cb6c6a0d168787640cf07))

## [6.4.8](https://github.com///compare/v6.4.7...v6.4.8) (2026-07-30)

### ⚙️ Refactoring

* **admin:** remove legacy inline scripts from PHTML templates ([aaad256](https://github.com///commit/aaad256010c437a85c3d32ae6c4177cd14aa2fe3))

### 💎 Styling

* **admin/js:** fix remaining biome linter warnings ([4301a66](https://github.com///commit/4301a66c4c64f5bf93ad52464ad75d9bc5a87201))

## [6.4.7](https://github.com///compare/v6.4.6...v6.4.7) (2026-07-30)

### 🐛 Bug Fixes

* **admin/ui:** preserve DataTable pagination state across page reloads ([b13d164](https://github.com///commit/b13d16401a7b127207418009d4530bd12b6b44f7))

### ⚡ Performance

* **admin:** eliminate save latency by backgrounding XML generation and removing JS delays ([a2909e0](https://github.com///commit/a2909e041d54cbaa2ccdfc4ecf3cdb64ba05ffeb))
* **api:** eliminate 3-5s save latency by parallelizing remote extension probing ([04f34f0](https://github.com///commit/04f34f01d3e9630be12a6f7ee517a51b205816a6))

## [6.4.6](https://github.com///compare/v6.4.5...v6.4.6) (2026-07-30)

### 🐛 Bug Fixes

* **admin/ui:** resolve AJAX 404 and CSP violations for lazy-loading and FOUC prevention ([1d3ae90](https://github.com///commit/1d3ae90be50b4661cb402e34192e8ea1b0545023))

### ⚡ Performance

* **admin:** eliminate FOUC and reduce dashboard load time from ~12s to ~0.1s ([9980260](https://github.com///commit/9980260c959c1ec9f65acbf0c657ac45d5ad2d33))

## [6.4.5](https://github.com///compare/v6.4.4...v6.4.5) (2026-07-30)

### 🐛 Bug Fixes

* **admin/js:** resolve DOMException crash during form draft restoration ([57e58de](https://github.com///commit/57e58de964b574b80e11da237853851365c4ca53))
* **admin/js:** resolve DOMException during auto-save draft restoration ([fab046e](https://github.com///commit/fab046e761481c39f8bdf6150f805d0c5558bfd5))

### ⚙️ Refactoring

* **admin/js:** implement api standardization, auto-save caching and code splitting ([4be9fa0](https://github.com///commit/4be9fa033df5a139f86a44f53cae54533ed97125))
* **admin/js:** implement core services for global error and form handling ([3df93f3](https://github.com///commit/3df93f3d20495862fbc74285d191311f74fb77ec))
* **admin/js:** implement DragDropService, Debouncing, and DirtyStateTracker ([36842bd](https://github.com///commit/36842bda2c59121c51967e564ff7176ad3c50b88))
* **admin/js:** implement reactive state management and FormService integration ([74ebd24](https://github.com///commit/74ebd245b63599803c07ea5fd441ba9d42661428))
* **admin/js:** restructure architecture, fix linter warnings, and implement full form auto-save ([7939895](https://github.com///commit/7939895829015d2cc57e89c10f6cd2b5d9669935))

### 🏗️ Build System

* **scripts:** rewrite JS minifier to support recursive subdirectories ([0183983](https://github.com///commit/0183983a55c9220226a69f813083b7f4dfc4cf5d))

## [6.4.4](https://github.com///compare/v6.4.3...v6.4.4) (2026-07-29)

### 🐛 Bug Fixes

* **admin/js:** resolve biome linter warnings and restore character image live previews ([730a3b9](https://github.com///commit/730a3b972c8f575f5b5cefe33330a7a8bfff8e60))
* **admin/js:** resolve dependency injection and API endpoint regressions ([84b626f](https://github.com///commit/84b626f833381c89321cc02ff45c1bdfd488d3e4))

### 💎 Styling

* **admin/js:** fix biome linter warnings ([9b6c90e](https://github.com///commit/9b6c90ea379d9fb4000ff9d67bccc2b2a664ea39))

## [6.4.3](https://github.com///compare/v6.4.2...v6.4.3) (2026-07-29)

### ⚙️ Refactoring

* **admin/js:** completely eradicate legacy admin.js monolith ([17767ee](https://github.com///commit/17767ee207870de8a447c19d86214babb74588e1)), closes [#btn-transfer-transcript](https://github.com///issues/btn-transfer-transcript)

## [6.4.2](https://github.com///compare/v6.4.1...v6.4.2) (2026-07-29)

### ⚙️ Refactoring

* **admin/js:** complete JS modularization with MediaGallery and NewsletterManager ([2d46977](https://github.com///commit/2d46977c10c852be58fa3c8ce9a390e796091dcb))
* **admin/js:** completely eradicate legacy admin.js monolith ([fe4f3bf](https://github.com///commit/fe4f3bf33cfe1a0cbfb49850230e096a79e164ae))
* **admin/js:** modularize MassUpload and Cropper functionalities ([f911005](https://github.com///commit/f911005cd8279dde52c134571eb7dcc37d7a6d84))

## [6.4.1](https://github.com///compare/v6.4.0...v6.4.1) (2026-07-29)

### 🐛 Bug Fixes

* **admin/js:** resolve Biome linter warnings and runtime DOM ID errors ([4898644](https://github.com///commit/4898644e0d5d0e072f0ae709e1ab268150cfa406))
* **admin/js:** resolve DOM ID collisions and restore missing Tab-Logic ([20287a8](https://github.com///commit/20287a8383acaeb6787388673657dc6b7a50f24f))
* **admin/js:** resolve missing character description in editor modal ([5ce35a9](https://github.com///commit/5ce35a995fbc797d0ae35f7c73f188470b589692))
* **admin/js:** resolve unsaved changes prompt, SystemManager crash, and restore comic previews ([98a02c7](https://github.com///commit/98a02c7a8175138282a9486105109490155f1aa7))

### ⚙️ Refactoring

* **admin/js:** cleanup legacy admin.js and preserve non-modularized features ([1e83b66](https://github.com///commit/1e83b662ab107d6be6e026169a38a432e38e2ff9))
* **admin/js:** implement ES6 ChapterEditor and GroupEditor modules ([07435bb](https://github.com///commit/07435bbc9f3a58da4915932932a1da14bd6959c6))
* **admin/js:** implement ES6 CharacterEditor module ([8415b63](https://github.com///commit/8415b63e3f776178d06f79a5ea7d3964732a693c))
* **admin/js:** implement ES6 ComicEditor module and bootstrapper ([579746e](https://github.com///commit/579746e3ea02697aa5517f6db4bccddd60d4aa75))
* **admin/js:** implement ES6 ReportManager and SystemManager modules ([69788f4](https://github.com///commit/69788f429c94037487013163c0731e9273dc01d6))
* **admin/js:** introduce modular ES6 architecture and core services ([2653320](https://github.com///commit/2653320bd1b898964a33f0ad92c07a0062da8005))

## [6.4.0](https://github.com///compare/v6.3.0...v6.4.0) (2026-07-28)

### 🚀 Features

* **admin/comic:** implement undo/redo toggle and undelete (trash) functionality ([43867c2](https://github.com///commit/43867c2a4ea524091b067441c765f0084f96730b))
* **admin/security:** implement granular role-based access control (RBAC) ([8c8fddd](https://github.com///commit/8c8fddda9d4f9bc047799516b48b064cd5bec40a))
* **admin/ui:** apply granular RBAC permissions to UI buttons ([a74b90e](https://github.com///commit/a74b90e6dfa89e8f7e8444083ff51ed1bee394c8))

### 🐛 Bug Fixes

* **admin/templates:** resolve linter warnings for permission variables ([202d081](https://github.com///commit/202d081cabf053e4b262800c52a5f7bebf3af4a3))

## [6.3.0](https://github.com///compare/v6.2.1...v6.3.0) (2026-07-28)

### 🚀 Features

* **frontend/seo:** implement Open Graph and Twitter Card meta tags ([8caae16](https://github.com///commit/8caae1682c6998430f07c30158c999347714c7c9))
* **frontend/seo:** use character images for social media sharing ([c2b6215](https://github.com///commit/c2b6215a588574642e80c064a78d0f2a131e5caf))
* **frontend/ui:** implement dynamic character filtering system ([0a31199](https://github.com///commit/0a31199fe4e4fd07d6bbc43ea4427985422c75a3))
* **frontend/ui:** set alphabetical character view as default and add image fallbacks ([4c43b8d](https://github.com///commit/4c43b8d90e731ba98bac76e67332bbb37aa62d80))

### 🐛 Bug Fixes

* **frontend/seo:** resolve undefined site_description and add missing thumbnail fallbacks ([d9817ef](https://github.com///commit/d9817efd941ac13d24e373355bbfdd0ca6171ce0))
* **frontend/templates:** resolve linter warnings for undefined variables and classes ([d470ae3](https://github.com///commit/d470ae377f8bdb63dea20df113fcbbd23d25ae09))

### 🏗️ Build System

* **server:** deploy production htaccess with caching, compression, and routing ([9e6cafc](https://github.com///commit/9e6cafc57b1c28bbdb7bef59680e15f74699aa3a))

## [6.2.1](https://github.com///compare/v6.2.0...v6.2.1) (2026-07-28)

## [6.2.0](https://github.com///compare/v6.1.0...v6.2.0) (2026-07-27)

### 🚀 Features

* **admin/auth:** lay API foundation for advanced role and user management ([46f4bba](https://github.com///commit/46f4bba54cd69dc4c5adfc0d9165d59862fffe82))
* **admin/ui:** implement JS logic and KGA permission tree for user management ([81aea37](https://github.com///commit/81aea3764cd7d4a19623a78e62525f2c80caa649))
* **admin/ui:** implement user and role management dashboard ([5993045](https://github.com///commit/5993045b880ffff72610aec75e7947f31b553f7e))

### 🐛 Bug Fixes

* **admin/ui:** resolve Biome linter warnings in admin.js ([96a91d3](https://github.com///commit/96a91d374a097eae98920bbca072ccb15d1caf4a))
* **admin/ui:** resolve undefined variables and docblock warnings in users section ([a9858fa](https://github.com///commit/a9858fa7185854863de9b4ebf882efd088dabd02))
* **core/domain:** allow timestamp-based legacy character IDs ([2851a7c](https://github.com///commit/2851a7c214c396f4b9095bbb6692581906ae840d))
* **tools:** align migration script with actual database schema ([0fa4f06](https://github.com///commit/0fa4f06fa0fa639d7f019667d0f1408580efe620))
* **tools:** align migration script with storage configuration structure ([f583036](https://github.com///commit/f58303675ec9a5ae95252e07f8c462a54eee705b))
* **ui/routing:** correct arguments for template renderer in 404 fallback ([ec15caa](https://github.com///commit/ec15caad516f7657544bc04c621c03712f444600))
* **ui/routing:** use character IDs for URLs to prevent 404 errors ([6222caf](https://github.com///commit/6222caf64316e2b136d188b6c507701b3cdcde5b))

### 🏗️ Build System

* **server:** configure htaccess to allow migration script execution ([69b2dd6](https://github.com///commit/69b2dd6e71ab1867b04c894c6b4b006d68f329b2))
* **tooling:** configure Biome to allow console.error and fix remaining lint warnings ([e7dd739](https://github.com///commit/e7dd73964aa4862a177b6e30d3895bfe2db42d42))
* **tools:** implement fast, isolated data migration script ([cc52b4b](https://github.com///commit/cc52b4bb005dbcef3d7ee763f38fe8233a47771a))

### 🧹 Chore / Maintenance

* **admin/ui:** restore structured error logging for debugging ([025f425](https://github.com///commit/025f425240703089e7adc6db68b311a702968aac))

## [6.1.0](https://github.com///compare/v6.0.0...v6.1.0) (2026-07-26)

### 🚀 Features

* **ui/frontend:** add cancel option to bookmark sync modal ([eb124e6](https://github.com///commit/eb124e680cbfd472f45c4cd99450661b3e19b5d8))

### 🐛 Bug Fixes

* **core/bookmarks:** disable cloud sync for system accounts ([c093826](https://github.com///commit/c093826c6e20a05fa05cc0c9f2f256a529367e21))
* **core/seo:** resolve sitemap.xml url formatting and missing pages ([91eb22c](https://github.com///commit/91eb22c916a5292f061fe145dfa15f632d6b3b12))
* **core/seo:** resolve sitemap.xml url formatting and missing pages ([89d3157](https://github.com///commit/89d3157491aa6e0bd129f64b5efdf03c7ecd344e))
* **core/seo:** trigger sitemap generation on character updates ([fec1bc6](https://github.com///commit/fec1bc61d5520b2158681eb7bd51f18aa74d3d44))
* **deps:** patch npm security vulnerabilities and update dev tools ([62a62c4](https://github.com///commit/62a62c4687222a5939e668432e06c69e88d2a0dc))

### ⚙️ Refactoring

* **core/domain:** introduce Username and EmailAddress Value Objects ([340b881](https://github.com///commit/340b88188100a4cb2a7b82d3c1aeafd33f38fb67))

### 🧹 Chore / Maintenance

* **deps:** bump actions/checkout from 7.0.0 to 7.0.1 ([122f06d](https://github.com///commit/122f06d3be692107f082330b48a26ab62a527853))
* **deps:** bump actions/setup-node from 6.4.0 to 7.0.0 ([b9efa32](https://github.com///commit/b9efa321940cff43d69fc148692e17564415fba0))
* **deps:** bump softprops/action-gh-release from 3.0.1 to 3.0.2 ([fd8ef61](https://github.com///commit/fd8ef616fc3621558ef6fef53b3db200b604c60c))
* **deps:** bump the npm_and_yarn group across 1 directory with 3 updates ([954f220](https://github.com///commit/954f220dae23c1e31a3270e233f0b4fb4a7d9856))
* **deps:** clean up deprecated npm dependencies ([02d4608](https://github.com///commit/02d46084796474044bd5c876b58092a44dbf00a5))

# Changelog



## [6.0.0](https://github.com///compare/v5.0.0-alpha.23...v6.0.0) (2026-07-26)

### ⚠ BREAKING CHANGES

* Breaking Change um auf Version 6.0.0 zu springen

### 🚀 Features

* **admin/characters:** expand character entity to support high-res portraits, swatches, and reference sheets ([6b8250b](https://github.com///commit/6b8250bc2537c8c08f225744f6fd643e3561d1b0))
* **admin/characters:** implement drag-and-drop and live previews for extended media ([5077cc3](https://github.com///commit/5077cc37b89f60c75ec89e2485e88d84dd9c1be4))
* **admin/characters:** implement FTP fallback via manual text inputs and dynamic gallery selection ([8586a2e](https://github.com///commit/8586a2e1913f0c61fa3c6e854aa2020188dac736))
* **admin/media:** finalize interactive social media cropping workflow and UI integration ([0153e99](https://github.com///commit/0153e997f2526c74433e700a45b61095dfb4494c))
* **admin/media:** implement real-time client-side search filter for media galleries ([9d39293](https://github.com///commit/9d3929353bb4403d11966cf267f2581f30c73893))
* **admin/media:** upgrade media library with multi-tab interface and comic asset management ([bc9f2aa](https://github.com///commit/bc9f2aaacba6b6175cebf6f7412dc898dc0d39f1))
* **admin/reports:** add interactive JSON telemetry visualizer ([3179454](https://github.com///commit/31794546911c3f221b80c9f3e9e50695fe914ec4))
* **admin:** enhance admin dashboard with character management and modular JS architecture ([eaaca68](https://github.com///commit/eaaca6839130c97966451aa3ee56eac24a9011d8))
* **admin:** enhance character domain schema, implement image auto-detect, and resolve tab reset bug ([c8cc1e0](https://github.com///commit/c8cc1e028b4dcec2bb1e14f6f606a51c7de17504))
* **admin:** implement advanced group management and dual-view character assignment ([8c6acd5](https://github.com///commit/8c6acd5e844cf65e13a7bbe66c6696ba8944e973))
* **admin:** implement asynchronous side-by-side overwrite protection for mass media uploads ([3c70dca](https://github.com///commit/3c70dcad1055c71745d7d6c1dbb06384de050154))
* **admin:** implement AuthMiddleware and scaffold Admin Dashboard UI ([4eeaace](https://github.com///commit/4eeaacec15649a7ac9d394edc0c7e567ea928e5c))
* **admin:** implement chapter management for dynamic archive generation ([0fd2ffd](https://github.com///commit/0fd2ffdba2300f0c9991579f295b6ecf60ce2c79))
* **admin:** implement Comic Editor Modal with AJAX live-saving and Undo functionality ([e566a95](https://github.com///commit/e566a952cf64c8a7765f6fc4697c36b3810087d4))
* **admin:** implement deep renaming algorithm for comic IDs and associated media ([fd4a46d](https://github.com///commit/fd4a46dddda698a6f195a92b5371f93734b7fa2a))
* **admin:** implement drag & drop media workflow and robust WebP generation pipeline ([1e4593b](https://github.com///commit/1e4593b8f98d43eb8e97275635147e6db7913f83))
* **admin:** implement intelligent dual-upload pipeline with automatic media generation ([2a6ef51](https://github.com///commit/2a6ef517b0d9659f0e817a3aec2fc3041ddbbce5))
* **admin:** implement intelligent resolution-based mass media upload queue ([9fd5ae7](https://github.com///commit/9fd5ae76a09e82008c7a3ffe3c2d88bd50d78d7c))
* **admin:** implement interactive media library dashboard ([e06dde8](https://github.com///commit/e06dde83482d45e461bbbabb39ade2721c78a63f))
* **admin:** implement missing data indicators, destructive prompt validation, and smart defaults ([e39ab34](https://github.com///commit/e39ab34f9e0990c9d813f39c7ed01f3a94719cb9))
* **admin:** implement paginated report management with live diffing and editor integration ([af52aeb](https://github.com///commit/af52aeb2ea5876a12ef81b7334889ab2040c31fb))
* **admin:** implement secure authentication workflow and admin routing ([78e9d51](https://github.com///commit/78e9d514941c58f03a6d6c26ff45e074e822b5fa))
* **admin:** implement visual character assignment and drag-and-drop group management ([71c24ed](https://github.com///commit/71c24ed1cff03e0a347ae189621b909069baaf68))
* **admin:** UI/UX overhaul, WYSIWYG editor integration, and intelligent media handling ([089470c](https://github.com///commit/089470cdd0c80fb132a9cde0279bdb8a0de508bb))
* **api/admin:** implement force-upload override for orphaned media files ([81d3951](https://github.com///commit/81d3951e49b83dfb506fe67e827f0b9583a83284))
* **api/reports:** add live transcript fetching for error reporting modal ([a137be3](https://github.com///commit/a137be3a37580a616cfe0ebafbedf51ef25eb42d))
* **application:** implement batch-save api actions and DTOs for comics and characters ([b6f5cae](https://github.com///commit/b6f5cae8781c38c4517cb21502d493301e10baea))
* **application:** implement front-controller and dynamic clean-url routing ([4ed840c](https://github.com///commit/4ed840c84519da77727e2e22b678a3eef53148f5))
* **application:** implement http actions and strict request DTOs for comics and reports ([25edb28](https://github.com///commit/25edb281f1f8c9b911b2702ca1b2e05761cb9ac1))
* Breaking Change um auf Version 6.0.0 zu springen ([7d2cc05](https://github.com///commit/7d2cc05253b004cea4ac19d80a09a447a905828f))
* **characters:** expand character profiles with comprehensive biographical fields ([b3ab04c](https://github.com///commit/b3ab04c7d6d9b6e0fe2b064a6b2294a2354017fc))
* **config/rss:** introduce configurable item limit for RSS feed ([a534598](https://github.com///commit/a534598852ffe645af1cb145b2cbb3d8505e3c0f))
* **core/bookmarks:** implement bookmark entities, repository, and sync API ([353c38e](https://github.com///commit/353c38e968adab94874935c1960a38484678a2c7))
* **core/cron:** implement garbage collection for unverified users and expired tokens ([64dbc49](https://github.com///commit/64dbc49158d4421a31573d34731f0fcaad896be6))
* **core/mail:** implement admin newsletter triggers and email templates ([456622b](https://github.com///commit/456622bb88bfec952dedaade7c27c3562afbbf98))
* **core/mail:** implement smart mail queue and missing auth services ([2152e09](https://github.com///commit/2152e0983c0fb80baf7bbc735ced005c9cbd6ced))
* **core/mail:** prepare priority queue and report notifications ([1f24ed4](https://github.com///commit/1f24ed4c397dfac198156a4441f8aa2177a9f936))
* **core/security:** establish unified RBAC schema for frontend and backend users ([c82db2c](https://github.com///commit/c82db2cc9adecac7db6b5852db308e0c4e679517))
* **core/security:** implement new RBAC entities and mysql repositories ([6824a95](https://github.com///commit/6824a9501d27a70e39d6d801263959b77ac7870f))
* **core/user:** add second newsletter subscription for text-only updates ([61e0efb](https://github.com///commit/61e0efb9152ef051e6c57d8074d27085d946dd89))
* **core:** implement automated background generators for RSS 2.0 and Sitemap ([1b69f0d](https://github.com///commit/1b69f0de27312db0926eb84bbb13d1c3ee9c8321))
* **domain:** complete phase 3 with character, feed, and media services ([e1e9da9](https://github.com///commit/e1e9da958115ca3be6b82a4b705a8b25ee416d61))
* **domain:** implement automated revision history and flexible URL hydration ([426f746](https://github.com///commit/426f746951a3ba1ea6ba4418173a6972f836c7ce))
* **domain:** implement core business logic services for comics and reports ([91d71c8](https://github.com///commit/91d71c89bf32149d49fb57d123cbb8ae74d19c04))
* **domain:** implement core entities and value objects for comics, characters, and reports ([d7669f0](https://github.com///commit/d7669f0c92b932854436be1ae6e7b7070c5eb0cf))
* **frontend/archive:** implement database-driven comic archive with chapter grouping ([0af3011](https://github.com///commit/0af3011ce952ac854f1dc78c1118bce1385c1e4b))
* **frontend/characters:** extract reusable character component and implement native lazy loading ([afca834](https://github.com///commit/afca83484ba5f490541c8f7f19bb49de69aa8364))
* **frontend/characters:** implement dual-mode character list view ([ebbfb9d](https://github.com///commit/ebbfb9d304d94e83d29a199c17ad26bd7b7a10a7))
* **frontend/characters:** implement ultimate character detail profile and appearances view ([6f8ef56](https://github.com///commit/6f8ef56f991824adc25b1b1a785ad6ed85ec547f))
* **frontend/reader:** enhance comic page header with dynamic metadata ([7be3824](https://github.com///commit/7be3824d9cbe86a41180e4c0fdabce17e9144f5f))
* **frontend:** add apache routing configuration and 404 error template ([75c390b](https://github.com///commit/75c390bb0a5d4567cde74b36b9548eafe889b6ed))
* **frontend:** implement robust, high-performance AssetHelper for automated cache-busting ([643e612](https://github.com///commit/643e612d65f68f8408f842e129015b46b25855ed))
* **frontend:** implement server-side rendered templates for archive and character list ([2010f32](https://github.com///commit/2010f32c36b74a05a108de5812fa7c0f3dd46d7e))
* **frontend:** implement server-side version hydration and global layout components ([80f2e6e](https://github.com///commit/80f2e6e3402961c3a3b2bf2b70ddeeb535a9d90a))
* **frontend:** implement TwoKinds maintenance page and migrate SCSS architecture ([d7c7b97](https://github.com///commit/d7c7b9764525a2e0ec220eed931e038d933deebf))
* **infrastructure:** define mysql schema registry and storage contracts for domain entities ([83e62e1](https://github.com///commit/83e62e11af435b62dc06692d0086e8ca3996fcee))
* **reports:** implement image upload for user screenshots ([1774324](https://github.com///commit/17743241eb8609268223c71650834419a472fc91))
* **storage:** implement rolling history limit for comic revisions ([89c95d6](https://github.com///commit/89c95d65c73a5b7bc9027be6c3cb80b5ee8ef245))
* **ui/admin:** integrate newsletter and report notifications into admin workflow ([d56de55](https://github.com///commit/d56de55100455408595dd1333897fed689fe1ef5))
* **ui/auth:** add resend verification email functionality ([f9e09ff](https://github.com///commit/f9e09ffba09b58ed2c8309e0361c66427031e3f3))
* **ui/auth:** enhance email dispatch notifications with detailed instructions ([f506728](https://github.com///commit/f5067285ce049adb0317d723178993bf9d96074b))
* **ui/auth:** implement double opt-in email verification for registration ([62788ab](https://github.com///commit/62788abd7406e602c703f98c9b990f0e434793cf))
* **ui/auth:** implement robust frontend user registration with anti-bot protection ([e8a89b0](https://github.com///commit/e8a89b033dcdd6efbd373dcfb4f73bb2b611ee11))
* **ui/auth:** implement secure forgot password flow and dedicated email config ([06477c8](https://github.com///commit/06477c8c205556aac690f6ca37776061697c2832))
* **ui/auth:** resolve auth bugs, protect admin usernames, and add user profile ([c46836f](https://github.com///commit/c46836ff0d23689a98d66b209660c4a6bc2cc9c7))
* **ui/characters:** enhance layout for biographical data and integrate Kaidran age conversion ([9bc7ec1](https://github.com///commit/9bc7ec1bac05dd738e2a2d76d7f3e13fe505fd4d))
* **ui/core:** deploy cookie banner and streamline bookmark storage ([2b24f3a](https://github.com///commit/2b24f3a00960ef832d3eb75603b813ab7c3049a7))
* **ui/core:** make GA4 tracking dynamic and conditionally hide cookie banner ([d3a2486](https://github.com///commit/d3a24868cf3b2ce124302637b2134604eed6aeff))
* **ui/pages:** add Lima City to imprint credits and implement drag & drop for report screenshots ([0f6837c](https://github.com///commit/0f6837c454185645b287ff8751c4652430bd9e56))
* **ui/pages:** create unified project info hub and FAQ ([a60a2d2](https://github.com///commit/a60a2d242fbae2f14f261fe199c664d4d4132f96))
* **ui/pages:** enrich info hub with lore, bios, and complete FAQ ([768a83a](https://github.com///commit/768a83ab390a96691865c2e6d2cfb529ab3e03a4))
* **ui/pages:** implement dynamic frontend bookmark overview ([4a719e3](https://github.com///commit/4a719e3936591069fa10f0772fb0b3bc2582b6f8))
* **ui/pages:** implement Imprint, Privacy Policy and 403 Error pages ([c622178](https://github.com///commit/c622178f7d276d3881c1909539ec44c78d51f672))
* **ui/pages:** integrate author mugshots and legal menu links ([1d74622](https://github.com///commit/1d7462263ae3179ed628fbecb8cbc09e515f59c6))
* **ui/reports:** extend visual text-correction system to character biographies ([d45bba3](https://github.com///commit/d45bba3606a5093a7083f0a3089b079682d5f074))
* **ui/reports:** implement automated telemetry gathering for global reports ([09775df](https://github.com///commit/09775df607098d5ffbd6ba2de5cd728145833e3f))
* **ui/reports:** implement global error reporting modal with supporter credits ([6464f52](https://github.com///commit/6464f52c3a7668cd5d20fa5839fd4e1d469229fe))
* **ui/reports:** integrate WYSIWYG editor and contextual URL tracking for global reports ([465c7ee](https://github.com///commit/465c7eeb29027458e6adf0403149dfc75e4d8e67))

### 🐛 Bug Fixes

* add sitemap and rss to gitignore ([a5c4367](https://github.com///commit/a5c4367c72c55f15e3c80765bc73f9cbff03e72e))
* **admin/characters:** ensure bulletproof multi-file upload for reference sheets ([5e51c3c](https://github.com///commit/5e51c3c17c7ef2d19ff0c655a448f403b98eee7a))
* **admin/characters:** ensure robust media conversion and multi-file drag-and-drop ([9cc2be1](https://github.com///commit/9cc2be1a2fea3508fa8784cfaea0d0f1c61ccde4))
* **admin/characters:** implement proper DTO mapping, taxonomy datalists, and resolve edit mode bugs ([1bcfe66](https://github.com///commit/1bcfe666667246699cfc3200e3f11359907c4b4f))
* **admin/media:** resolve CSP violation and mitigate OOM errors during high-res cropping ([c0898c6](https://github.com///commit/c0898c68739178e6e562df387d64292d2dbf1556))
* **admin/media:** update UI metadata for comic assets and implement image performance optimizations ([e01be14](https://github.com///commit/e01be1469f1d909175579670b8b0c117e281593a))
* **admin/reports:** add missing jsdiff library for transcript comparison ([46bace6](https://github.com///commit/46bace69064126f9ffbe3458a4dfab58978670bb))
* **admin/reports:** resolve fatal json parsing error and separate visual URL field ([b4374b3](https://github.com///commit/b4374b3b43fc661add92dbbfc56894a6ab1d4da1))
* **admin/ui:** optimize cropper UX, enhance preview rendering, and finalize lazy loading ([5056991](https://github.com///commit/5056991fe69b5a82b0c3989bd35e7c12e2e5fe1e))
* **admin/ui:** resolve flexbox table overflow and restore asset cache-busting ([a12d9c3](https://github.com///commit/a12d9c3c8e88d90a5741b76787a46da6d1b2300d))
* **admin/ui:** resolve image scaling constraints in comic preview modal ([c2466ed](https://github.com///commit/c2466ed6c448da0bf5cd1ad88abf300c68484a94))
* **admin/ui:** resolve ReferenceError in media gallery upload handler ([13f03b2](https://github.com///commit/13f03b2e5508b091e52edb07d7c96ffcd22e03bd))
* **admin/ui:** resolve strict CSP violations, implement drag-and-drop media upload, and satisfy linter constraints ([0481e15](https://github.com///commit/0481e157af4cb69100c43da327ef05ba20505699))
* **admin:** implement automatic `_sketch` suffix appendage for Keenspot sketch URLs ([e05617e](https://github.com///commit/e05617e4c2eb4e1e0fbed07dc62aef9786ceb11b))
* **admin:** purge illegal inline event handlers to enforce strict Zero-Trust CSP ([44a1491](https://github.com///commit/44a1491fc7fdfc6cb39afda70e46fd28b9e5532f))
* **admin:** resolve Keenspot CDN auto-detect false positives and refactor image preview UX ([68563d8](https://github.com///commit/68563d89639ab182170634f358a78bfa33d7f0e9))
* **admin:** resolve modal z-index layout bugs and implement row highlight tracking ([3fae2d9](https://github.com///commit/3fae2d9ea2e47127cc98f9204754dcba674282ac))
* **admin:** resolve modal z-index layout bugs, implement client-side pagination, and intelligent search ([6138eeb](https://github.com///commit/6138eeb436aa5c5c0c435f83589e9db6dc392dfd))
* **admin:** resolve static analysis warnings and repair event delegation scope ([00c04a1](https://github.com///commit/00c04a1204f8e238fc5c4e124b742221b8ca4e35))
* **admin:** resolve Trumbowyg CSP violation, refine modal layouts, and implement triple-preview system ([58cc93e](https://github.com///commit/58cc93e4442595450d501af322f3c1be4f38e2cc))
* **admin:** resolve Trumbowyg CSS conflicts, refine comic preview, and adjust auto-fill logic ([d8e9c50](https://github.com///commit/d8e9c501f9d5cdbdf5f4a499d1c2ee002f570578))
* **api/auth:** exempt public report routes from authentication middleware ([6690b05](https://github.com///commit/6690b057edaeffe5653dcaa17952db929532074c))
* **api:** implement cURL fallback for missing extension and add UI error recovery ([e2b9f58](https://github.com///commit/e2b9f58abe340045a2b0036b793afc2914528565))
* **api:** resolve get_headers TypeError and refactor JS iterables for strict compliance ([48a57be](https://github.com///commit/48a57be9f952c2751a79f21ea98efdc105605c34))
* **api:** resolve linter warnings and refine media pipeline parameters ([20ed346](https://github.com///commit/20ed34624f7adfa9be141a55feaef868bb9efb45))
* **config/frontend:** implement environment-agnostic root-relative assets and local config overrides ([81b9f05](https://github.com///commit/81b9f0552b52d54b659086c4e43abdb491bcafda))
* **config:** implement advanced webp handling and transparency configuration ([b89c2a3](https://github.com///commit/b89c2a33d85a76053f144175341f90c87d4663f4))
* **core/di:** bind RoleRepositoryInterface and clean up user dependencies ([3647282](https://github.com///commit/364728290b1b495c0cac9847f0020ff9ebc1a668))
* **core/di:** restore bindings for mail and magic link repositories ([c0c7ce2](https://github.com///commit/c0c7ce295838c14357fc6ade7f0cec3035942822))
* **core/di:** restore RateLimiter and LoginAttemptRepository bindings ([354b1ac](https://github.com///commit/354b1ac925674c15e28ba88aaf3de39e4666ea90))
* **core/mail:** implement STARTTLS support for modern SMTP providers like Gmail ([b973fc3](https://github.com///commit/b973fc3357de406a210f477f95b030542decdaa3))
* **core/mail:** resolve missing namespace imports and template variables ([bf111a1](https://github.com///commit/bf111a1ba59d288d9ad7216b5b8408be915d43f2))
* **core/mail:** resolve PDO syntax error in mail queue processing ([2d2637d](https://github.com///commit/2d2637d6ae1eca899668a43207c82ce19d4e46ac))
* **core/media:** align social media asset generation with 2026 Open Graph standards ([e8dff8f](https://github.com///commit/e8dff8f830073665d8c21bb78a98e3b892e4a631))
* **core/media:** harden crop operations against out-of-bounds coordinates and memory exhaustion ([93f21d6](https://github.com///commit/93f21d698062341d7ee4f4887b5de86125a38875))
* **core/media:** resolve undefined method references in manual cropping logic ([d524bf3](https://github.com///commit/d524bf3064ed98893678fc27cbdd17b723c8211f))
* **core/reports:** integrate user_id correctly into report pipeline ([e4200d5](https://github.com///commit/e4200d5f34e95f9bb252ad2a3a3524ee5f9023e4))
* **core/routing:** whitelist frontend auth API routes from auth middleware ([e40fd19](https://github.com///commit/e40fd19e89736ecc5dcaa19e0236c5a250639d60))
* **core/security:** port missing anti-bot entities and refactor sql upserts ([d80bc46](https://github.com///commit/d80bc46ebd23c8b8c66a1d8492f61412d29849eb))
* **core/ui:** resolve IDE warnings and undefined variables in templates ([3612e50](https://github.com///commit/3612e5035b91eb7664ad39fe31d818552148763f))
* **core:** perfect RSS 2.0 formatting parity and introduce legacy media fallback ([f3e93b7](https://github.com///commit/f3e93b7cfd05f5f592a85f51af5b2a97bc050c7c))
* **core:** restore PermissionCompiler and expose auth state to templates ([e77c73a](https://github.com///commit/e77c73ad2dbf624fba2d6153e018470f74145c1f))
* **database:** implement granular table-level auto-repair mechanism ([2a5a2d2](https://github.com///commit/2a5a2d2b982142afaaf5b79170a332236fc022da))
* **domain/admin:** relax ComicId validation and patch preview/linter issues ([6caae9c](https://github.com///commit/6caae9cb715d9615ff7f9ed19905d1c3227c2400))
* **frontend/archive:** ensure unassigned comics are strictly positioned at the end ([35d5a75](https://github.com///commit/35d5a7596e54b90556a4766737b46f53013009c8))
* **frontend/archive:** resolve TypeError and trailing slash routing bugs ([96c055b](https://github.com///commit/96c055b0089d3596c747ef20bde2032e30d4de3b))
* **frontend/characters:** resolve native lazy loading image source issue ([6f9640e](https://github.com///commit/6f9640e5883fd97fab95155e3704f55538c978c2))
* **frontend/routing:** resolve root comic route 404 and template inclusion paths ([9ed5369](https://github.com///commit/9ed5369ed3935f8127dc790e8e58c1916edf0027))
* **frontend:** add missing docblocks to layout components to resolve ide warnings ([ba3f971](https://github.com///commit/ba3f9714a022b0dbaf8f36ff29491a5c17ea2a81))
* **infrastructure:** implement LocalImageStorage and resolve DI container crash ([3832c3d](https://github.com///commit/3832c3ddd6bed345c912d37e7326c57a7300b2cd))
* **infrastructure:** resolve early return bug in PdoFactory causing failed schema auto-installation ([a6059e9](https://github.com///commit/a6059e933272ceb5a5f90d1c751083f2166bb108))
* **infrastructure:** unify error log directories and enforce strict mysql connection ([29554c9](https://github.com///commit/29554c9dd1885d6e894c5e4796363e7afe46e6ac))
* **media:** resolve PHP 8.5 imagedestroy deprecation and implement strict mass-upload ID collision handling ([d368613](https://github.com///commit/d368613115984c77668d260ea3fb3e18b1a4b713))
* **qa:** enable BEM selector support ([d34fca7](https://github.com///commit/d34fca7d169454414934f583b6d274858d745c82))
* **qa:** resolve conflict between PHP-CS-Fixer and PHPCS spacing rules ([a6444c2](https://github.com///commit/a6444c2f8138e0dd921101a1cc65a5d1e49a63de))
* **qa:** resolve tool conflicts and suppress false-positive unused variables ([019a1c5](https://github.com///commit/019a1c5f40a5400ef08d64cb33b8abfe30b8e62a))
* **routing:** eliminate pass-by-reference in frontend controller and resolve IDE warnings ([0dcffa9](https://github.com///commit/0dcffa910792eb5a26bf5f37eb07caee7c3ae0e5))
* **routing:** implement missing API routes in FrontendController to resolve 404 on login ([ba6cbd1](https://github.com///commit/ba6cbd19adfd3bb07f9555b89f2c0b0690b5b0bd))
* **scripts:** prevent deletion of PHP 8 attributes in token optimizer ([74687a8](https://github.com///commit/74687a8b5b2b8853f5809ee1f820a648790d8d44))
* **security:** resolve bootstrap crash and implement domain-specific PermissionRegistry ([80eb14d](https://github.com///commit/80eb14dd32b889614b68e967d107f77ef448674a))
* **security:** whitelist blob URIs in CSP img-src directive for local previews ([b03d752](https://github.com///commit/b03d752554a79fb6c8f53a6b202d8844a9d9cc52))
* **security:** whitelist production domain in CSP and remove illegal inline handlers ([880168c](https://github.com///commit/880168c99f83bb31ad4e0a67b41457f4dbbedd25))
* **ui/auth:** explicit routing for backdoor users in admin login action ([794d90c](https://github.com///commit/794d90c600579781c270def6ee4c96b4c0e17bb2))
* **ui/bookmarks:** resolve CSS conflict hiding thumbnails and refine hover logic ([795aefb](https://github.com///commit/795aefb669125b9af007d5ef3521e1bd5066bae0))
* **ui/bookmarks:** resolve missing thumbnails and add rich data sorting ([150d0fd](https://github.com///commit/150d0fda09f7a03a8f94c404c72cd8012c3077c8))
* **ui/characters:** implement masonry layout for reference sheets ([1cf67fa](https://github.com///commit/1cf67fa53198c8fbbc6671dd3a6ef2df240830b2))
* **ui/characters:** implement wrap-around float layout for reference sheets ([66a1c18](https://github.com///commit/66a1c18b14837087166f20128102b537d4d14d41))
* **ui/characters:** improve admin modal layout and frontend character recognition ([535c678](https://github.com///commit/535c6785e5714cc57ad29d00c4ac5373b78e0679))
* **ui/core:** replace obsolete comic.js reference with comic_reader.js ([db5c154](https://github.com///commit/db5c154468276b83a63db5bb0f7f2ca56cd3680b))
* **ui/core:** resolve jquery reference error on login page and modernize variables ([097bd01](https://github.com///commit/097bd01d08cc4de132e658b486f5bb6060c6f217))
* **ui/core:** use config object to retrieve google analytics id ([318285a](https://github.com///commit/318285a164e95886287037eb3f08a7643fadab91))
* **ui/frontend:** auto-fill report modal for logged-in users and fix newsletter routing ([0200d14](https://github.com///commit/0200d1495deec02d7de65a5569c0b46217a291ef))
* **ui/frontend:** auto-fill report modal for logged-in users and fix newsletter routing ([b9b31d5](https://github.com///commit/b9b31d567ac8a1916019b2e527623a33138f1379))
* **ui/frontend:** resolve CSP violations in bookmarks and implement JS cloud sync ([b10e965](https://github.com///commit/b10e965121599d5510aeff3238e30ba7b33b2960))
* **ui/pages:** utilize TemplateRenderer for project info page ([a976d01](https://github.com///commit/a976d017839ea90d409bd932a6a6b5041bf83388))
* **ui/privacy:** rename cookie namespace and fix CSP-blocked banner trigger ([292eabb](https://github.com///commit/292eabb7017b8e4f8053b69252b955bcef617d80))
* **ui/reports:** resolve CSP violations and form payload parsing ([f2c4156](https://github.com///commit/f2c415666a2608f68ad09b5922a9b2665b3525e0))
* upload some files ([82122a9](https://github.com///commit/82122a9a1b5b71d20175cfbe79cc151599ee741e))

### ⚡ Performance

* **core/db:** add database indexes for newsletter subscription flags ([e915525](https://github.com///commit/e915525bac03d706a3eecc3e3361e9304d810a64))

### ⚙️ Refactoring

* **admin/js:** resolve linter warnings for unused variables and optional chaining ([a64aba7](https://github.com///commit/a64aba73c96f7a490cfd08cc84f55a150648232e))
* **admin:** apply ES2020 optional chaining and resolve final static analysis warnings ([ede63e6](https://github.com///commit/ede63e631b1fa6f5f5a386de9bf068a7461195ff))
* **admin:** decompose dashboard templates and implement advanced character modal UI ([a0097b4](https://github.com///commit/a0097b40ad2007c47e65b92fbf7519d879ab050d))
* **application:** pivot from client-side state management to server-side rendering and single-entity updates ([550189f](https://github.com///commit/550189f3980a4f980fe8ceb76ed2018a1170473d))
* **core/db:** use DynamicSqlTrait for mail queue insertion ([a17178b](https://github.com///commit/a17178bf37be1a7faaa4faa9b1d4643f0bd9695c))
* **core:** decouple database from domain services and implement single-character actions ([ec8f20f](https://github.com///commit/ec8f20f462ef3b102dea2e589046fa81e07f9f85))
* **core:** purge remaining KGA boilerplate remnants and fix site title ([c9f093d](https://github.com///commit/c9f093d194d8485196f79a2cee9ce101ee53ce88))
* **frontend/core:** modularize frontend routing and action namespaces ([fb2cf7d](https://github.com///commit/fb2cf7d53f28a0b8b96b995e01b7a7885d89c956))
* **frontend/reader:** consolidate templates and resolve static analysis warnings ([7d4361a](https://github.com///commit/7d4361a3baa9ef0243a948d3a977bec13c27c9cf))
* **frontend/reader:** restore original comic navigation UI and button placement ([d21e8f2](https://github.com///commit/d21e8f27a98776bdf3f3a6085dffbc6021343b1c))
* **infrastructure:** apply DynamicSqlTrait across all MySQL repositories ([1717633](https://github.com///commit/171763351da769a7de81ce9a7d8bcd82326f7623))
* **infrastructure:** introduce dynamic sql trait to eliminate hardcoded queries ([8985a9c](https://github.com///commit/8985a9ccd862101654b1eb80d72d0951ff6cc762))
* **infrastructure:** optimize schema registry and implement mysql repositories ([1b0e43d](https://github.com///commit/1b0e43d2caedd46b1ae11a91d4671db68cf6f561))
* move legacy core and business logic to .old-5.0.0-alpha.23 ([154bc20](https://github.com///commit/154bc2014780a36624d7abf09eba274aea50223f))
* **security:** restructure Content-Security-Policy builder for enhanced readability ([eb56bdd](https://github.com///commit/eb56bdd67a78fd7518be6a78145324b7e3facafd))

### 🏗️ Build System

* **server:** consolidate legacy htaccess with modern front-controller routing and SEO redirects ([2e7b3c2](https://github.com///commit/2e7b3c20161bde137bddff614c07597d603018a0))
* **server:** implement aggressive 301 SEO redirects to resolve Search Console errors ([fe29391](https://github.com///commit/fe29391e4c8730fdc82cc0cce4d4b5c23cebbc9e))

### 👷 CI/CD Configuration

* **cspell:** fix german dictionary loading and whitelist technical terms ([a096ea0](https://github.com///commit/a096ea03262fa0602338bf83bd00b72224960de2))

### 🧹 Chore / Maintenance

* **architecture:** bootstrap core kernel with generic oop/ddd foundation ([9c182ad](https://github.com///commit/9c182ad5b29f1bd0888ad338c58cbd14d1b6092a))
* **assets:** instruct migration of legacy public assets and javascripts ([6804093](https://github.com///commit/6804093b4adf05ba07e4fcd32b6bed3d9ea1c596))
* **bootstrap:** implement EventServiceProvider and purge legacy project artifacts ([23d242e](https://github.com///commit/23d242e724819bc27b06fec69c5b518cf5d32168))
* **infrastructure:** bind domain repositories and implement comic revision storage ([faa8c32](https://github.com///commit/faa8c32579686fd21be086bb13d23b054ce81afe))
* **qa:** enforce strict line length and streamline PHPCS rules ([bee3757](https://github.com///commit/bee375786dfb7031037eb38bca462bd0a0b4ff7b))
* **qa:** polish Rector configuration for PHP 8.4 and strict typing ([89dd33f](https://github.com///commit/89dd33f8a68c24dd7eec25585146e64ded90258e))
* **qa:** unify quality workflows and upgrade to PHP 8.4 ([32afba5](https://github.com///commit/32afba5fa8f7c3ca9f194ffe710066e0d19a9674))
* **qa:** upgrade PHP-CS-Fixer config to strict modern standards ([5b0eb37](https://github.com///commit/5b0eb375b03051582b727cb9ca07fc1193d32592))
* update dev environment blueprint to php-js-dev-env-blueprint ([39da738](https://github.com///commit/39da7388ee7e9086e033a02c883fc491123c309c))

# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [5.0.0-alpha.23](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v5.0.0-alpha.22...v5.0.0-alpha.23) (2026-01-05)


### Bug Fixes

* **security:** expand img-src csp to include explicit domains and add upgrade-insecure-requests ([2c14efb](https://github.com/RaptorXilef/twokinds.4lima.de/commit/2c14efb1e9741a9dea46df15f7d6dd602a26529a))

## [5.0.0-alpha.22](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v5.0.0-alpha.21...v5.0.0-alpha.22) (2026-01-05)

## [5.0.0-alpha.21](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v5.0.0-alpha.20...v5.0.0-alpha.21) (2026-01-03)


### Features

* **admin:** finalize atomic storage and optimized preview logic ([c6d003f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c6d003f731627d2d071b857e5efa18c41231b363))
* **admin:** implement image comparison and modernize report logic ([d177ff1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d177ff1bd62d315676466d786a0fd4fbb6835cec))
* **api:** implement automated telemetry collection and storage ([692ea92](https://github.com/RaptorXilef/twokinds.4lima.de/commit/692ea92a35a197c6339985465d80378bce710eb6))
* implement browser reporting endpoint and upgrade infrastructure to v5.0.0-alpha.21 ([2f90ad4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/2f90ad45b1cd5ff6bf2458d8d0c85c12c088c850))


### Bug Fixes

* **api:** implement dynamic endpoint routing and csrf validation ([8c6f4ea](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8c6f4eaecfde440f6edb3f4ba63eb01127df553d))
* **editor:** resolve auto-fill aggression and restore preview integrity ([438525d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/438525de928e04c586f1fdf1c02d1d9534bb1fc9))
* resolve 500 Internal Server Error and stabilize local environment ([016b62a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/016b62aa570bb90760680296885f2ff162534015))

## [5.0.0-alpha.20](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v5.0.0-alpha.19...v5.0.0-alpha.20) (2025-12-30)


### Bug Fixes

* **admin:** prevent HTML output pollution in API responses and add buffering ([4bcf416](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4bcf416b41ac8c7e623b33418ac4ee05aecb54ad))
* **summernote:** fix report buttons design ([1144718](https://github.com/RaptorXilef/twokinds.4lima.de/commit/11447182f68683ac432accd6f6acfb4f366548eb))

## [5.0.0-alpha.19](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v5.0.0-alpha.18...v5.0.0-alpha.19) (2025-12-30)


### Features

* **characters:** implement interactive view toggle for character display ([ba22971](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ba22971edf120b1358a1d5575f1b9757dd9d8c08))
* **characters:** implement prioritized logic control for interactive views ([e5f0992](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e5f09929f783667bd062e513d4a48110732413c9))
* **characters:** implement sorted tag-based display using existing SCSS grid ([a419b0f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a419b0f83eb758bcdbe576a2383c4f9a62f2b9a1))
* **characters:** implement sorted tag-based view with interactive toggle ([3d9962d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3d9962dea8e0766b9d0f3b2e4e06230c4fbad70a))
* **debug:** upgrade social media debugger with fallback logic and image preview ([bb30e9e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/bb30e9ea900b6992eef1f91faf81eb24aef433d3))


### Bug Fixes

* **characters:** achieve 100% UI perfection with unified tag alignment ([13dbb69](https://github.com/RaptorXilef/twokinds.4lima.de/commit/13dbb699f99f73ddd4b37a04480d206e49096fdb))
* **characters:** reach 100% perfection with centered grid and row-synced tags ([1c44823](https://github.com/RaptorXilef/twokinds.4lima.de/commit/1c448236a2ddb3739b7b1e83a8d885831b203b59))
* **characters:** restore centering and implement row-based tag alignment ([5e12133](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5e121334a519e3bfe3f021705546bf953ad451b3))

## [5.0.0-alpha.18](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v5.0.0-alpha.17...v5.0.0-alpha.18) (2025-12-29)


### Features

* **admin:** achieve absolute perfection in login logic, security, and UX ([bd55f65](https://github.com/RaptorXilef/twokinds.4lima.de/commit/bd55f651db0353d7f4be0cb032e1f5e2a879d8ed))
* **admin:** enhance login UX with input persistence and password toggle ([7b4012d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7b4012d5ee01326920bf3bad7f8bb16ae7d80e52))
* **admin:** implement multi-layered detection for password manager compatibility ([e54db13](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e54db135d0b60b8063b94643ae96c964d00d6fe8))
* **admin:** implement pre-login security warnings and adaptive persistence ([d537ac2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d537ac2a8d478439ac9dae5803eddb57f2512ceb))
* **admin:** implement secure input persistence and password visibility toggle ([d82dddf](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d82dddfb44220efa09014fba262ddb687038b446))
* **admin:** restore brute-force protection and rate-limiting for login ([98c257a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/98c257ace7f3a70e5912d1fd4297e90d89dbcc86))
* **admin:** restore brute-force protection and rate-limiting for login ([31eb792](https://github.com/RaptorXilef/twokinds.4lima.de/commit/31eb79213064bc20381187dc0703b0b727202765))
* **admin:** restore directory creation and synchronize session timing ([44bebce](https://github.com/RaptorXilef/twokinds.4lima.de/commit/44bebce31111db90025dddd98bde16cbb118090a))
* **security:** achieve 'Final Perfection' state for admin initialization ([8353de7](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8353de77d4ee96cd3392a191d601a398364b6073))
* **seo:** implement comprehensive social media integration and image validation ([66d49bd](https://github.com/RaptorXilef/twokinds.4lima.de/commit/66d49bd86a3439409ec9ded5c7eaa42989d2a880))


### Bug Fixes

* **admin:** resolve password field UI conflicts and improve manager compatibility ([c69fb28](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c69fb282bcade0fb279a7a73a31ea6665d04ff1f))
* **auth:** resolve zombie sessions and implement multi-tab synchronization ([13c3202](https://github.com/RaptorXilef/twokinds.4lima.de/commit/13c320219a94c2e7adfe0cd0eb786ce42224f64f))

## [5.0.0-alpha.17](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v5.0.0-alpha.16...v5.0.0-alpha.17) (2025-12-29)


### Features

* **admin:** finalize admin menu structure and integrate red logout style ([57b610a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/57b610a0b9450e26bd9f03fb74198720ef012494))
* **sidebar:** implement subheadings and dynamic tag with precision layout ([3696b6e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3696b6e0e991f30056bef88ab97b08ba961ca471))


### Bug Fixes

* **layout:** unify viewport settings for responsive design ([64551d4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/64551d41d9c25974b0f60d88faf155973e9eced9))
* **menu:** redesign the light-button ([e694035](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e69403585ba442905bfe250ee60d146643a4dff1))
* **menu:** set new dark design ([7cd2a57](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7cd2a576d1ba026be63dfde4d659f59ce4954852))
* **menu:** wiederhergestellt v5.0.0-alpha.15 ([4715ada](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4715adad1eafea3b05845bafe7ad2ff6b53ea129))
* **sidebar:** implement robust and modern RSS copy feedback tooltip ([7d74727](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7d747271151fcc2badc6c2694ee3337cd8fad881))

## [5.0.0-alpha.16](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v5.0.0-alpha.15...v5.0.0-alpha.16) (2025-12-28)


### Bug Fixes

* **about:** prevent layout shift by reserving space for mugshots ([ef0de5c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ef0de5cf41c663e984b32dd952f59e0618252cd2))
* **faq:** resolve accordion ghost-gap using selective paragraph padding ([c713b33](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c713b335a52e706b6d9d9d81e5c91c6f87aee8c2))
* **header:** restore missing classes for cookie banner and typography ([a23cbb1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a23cbb1b4c3fa38ad8e3bf7309c06eae6fe25a67))
* **imprint:** improve email display logic and finalize button styling ([c2ceab8](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c2ceab89b9f9b7e69a1ded01e9919c129ff4f17b))
* **layout:** resolve floating issues in character overview and align navigation icons ([e186be4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e186be402436f90ae8efb1a8d46521dd829db5d6))
* **scss:** add missing original-transcript-box class to modals ([5523df7](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5523df756b8e90742c53b4081bdcf58f4fa97bb9))
* **scss:** restore missing classes for character overview and website version ([75da30e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/75da30e5aa42311a0b6b3e3fbbef6bb48f8fe378))

## [5.0.0-alpha.15](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v5.0.0-alpha.14...v5.0.0-alpha.15) (2025-12-26)


### Features

* **seo:** restructure character routing and enforce clean URLs ([1da25ff](https://github.com/RaptorXilef/twokinds.4lima.de/commit/1da25ff0c884e2f0587665727f5d80f20251dbc7))


### Bug Fixes

* **admin:** finalize admin UI synchronization and update robots.txt ([fbe635a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/fbe635a859a97f2c12ac0df80270adbb191462ae))
* **admin:** prevent login POST data loss by fixing form action and routes ([e0b4839](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e0b4839614b983f4d22a4ce0bc76076fb23ec436))
* **admin:** style inputs and selects in filter controls ([4fddc86](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4fddc86ad054cc142bb653813b2e773f9a041db1))
* **setup:** align button class in initial_setup.php with SCSS pattern ([a1ae510](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a1ae51003c97e0065ed994dce16839db32388377))

## [5.0.0-alpha.14](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v5.0.0-alpha.13...v5.0.0-alpha.14) (2025-12-25)


### Bug Fixes

* **config:** resolve json syntax error in composer.json ([837097d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/837097de313d89fecea0629e4e7fda0830b9a1d6))
* **config:** resolve json syntax error in package.json ([2201d16](https://github.com/RaptorXilef/twokinds.4lima.de/commit/2201d166419bc5ecbfaeb981442defe75306511c))

## 5.0.0-alpha.13 (2025-12-22)


### ⚠ BREAKING CHANGES

* **assets:** Folgende CSS-Dateien wurden gelöscht. HTML-Templates müssen aktualisiert werden, um nur noch `main.css` einzubinden:
- `main_dark.css`
- `character_display.css`
- `character_page.css`
- `cookie_banner.css`
- `cookie_banner_dark.css`
* **ComicEditor:** Das `charaktere`-Array in der `comic_var.json` speichert nun Charakter-IDs (z.B. "char_0001") anstelle der vollständigen Namen (z.B. "Trace"). Skripte, die diese Daten verarbeiten, müssen noch entsprechend angepasst werden.
* **security:** Alle `<script>`- und `<style>`-Tags auf allen Seiten müssen das `nonce="<?php echo $nonce; ?>"`-Attribut erhalten, um von der neuen CSP zugelassen zu werden.
Alle POST-Formulare und zustandsändernden AJAX-Anfragen in den Admin-Dateien müssen ein `<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">`-Feld (oder den Token im Request-Body) enthalten.
* **security:** Der Logout-Link in `admin/src/components/admin_menue_config.php` muss noch angepasst werden, um den neuen CSRF-Token mitzusenden.
* **security:** Der Logout-Link in `admin/src/components/admin_menue_config.php` muss angepasst werden, um den neuen CSRF-Token mitzusenden.

- **Versionsänderung:** Die Projektversion wurde auf 1.8.3.0 erhöht.

### Features

* **admin_menue:** Neue Generatoren eingetragen ([eb5d0a5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/eb5d0a50e0224b194c92f6a0f2c2baefa6c739df))
* **admin_menue:** Neue uploead_image.php im Admin Menü eingefügt ([64df74a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/64df74aa3473b4b35ef6343783f0c66288559b8f))
* **Admin/Reports:** UI/UX Auto-Hide implementiert ([546d3e1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/546d3e1ba6ba7733d5ff10fb44d13f3d1cfe1368))
* **Admin:** Aktionsbuttons 'Neu' und 'Speichern' duplizieren ([7c0d1ff](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7c0d1fffcffb351d2402c44c316682efb772ed14))
* **admin:** Angleichung des Thumbnail-Generators an Social-Media-Generator ([1aef4d7](https://github.com/RaptorXilef/twokinds.4lima.de/commit/1aef4d7bd1436317ccee9250132151f0347db6a9))
* **Admin:** Bearbeiten von Gruppennamen im Charakter-Editor ermöglichen ([cd3cd3b](https://github.com/RaptorXilef/twokinds.4lima.de/commit/cd3cd3b66f68a0ec68d5e13349636db7568ffdc9))
* **Admin:** Bild-Details im manuellen Upload-Dialog anzeigen ([975d3c6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/975d3c6fd0a553bffea518c3ba2d4d9c48c17794))
* **Admin:** Comic-Editor an v2-Schema der comic_var.json anpassen ([f1c6981](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f1c6981e232c439c0b832b15b0116ae986fd2565))
* **Admin:** Comic-Generator an v2-Schema der comic_var.json anpassen ([5d72bf8](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5d72bf8f9615c2f87a5d5eae429d9520c74c52cc))
* **admin:** Erstelle Verwaltungsseite für Fehlermeldungen ([e14c806](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e14c806004419ba03d88644fe822a07b7a67f7af))
* **Admin:** Erweiterte Einstellungen für Bild-Upload (Auflösungserkennung) ([3902cdf](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3902cdf943910b67706bb76e5200aca682302505))
* **admin:** Führe JS-Logik für das Report-Detail-Modal ein ([e62a695](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e62a695a0813048e51c538b4bdaabc2df1f948ce))
* **admin:** Implementiere konsistente "letzte Ausführung" Anzeige ([cd18329](https://github.com/RaptorXilef/twokinds.4lima.de/commit/cd1832956ae08e512f6617feb27f3f81f07fa59e))
* **admin:** Lösch-Funktion für generierte Social-Media-Bilder ([886ad16](https://github.com/RaptorXilef/twokinds.4lima.de/commit/886ad1605e9c6c85805a969b581023520b4e72ef))
* **Adminmenü:** Reihenfolge der Menüpunkte angepasst ([447cd46](https://github.com/RaptorXilef/twokinds.4lima.de/commit/447cd46619485842924618a9b4586d4e0b9bc4d9))
* **admin:** Modernisierung des Charakter-Datenbank Editors (Refactoring v5.0.0) ([5e1aafb](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5e1aafb3ba70d898f1a69e42a05b5c4c7b0b052c))
* **admin:** Modernisierung des Comic-Seiten Generators ([4b3aa43](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4b3aa43bca785e9355e8b58c33a80f4f6ae28768))
* **admin:** Modernisierung des Social-Media-Generators ([b3c4294](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b3c42948240e1808e4fb6c570bc9375502f2e08d))
* **admin:** Modernisierung des Social-Media-Generators ([d5f714b](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d5f714b85c59bd2f094c72c5a3963165475b03e7))
* **admin:** Modernisierung des Thumbnail-Generators ([030c7c6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/030c7c6c7f8e12ca2b45c623ac1c7739b985a168))
* **admin:** Neue Adminseite zum Hochladen von Comicbildern ([ede7736](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ede7736d3facc4fe948fd77366d125a1bf47a561))
* **admin:** Option "Weißer Hintergrund erzwingen" für Social-Media-Bilder ([468e6e8](https://github.com/RaptorXilef/twokinds.4lima.de/commit/468e6e8f881cdb1b290c4abc23d2a5a0116f25b0))
* **admin:** Report-Verwaltung zum Admin-Menü hinzufügen ([12ec278](https://github.com/RaptorXilef/twokinds.4lima.de/commit/12ec278ddc804b6239825f5d1dd3796395bc4e13))
* **admin:** Sitemap-Editor Link zum RSS-Generator hinzugefügt ([46316b0](https://github.com/RaptorXilef/twokinds.4lima.de/commit/46316b023d8ae908e32e0125d87a71e24f448f56))
* **admin:** Überarbeitung der Archivverwaltung und Dateiumbenennungen ([4e243bb](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4e243bb079d10d0146baedcdb91b18e0076d1611))
* **Admin:** Überarbeitung der Report-Verwaltung mit Paginierung und modernem UI ([d08817c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d08817c263a56ab4fc74473a1b05e9361ee0ea5a))
* **admin:** User-spezifische Config und manuelle Speicherung ([8ddc1ff](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8ddc1ff62f0647612ac4e3459b43cd5f5c0317bf))
* **admin:** Workflow für Thumbnail-Generator verbessert ([286be33](https://github.com/RaptorXilef/twokinds.4lima.de/commit/286be33c3a2af90dcffa93e5a4644f9b8f2d8366))
* ähhh ..... Alles neu, neues Design, neue Funktionen, zu viele Änderungen zum aufschreiben xD ([ad9accb](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ad9accbdb96786b3fcd52d4eab1e798bbb978f4d))
* AJAX-basierte Bildgenerierung für Thumbnails und Social Media Bilder ([9fa3da9](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9fa3da93e906e650e14131ecad374d8143169bd3))
* aktualisiere Abhängigkeiten ([45978ae](https://github.com/RaptorXilef/twokinds.4lima.de/commit/45978ae027154c8b2b3ec7d26fd448d5437322ec))
* **analytics:** Erlaube Google Analytics-Tracking im Admin-Bereich ([cc0f9db](https://github.com/RaptorXilef/twokinds.4lima.de/commit/cc0f9dbb22b69c7519a8651a67208de59efd3246))
* **api:** Führe Admin-API-Endpunkt zum Abrufen von Comic-Daten ein ([0f5fc48](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0f5fc48aa101e1fa1684b8c41575cd25858c0bb0))
* **api:** Führe API-Endpunkt zum Einreichen von Fehlermeldungen ein ([32bba09](https://github.com/RaptorXilef/twokinds.4lima.de/commit/32bba09c56448bb1716c59080db0e84792361356))
* app Icons in Menü ([5c3f43b](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5c3f43b6a48548323358e2f14421c49d4f0b8a74))
* Archiv-Kapitelbeschreibung dauerhaft sichtbar machen ([7f32003](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7f32003ebbd41b5322ad7797e0445e5e9234b24a)), closes [#8](https://github.com/RaptorXilef/twokinds.4lima.de/issues/8)
* **Archiv:** Archivseite an v2-Schema der comic_var.json anpassen ([c42e82d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c42e82ddfe360b64c346c5eb65376640d901b702))
* **archive-editor:** Erweitertes Anlegen von Kapiteln mit speziellen IDs ([857b273](https://github.com/RaptorXilef/twokinds.4lima.de/commit/857b2736930918ddaf9017b648c24537f4354647)), closes [#18](https://github.com/RaptorXilef/twokinds.4lima.de/issues/18)
* **archive-editor:** Nach Bearbeitung/Hinzufügen zu Kapitel scrollen ([d396be2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d396be24ff9149fed80b64ad1d6f6fd029909d66))
* **archive-generator:** Überarbeitung und Optimierung der Archiv-Generierung ([138c6d2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/138c6d272d2f3ffc8aed8909c91a4baee0e20e26))
* **archive:** Dynamische Archivseite implementiert und Pfeilüberlagerung behoben ([7b555cc](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7b555cc75242624af1cc817194f5e21ee08f952f))
* **archive:** Implementierung der dynamischen Archivseite (`archiv.php`) ([ff76975](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ff76975163f83248f5a3a0681d9603ed4eece9ef))
* **archive:** Unterstütze mehrere Thumbnail-Dateiformate ([b5f6427](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b5f642700a0cc6cf937a56097cbe0a7473f6554b))
* **archiv:** Modernisiere Archiv-Editor mit umfassenden Funktionen und Fehlerbehebungen ([1be3c4e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/1be3c4e97ec44201852f603e18f5adf5689c6665))
* **assets:** Aktualisiere alle Charakter-Profilbilder ([34d3d3c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/34d3d3ced466425fddd19d810c162af069352470))
* **assets:** Implementiere AssetManager für erweiterte Asset-Verarbeitung ([a90a9e6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a90a9e61de8609277a2efda5a4825769b916be36))
* **assets:** Zentrale Asset-Pfadverwaltung und Umschaltung zwischen CDN/lokalen Dateien ([89cfce1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/89cfce1a051d2212fb87dcf0f91bd7b41225733b))
* Bilder generieren jetzt ohne überlauf des Arbeitsspeicher ([066bf03](https://github.com/RaptorXilef/twokinds.4lima.de/commit/066bf03049e9b9481e9bf0ef1b2037668746100c))
* Bildgeneratoren erstellt ([7bee24a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7bee24a837642ed87bc3c8d760eadaabc0a32ac7))
* Bugfix in cookie_consent.js ([948adec](https://github.com/RaptorXilef/twokinds.4lima.de/commit/948adec4984120939468cec6997cfc84be08f335))
* **cache:** Optimiere den Bild-Cache-Generator und behebe CSP-Fehler ([c64a31a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c64a31a7395bf9f366f28a7ad45f0bac0cd59325))
* **characters:** Füge Seitenanzahl-Zähler zu Charakter-Seiten hinzu ([e59a49e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e59a49ed0d6a4edf4e88cb5154832d239a1ae649))
* **characters:** Führe Charakter-Anzeige auf Comic-Seiten ein ([3d50e47](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3d50e47b1b298e43b6bab91ccb5218b4007b9346)), closes [#48](https://github.com/RaptorXilef/twokinds.4lima.de/issues/48)
* **characters:** Implementiere dynamische Charakter-Seiten und behebe Designfehler ([9938b4e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9938b4ed6ffadf6763c50b6d94f7d2a2cfb735a5))
* **Charakter-Editor:** Visuelles Multi-Select-Grid und Sticky-Footer-Layout implementiert ([66b6e61](https://github.com/RaptorXilef/twokinds.4lima.de/commit/66b6e6148abf85d79225560844127d313717dd99))
* **charaktere-editor:** Erlaube Duplikate über Charaktergruppen hinweg ([4936455](https://github.com/RaptorXilef/twokinds.4lima.de/commit/49364550c39239c221ce90f8f2e347914fea5dfd))
* **charaktere-editor:** Implementiere Drag-and-Drop-Sortierung für Charaktere ([df14e79](https://github.com/RaptorXilef/twokinds.4lima.de/commit/df14e7935ac82dbda8e469502feb3a9253dfa04f))
* **charaktere-editor:** Implementiere ein Admin-Tool zur Charakterverwaltung ([bc37384](https://github.com/RaptorXilef/twokinds.4lima.de/commit/bc37384d0bc9ae357bb545b5c669360039fab049))
* **charaktere-editor:** Modernisiere Charakterverwaltung und behebe Bugs ([726637f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/726637f29f6689bffb3896baef69e015a3104b8e))
* **charaktere-editor:** Überarbeite Charakter-Editor auf ID-Basis und bessere UX ([9c17a90](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9c17a90d01e58c19db9f3e2aeaaeb09cf28f4e65))
* **charaktere.php:** Korrektur des Charakternamen Red ([47f0298](https://github.com/RaptorXilef/twokinds.4lima.de/commit/47f0298b6f15cee661bae35a5077474c69bb3ed2))
* **Charaktere:** Anzeige und Renderer auf ID-System umstellen ([0ecb8ab](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0ecb8ab63786cda7dab4756c8c9d2e7b38a849a1))
* **charaktere:** Erstelle neue Übersichtsseite und behebe Link-Bug ([86f4342](https://github.com/RaptorXilef/twokinds.4lima.de/commit/86f43423346795871472d0236a4b55bbc8088f32))
* Color-Change in sitemap_generator.php ([608a260](https://github.com/RaptorXilef/twokinds.4lima.de/commit/608a2607096fde07703350112bfdb985cda6f6cb))
* **comic-data-editor:** Einklappbare Sektionen und Speichern-Hinweise ([89b4599](https://github.com/RaptorXilef/twokinds.4lima.de/commit/89b45997316b1b770c13dc21688af7d0751d1fd6))
* **comic-data-editor:** Erweiterung des Comic-Informationsberichts um Bildverfügbarkeit ([cdfb206](https://github.com/RaptorXilef/twokinds.4lima.de/commit/cdfb2066558ab22124c5dd605bfd7f1304fdf2fe))
* **comic-data-editor:** Implementierung der Paginierung und UX-Verbesserungen ([f097071](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f097071813ae223dbb5b339e1571c979458e8472))
* **comic-data-editor:** In-Place Editing und lokalisierter Fortschrittsbericht ([7937257](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7937257357df54865de8ef42633bb32c9aa5b58d))
* **comic-data-editor:** Neues Admin-Panel für comic_var.json ([f83a344](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f83a3440166c45ace348687d400dc45b74842b66))
* **comic-data-editor:** Standardmäßig eingeklappte Hauptsektionen ([466d756](https://github.com/RaptorXilef/twokinds.4lima.de/commit/466d756a5e0f4379aa2527ee5d96a666dcf587b9))
* **comic-data-editor:** UI-Optimierungen und Fehlerbehebungen ([588619e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/588619edbef2686e5c07b0d1325cc48270733757))
* **comic-data-editor:** Verbesserte Formularfelder und Paginierungs-UX ([3af378f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3af378fbfe81b60d92388d2f480d910d26337130))
* **comic-data-editor:** Verbesserte Übersicht und Rich-Text-Editor ([1f23083](https://github.com/RaptorXilef/twokinds.4lima.de/commit/1f23083110c240e78efccfec79cba5438f92eb56))
* **Comic-Daten:** Ladeskript für v2-Schema anpassen ([33169ee](https://github.com/RaptorXilef/twokinds.4lima.de/commit/33169ee2b038cec974466efcda1895ad4b4ec840))
* **comic-display:** Implementiere sortierte und gruppierte Charakter-Anzeige ([7f8f6d9](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7f8f6d94fe1f11b5acbee3058056e6cfdf0c8f80))
* **comic-editor:** Direkter Workflow, verbesserte Paginierung und Konfigurierbarkeit ([ba2d13e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ba2d13e7b78ee8bcd45b74b7793a3f09d28ea3e3)), closes [#25](https://github.com/RaptorXilef/twokinds.4lima.de/issues/25)
* **comic-editor:** Erweitertes Kapitel-ID-Handling und UI-Verbesserungen ([3f04e4f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3f04e4fd3684127d5038ad6b0aa7b69b05fa1e4f)), closes [#18](https://github.com/RaptorXilef/twokinds.4lima.de/issues/18)
* **comic-editor:** Füge "URL"-Spalte im Bericht hinzu ([36d7bd1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/36d7bd117e9ae27c8bbfb2694430214bb67d012f))
* **comic-editor:** Füge Bildvorschau zum Bearbeitungsformular hinzu ([8b59437](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8b594370b8e7bf0b0274a783aa69e374e698e5f2)), closes [#29](https://github.com/RaptorXilef/twokinds.4lima.de/issues/29)
* **comic-editor:** Füge Checkbox für leere Originalbild-URLs hinzu ([b68a929](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b68a9294ee2b793eb1fa19338352aa715a219b65))
* **comic-editor:** Füge Live-Vorschau für Comic-Bilder hinzu ([5a2531b](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5a2531bb4fa19c62eff7513035e6c464ee20a635))
* **comic-editor:** Füge mehrfache Paginierungs-Buttons hinzu ([3b91058](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3b91058dc7198e8418b1418be115ecd4b028faad)), closes [#25](https://github.com/RaptorXilef/twokinds.4lima.de/issues/25)
* **comic-editor:** Füge Option zum Leerlassen des Namensfeldes hinzu ([30c23ab](https://github.com/RaptorXilef/twokinds.4lima.de/commit/30c23ab8b847a2acda424a033ff610bc024e3a7f)), closes [#25](https://github.com/RaptorXilef/twokinds.4lima.de/issues/25)
* **comic-editor:** Füge optionalen URL-Link und Live-Vorschau hinzu; Diverse Updates ([e2517a1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e2517a10ee942e45c68f658143a92e278cbf18a0))
* **comic-editor:** Füge visuelle Marker für die Datenherkunft hinzu ([38e7806](https://github.com/RaptorXilef/twokinds.4lima.de/commit/38e78069f9181777e6839417129adb1f79110ab1))
* **comic-editor:** Füge zusätzliche Buttons und längere Animation hinzu ([c982213](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c98221335782cced7fbdd43505aa57a03ba93916))
* **comic-editor:** Implementiere Live-Vorschau-Update für Originalbilder ([26c7d69](https://github.com/RaptorXilef/twokinds.4lima.de/commit/26c7d69c67652912f68dfc8bde67299f43a128a9))
* **comic-editor:** Verbessere die Übersichtlichkeit des Transkript-Feldes ([b3d9ad6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b3d9ad6c6f511b84ef69adca856558c8e945499c))
* **comic-generator:** Redesign und Funktionserweiterung des Comic-Generators ([d707cbb](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d707cbbd59d666bc5023ad66f2636f9b1b18765a))
* **comic-navigation:** Modernisiere untere Navigationsleiste mit Sprachumschalter ([e03f580](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e03f580a0e53ea4047f8c9d0c52a4b3a3948bd8c)), closes [#49](https://github.com/RaptorXilef/twokinds.4lima.de/issues/49)
* Comic-Seiten-Generator im Admin-Bereich hinzugefügt ([823f123](https://github.com/RaptorXilef/twokinds.4lima.de/commit/823f1233d2e23116459fd800c2c24ff9d2ac5e2b))
* **ComicEditor:** Stellt Charakterauswahl auf neues ID-System um ([5c90224](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5c9022409556b1ed7cee38de73c7d88a4e4c39ec))
* **comic:** Füge Modal zum Melden von Fehlern hinzu ([8f7b6ed](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8f7b6edcc9fc7e0239add8b5c5817d2ef1dc70a7))
* **comic:** Füge Vorschaubilder in die comic-data-table ein ([1b7c549](https://github.com/RaptorXilef/twokinds.4lima.de/commit/1b7c549f35fe997425cc030308cf737d057252f4))
* **comic:** Führe Client-Logik für Report-Modal ein und ersetze Lesezeichen-Modals ([5e47835](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5e478352c4aec110b732ade61c493a428a673d4e))
* **comic:** Integriere Report-Modal und modernisiere Renderer-Logik ([0850693](https://github.com/RaptorXilef/twokinds.4lima.de/commit/08506938027527f0f6596c33ec44f8411ec25b4f))
* Comicseiten in Unterordner verschoben und Pfade angepasst ([ec4fd13](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ec4fd13911a773d438f06e21c84df0abead8d721))
* **config:** Füge neue Konstanten für Kernverzeichnisse hinzu ([997c352](https://github.com/RaptorXilef/twokinds.4lima.de/commit/997c3523496d3272b8043bbd795cae08a097c298))
* **config:** Führe zentrale Konfigurationsdateien und URL-Steuerung ein ([dd8d996](https://github.com/RaptorXilef/twokinds.4lima.de/commit/dd8d996c6aaf73c90887be085e3c256f6eac41b0))
* **configs:** Implementiere Vorlagen-Generierung für JSON-Dateien ([b45700e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b45700e107c7c60b70006701643a3d22d87f91ea))
* **content-pages:** Aktualisierung und Modernisierung der statischen Inhaltsseiten ([2bfca51](https://github.com/RaptorXilef/twokinds.4lima.de/commit/2bfca512767f93a4fcb72866b16963340fd3c584))
* Cookie-Banner für DSGVO-Konformität implementiert und Fehler behoben ([f7ffc9a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f7ffc9aba9dfd1af79eabbd45fd9f3f480e6e876)), closes [#7](https://github.com/RaptorXilef/twokinds.4lima.de/issues/7)
* **Core:** Zentrale Konfiguration für Session-Timeout ([1370553](https://github.com/RaptorXilef/twokinds.4lima.de/commit/1370553b48b3ce604491807e568946b74419335c))
* **data-editor-comic:** Führe Charakter-Editor ein und behebe Fehler ([e1dd827](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e1dd827eef6b2dde638fc77a8f7cb20330017596)), closes [#48](https://github.com/RaptorXilef/twokinds.4lima.de/issues/48)
* **data-editor-comic:** Führe Originalskizzen-Management ein ([b924510](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b924510befb142f514e21d1f2e4c8a0cc4f315c5))
* **data-editor-comic:** Implementiere erweiterte Such- und Paginierungsfunktion ([26dd2c9](https://github.com/RaptorXilef/twokinds.4lima.de/commit/26dd2c91eeacea1552ba24070d0c8c612e27585f))
* Dateien umbenannt ([4f25cfa](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4f25cfab051eddf5a8428e500e3848862b37aee6))
* Dateiupload ([9df5568](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9df5568701a262c37d189201294109cfe338d68d))
* Daten entfernt, die nicht mehr benötigt werden (altes Archiv) ([2bff5ea](https://github.com/RaptorXilef/twokinds.4lima.de/commit/2bff5ea049273f766f6d995312ab1ea4cdced475))
* Debugging-Funktionalität für Frontend-Skripte und PHP-Integration ([3f71bb4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3f71bb4053d63234deddcfc9fcb59b1c5827a0ce))
* **Design:** Noto Sans Mono lokal eingebunden und Summernote Code-Ansicht angepasst ([085b762](https://github.com/RaptorXilef/twokinds.4lima.de/commit/085b7620ca71f8278014917272ec90cadb16f7ec))
* **design:** Verbessere Fehlerseiten und aktualisiere Bilder ([3d4bd64](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3d4bd64cb039d0f3dade66105f22997e4c13f5d6))
* Detaillierte Auflistung notwendiger Cookies im Banner und der Datenschutzerklärung ([792e118](https://github.com/RaptorXilef/twokinds.4lima.de/commit/792e11890afd43e2caca3c6be2d675342103268a))
* Drei ISSUES bearbeitet ([4be32f1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4be32f13e71fedc41d2755ab5e7d091233cbda45)), closes [#10](https://github.com/RaptorXilef/twokinds.4lima.de/issues/10) [#11](https://github.com/RaptorXilef/twokinds.4lima.de/issues/11) [#12](https://github.com/RaptorXilef/twokinds.4lima.de/issues/12)
* Dynamische Basis-URL-Bestimmung in header.php ([2e1722e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/2e1722e0c540a8ca0ebd41205f2b9057bda9b15c)), closes [#14](https://github.com/RaptorXilef/twokinds.4lima.de/issues/14)
* **editor:** Füge Charakter-Status-Tag und DocBlock zum Comic-Editor hinzu ([0be3511](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0be35116d13949bca3882f86c8b9a95853a83d6c))
* **editor:** Füge Dateisystem-Synchronisation zum Comic-Editor hinzu ([e5f5b10](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e5f5b10b0f06e0e166d3be4e67010bf0db0d3d78)), closes [#67](https://github.com/RaptorXilef/twokinds.4lima.de/issues/67)
* **editor:** Implementiere Hervorhebung und Scrollen zur bearbeiteten Zeile ([06bd9cf](https://github.com/RaptorXilef/twokinds.4lima.de/commit/06bd9cf24c29ffa3de74b8c7fac62bb5177f971e)), closes [#61](https://github.com/RaptorXilef/twokinds.4lima.de/issues/61)
* Einige Problembehandlungen im Bezug auf den Fehler ([fa28331](https://github.com/RaptorXilef/twokinds.4lima.de/commit/fa283317989f01dad1d6d5c078f5996a20d23ce0))
* endlich klappts! ([61904f5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/61904f5096952f283ee099b80c1e3edc25cc71c2))
* **error-pages:** Erstelle benutzerdefinierte Fehlerseiten ([5598d62](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5598d629d39fbf58de900c72febf9779d44a32a5))
* Fallback-Bilder für fehlende Comic-Übersetzungen implementiert ([f04443f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f04443f6768d2ee13cbd0ea04ab928c8abdec6b7))
* Fehler-Button und Modal in Index-Seiten einbinden ([e348e5a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e348e5a340cdd76e6de4771795b1236f1ca38444))
* Fehlermelde-Modal nach Erfolg automatisch schließen ([9a35d4e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9a35d4e1cf4bc5b6608708a767d7d2b4e2d05eff))
* Fertigstellungd er charaktere.php und Bild-Uploads ([b169c58](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b169c584a23c30bcf3a5375b17c40ad23f49a533))
* **footer+header:** Namensnennung und Versionsanpassung auf 1.0.3.0 ([81e4092](https://github.com/RaptorXilef/twokinds.4lima.de/commit/81e40926dbb5d14449194f13a741e7476cea9293))
* **generator:** Implementiere adaptive Bildgenerierung für Social-Media-Bilder ([352c222](https://github.com/RaptorXilef/twokinds.4lima.de/commit/352c22230625d2706e096bfb0ce17f0a471a5e92))
* **generator:** Implementiere adaptive Bildgenerierung mit Optionen und Konfigurationsspeicherung ([8285c22](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8285c22e1218b8cd428793705090c7704b8859bf)), closes [#43](https://github.com/RaptorXilef/twokinds.4lima.de/issues/43)
* **generator:** Kombiniere Social-Media-Generatoren und füge Modus-Umschalter hinzu ([6093dd4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/6093dd4c7a837c793c52448b11b5aeaa0bb030a3))
* **generator:** Kombiniere Thumbnail-Generatoren und füge Format-Umschalter hinzu ([b35b80d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b35b80de4bc2097769f765f273b5378da2b3f869))
* **generator:** Modernisiere generator_comic.php und erhöhe Version ([d9c36f6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d9c36f6dd3fe9b39a018ec2bcf2384deb5f67556))
* **htaccess:** Implementiere finale Sicherheits- und SEO-Optimierungen ([6afcdf3](https://github.com/RaptorXilef/twokinds.4lima.de/commit/6afcdf376e0ac0c679987ed109c98dea2dc8370b))
* **htaccess:** Implementiere URL-Weiterleitungen und interne Umschreibungen ([d7af404](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d7af4046cb5f08a5ee06eb1aad216ee18ae17117))
* **htaccess:** Verbessere Sicherheit, SEO und Performance ([0cffeff](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0cffefff60e2dec716c8e5da910ca977bc33ba75))
* **htaccess:** Verbessere Sicherheit, SEO und Performance mit erweiterten Regeln ([54e2d9f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/54e2d9f76fb0ec69dcbec3aea5caddf042de3eed))
* **htaccess:** Verbessere Website-Sicherheit und -Benutzerfreundlichkeit ([e41322f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e41322f1e9fdd4c096ac5a5bdccd20ccf5267dcd))
* Ich hab die Nase voll. 5 Stunden für eine Fehlerbehebung... ([d59c469](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d59c469b4733907638c2b96909878d3272d86ad0))
* Implementierung einer Verzögerung bei der Social Media Bildgenerierung ([455f0b9](https://github.com/RaptorXilef/twokinds.4lima.de/commit/455f0b958b3a71149de3c3e4e71c631c58592af7))
* **init:** Führe neue Initialisierungsdateien ein ([675d06e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/675d06e543acdab19161e58844d8d30c84ccbc1b))
* **initial-setup:** Design-Anpassung und UX-Verbesserungen ([c28117e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c28117e4492c3ffe3afec257c78ab0f14c27a85d))
* Jetzt sollte es wirklich gehen :/ ([e57786c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e57786c5dec3a42c8553f7978a1d34a4d5851920))
* **JS:** Session-Timeout Logik um 401-Handling erweitert ([4c5839f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4c5839f93a62d9d246f6b2d8f10a845c38bd52ee))
* Kapitelinformationen in comic_var.json hinzugefügt ([e8959f6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e8959f60a948d780787e7b4151e0eb33eb12ff4d))
* Kollaps-Funktionalität in Admin-Editoren behoben und verbessert ([29be385](https://github.com/RaptorXilef/twokinds.4lima.de/commit/29be385c7f87401dad99ddaad2598afa680d65fd)), closes [#9](https://github.com/RaptorXilef/twokinds.4lima.de/issues/9)
* Lazy Loading für Charakterbilder auf charaktere.php implementiert und behoben ([7f98230](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7f9823078d84079c1bd5be0450329b1a0dcde838)), closes [#5](https://github.com/RaptorXilef/twokinds.4lima.de/issues/5) [#5](https://github.com/RaptorXilef/twokinds.4lima.de/issues/5)
* lesezeichen laden, Bild nicht ([0bfa074](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0bfa074a4af4913174fe1afb0e6c9aa56baa336b))
* lesezeichen-test ([b0b46b3](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b0b46b36b956804f717f8231cee584040c70bd3e))
* **menu:** Füge Link zur Charakterübersichtsseite hinzu ([7f62b6e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7f62b6e1af6274f2ea45ce68b8bda96d125c5e6f))
* **meta:** Führe standardisierten PHP-Header für alle Dateien ein ([8ef4fe6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8ef4fe61b6778991435675d65c41a0b5b527f637))
* **migration:** Erstelle Migrationsskript für die Charakter-ID-Umstellung ([bc305ba](https://github.com/RaptorXilef/twokinds.4lima.de/commit/bc305bab370d4a30090301c9105faeb93671893e))
* Neue Icons im Menü ([99e55e7](https://github.com/RaptorXilef/twokinds.4lima.de/commit/99e55e7a5eecdefddc743b1db29aefe2f01cc1c7))
* Neuer Sozal-Media-Image-Generator ([515c942](https://github.com/RaptorXilef/twokinds.4lima.de/commit/515c942c729936f37aaca15e2e5d482a5a83e21f))
* neuer VErsuch mit der Lesezeichen.php (noch fehlerhaft) ([6181074](https://github.com/RaptorXilef/twokinds.4lima.de/commit/61810743e6f48afb6e2487368fbcf746d74f35ac))
* Nur zwischengespeichert, weils halbwegs funktioniert. Suche noch weiter.... ([df0498c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/df0498c372d5f190f60d8984cef4e3df1b0f92bd)), closes [#8](https://github.com/RaptorXilef/twokinds.4lima.de/issues/8)
* Optimierung der Social Media Bildgenerierung mit expliziter GC und angepasster Verzögerung ([f726e94](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f726e94ade9d780e676a8442d966c811a7348225))
* **pages:** Füge Seitenbeschreibungen und -titel hinzu ([9d55913](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9d5591354ee152c17029904a01eb64d0f0bb3331))
* **performance:** Implementiere Cache Busting für Bilder ([cb7387f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/cb7387fc9775841433ad7a14873cfd47f30c1071))
* Public JS update ([654164a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/654164a6d5e4cd6bed9e88b9e7aabe1788c8f383))
* release Version auf 1.0.1.0 ([edcff4d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/edcff4d5f21a04d136a0a22873c7036deee84520))
* RSS- und Menü-Update ([e0fa1f7](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e0fa1f72d03d6a2d58ade112a501162fb994978c))
* RSS-Generator hinzugefügt ([9c35e74](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9c35e74481b05aea5c9f19725e7df7ff35fd3e3c))
* RSS-Generator verbessert ([eadaf79](https://github.com/RaptorXilef/twokinds.4lima.de/commit/eadaf797d3d820ade59d4bded83095394383ecb4))
* **rss:** Anpassung der Bildvorschau und erweiterte Bildformate im RSS-Feed ([38ce905](https://github.com/RaptorXilef/twokinds.4lima.de/commit/38ce90523a6b96ec8e4d116097cda5489c5b100b)), closes [#17](https://github.com/RaptorXilef/twokinds.4lima.de/issues/17)
* **RSS:** Generator an v2-Schema der comic_var.json anpassen ([18229aa](https://github.com/RaptorXilef/twokinds.4lima.de/commit/18229aa7d7f0f67d951c585657c2d52a79b004b4))
* **RSS:** Generator an v2-Schema der comic_var.json anpassen ([4644ca5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4644ca52b5455c4077e7345c832345a8e9a3ca7c))
* **rss:** jetzt werden alle Variablen aus der json berücksichtigt. :) ([22ee09d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/22ee09d3a29ab7a4bd9aee13076e03dd67d18429)), closes [#17](https://github.com/RaptorXilef/twokinds.4lima.de/issues/17)
* **rss:** Modernisiere Generator, füge "letzte Ausführung" hinzu und behebe Designfehler ([92f2771](https://github.com/RaptorXilef/twokinds.4lima.de/commit/92f277100fdb138fb871a6de02e0bef6001abbcd))
* **rss:** Verhindere das Hinzufügen von Einträgen ohne Bild ([17a066b](https://github.com/RaptorXilef/twokinds.4lima.de/commit/17a066b15d96b87f032a3a21fe0a764a1be47969)), closes [#38](https://github.com/RaptorXilef/twokinds.4lima.de/issues/38)
* **scrape:** Fehler noch nicht gelöst.... ([fa98ae3](https://github.com/RaptorXilef/twokinds.4lima.de/commit/fa98ae3ddefaad8728b42d97e199b1ce57f637c2))
* **scrape:** Jetzt klappts :) ([dd80ca6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/dd80ca61f800d0ea667dadca8b761ab2ecb992a7))
* **SCSS:** Aufteilung der monolithischen _admin.scss in modulare 7-1 Struktur ([48c2da2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/48c2da21177440a90ce4761ea158f787b644af48))
* **security:** Implementiere Brute-Force-Schutz und Session-Timeout-Management ([9a51d66](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9a51d66c1aa80c700ecdef4c71b45ce20e3b11ad))
* **security:** Implementiere erweiterte Sicherheitsfunktionen in admin_init.php ([7e12627](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7e126273ca0fcd50ece9aaaf3ccae7f288d78665))
* **security:** Implementiere finale Sicherheitsfunktionen in admin_init.php ([5f2f048](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5f2f048c72bf20cbeaa741be87b8172a13ef02f7))
* **security:** Implementiere finale Sicherheitsfunktionen in admin_init.php ([aaa31ca](https://github.com/RaptorXilef/twokinds.4lima.de/commit/aaa31caacf8885303c58e5b5e26fd4bf606efb8f))
* **security:** Implementiere finale Sicherheitsverbesserungen in admin_init.php ([151ce7e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/151ce7e18f2024c5eb9e7bcd2dd2220982ef270e))
* **security:** Schütze sensible Verzeichnisse mit index.php ([502d386](https://github.com/RaptorXilef/twokinds.4lima.de/commit/502d3860391f1ec8b324c05d3ce90a503d5cd8d6))
* Seite umbenannt, menü und sitemap.json angepasst ([525bc37](https://github.com/RaptorXilef/twokinds.4lima.de/commit/525bc3766964c7f713d14d673aa9c531f2e67f94))
* **setup:** Erweiterte Initialisierung und Theme-Anpassung in initial_setup.php ([d6e8aaf](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d6e8aaf3b0720b76315391dc673d4a37f1b6458d)), closes [#13](https://github.com/RaptorXilef/twokinds.4lima.de/issues/13)
* **sitemap-editor:** Anzeige des Dateiexistenzstatus in Sitemap-Tabelle ([729bf03](https://github.com/RaptorXilef/twokinds.4lima.de/commit/729bf0387e68bf00624cd600eb2076c8e5722cb0))
* **sitemap-editor:** Erweiterte Sortierung und Konsistenz im Sitemap-Editor ([e0b1d80](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e0b1d80bef12391e75f47077cda3f31e8a9edd88))
* **sitemap-editor:** Interaktive Frontend-Sortierung für Sitemap-Tabelle ([0193b2b](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0193b2b8dcdef61f0f327a2733aa50276ee3985a))
* **sitemap-editor:** Umfassendes Speichern und Spaltenumbenennung ([ddb8b4a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ddb8b4a10bc3ab28281a40fc309904eb7ed5c916))
* **sitemap-editor:** Verbesserte Benutzerfreundlichkeit und visuelles Feedback ([f405c31](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f405c31deb1b0878553c877e55e71f48533967d3))
* **sitemap-editor:** Verbesserte Pfadbehandlung und UX für Sitemap-Verwaltung ([a68b941](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a68b9414230d0e059cab3a6e33b2bffa2b9027ab))
* Sitemap-Generator im Adminbereich hinzugefügt ([3673650](https://github.com/RaptorXilef/twokinds.4lima.de/commit/367365054af1c6c2558cff1ef6630e547135bbd4))
* **Sitemap:** Generator an v2-Schema anpassen & Comics dynamisch hinzufügen ([dd0c56f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/dd0c56f0515e1c78243d6745aa3c6deda3c6b9cb))
* **sitemap:** Modernisiere generator_sitemap.php und erhöhe Version ([8001487](https://github.com/RaptorXilef/twokinds.4lima.de/commit/800148717f1892414cff54cb9a7fcc98fc899d6c))
* stelle AsstManager wieder her ([41d1013](https://github.com/RaptorXilef/twokinds.4lima.de/commit/41d101353e95bb9afd92d09bd0c4a66c5bee5a15))
* Summernote-Editor im Report-Modal implementieren ([780e523](https://github.com/RaptorXilef/twokinds.4lima.de/commit/780e523da991b4fddf850cbb2a65a760a3254dcb)), closes [#83](https://github.com/RaptorXilef/twokinds.4lima.de/issues/83)
* **theme:** Deaktivierung des Theme-Wechsels per Tastendruck auf Adminseiten ([cd8fe9d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/cd8fe9d2e8c17eabebcaa84d03a6bfd997f7e503)), closes [#15](https://github.com/RaptorXilef/twokinds.4lima.de/issues/15)
* Tool zum herunterladen der Comicnamen und transcripte vom Original (nicht fertig) ([8be0f6c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8be0f6c545ed575feb23f8a0f3ec50637bdbc8fe))
* Tool zum Kombinieren von Comic-JSON-Dateien implementiert ([af5ad9c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/af5ad9c6cbd439a10beab8d7d11a8f07554f86b9))
* **ui:** Dynamische und fixierte Button-Positionierung im Bildgenerator ([33d9ec4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/33d9ec49f684c643a9a4109b2388f1a2ea37bdf8))
* **ui:** Füge Button zum Umschalten zwischen deutscher und englischer Comicseite hinzu ([05356c8](https://github.com/RaptorXilef/twokinds.4lima.de/commit/05356c813dad491ba413d7d4d409429e57fb76a7)), closes [#33](https://github.com/RaptorXilef/twokinds.4lima.de/issues/33)
* **ui:** Kompakte Darstellung fehlender Bilder in Bildgeneratoren ([0284725](https://github.com/RaptorXilef/twokinds.4lima.de/commit/02847250800ff67e48727173b11c479d6251a15d))
* **ui:** Öffne hochauflösende Comic-Bilder in neuem Tab ([3e81351](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3e81351fbe381259d1fa9e684c1d0daea722efc9))
* **ui:** Verbessere Tooltip-Anzeige auf Charakter-Seiten ([5e0edf4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5e0edf48bad1f8c99dc7a03bc775dca204dddeda))
* Update Repository files ([9df2abf](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9df2abffa8df6365512aecb7aa170cea62d9aa71))
* upload fehlendes charakter-sheet ([9997022](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9997022ea828688ee2253d5f5094d0717b89d7c3))
* Vereinheitlichung der Bildgeneratoren mit UI- und Leistungsverbesserungen ([7a2a9c2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7a2a9c2afab50adc5684b9d002450eaaf2332f48))
* Versionsanzeige der Webseite eingefügt ([44454ac](https://github.com/RaptorXilef/twokinds.4lima.de/commit/44454ac34df7c6c3425ec2cdd7387325d5b76e90))
* **VSCode:** Hinzufügen von .vscode/settings.json und .vscode/extensions.json für konsistente Entwicklungsumgebung ([446cadd](https://github.com/RaptorXilef/twokinds.4lima.de/commit/446cadd3d13dc6e2413a2a32affada1622cd4b14))
* **webp:** Implementiere adaptive WebP-Generierung und erhöhe Version ([e59f33f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e59f33f85beab875d407a74747a901b54a0c2122)), closes [#43](https://github.com/RaptorXilef/twokinds.4lima.de/issues/43)
* **workflow:** Füge Button-Verlinkung und automatische Cache-Aktualisierung hinzu ([3fc506c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3fc506cc7b419a3d899170c0f6b937debd12abb2))
* zwischenspeichern ([45f6b55](https://github.com/RaptorXilef/twokinds.4lima.de/commit/45f6b552dc717208cd6675e75bae275ac242f03f))


### Bug Fixes

* **admin:** Behebe CSP- und CSRF-Fehler im Admin-Bereich und erhöhe Version ([7902d32](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7902d3285ae2fdec6a5addff1cf14d64bc062033))
* **admin:** Behebe CSP-Verstöße und Session-Fehler in management_login.php ([c6c5d60](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c6c5d605ecdf3a4350f977176a717225dd7ce2ff))
* **Admin:** Bugfix beim Speichern der Uploader-Einstellungen ([dd2ae56](https://github.com/RaptorXilef/twokinds.4lima.de/commit/dd2ae5617e80f129f8b49bf9d1a9c18e24b0f113))
* **Admin:** CSP-Verletzung im Charakter-Editor beheben ([60dad9d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/60dad9df1801fbbd04099f4340c94208ec64d1f0))
* **Admin:** Fallback-Anzeige für Zeitstempel in generator_image_socialmedia.php hinzugefügt ([c30c614](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c30c614f1c97ff8558c2bd73ccf7b6a1891bf1fa))
* **admin:** Inline-Style in initial_setup.php entfernt ([c1524a7](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c1524a7842c053d82af2c4211ffe4cd17ff4efc8))
* **Admin:** Konfigurations-Speicherung in data_editor_comic.php korrigiert und modernisiert ([9e7f2de](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9e7f2deab7f396e8d239c7e3409de5b6f0ac315d))
* **Admin:** Korrektur der Logout-Meldung in index.php ([1e7e2f3](https://github.com/RaptorXilef/twokinds.4lima.de/commit/1e7e2f351b22c6876e4762ca07f0b5a0ed0c3d39))
* **Admin:** Korrektur der SCSS-Klassen für Initial Setup ([b3cccbd](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b3cccbdddebab7061a9f1c3af9fbc78f752b498f))
* **Admin:** Korrektur von Login-Loop und fehlenden Logout-Meldungen ([2b6e3b4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/2b6e3b4e7c582760d011ebddad139abf3428f56d))
* **admin:** Korrigiere JS-Pfade in management_reports.php ([b97bdc7](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b97bdc7e0221007dad5c5fa9c5f60be04e049c3d))
* **Admin:** Login-Stabilität und Logout-Feedback verbessert ([ac79b77](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ac79b77c10126efc5bd5a35a07e2d04b524afa44))
* **Admin:** Optimierung der Login-Seite und Logout-Anzeige ([314f727](https://github.com/RaptorXilef/twokinds.4lima.de/commit/314f727648ac156367e57fc5e5bfb46ad3004b21))
* **admin:** Standard-Ausschnitt auf "Oben" geändert ([abe2370](https://github.com/RaptorXilef/twokinds.4lima.de/commit/abe23704f55a6fc8eefb742eb855e80192b6055c))
* **Admin:** Wiederherstellung der ursprünglichen XML-Generierung in generator_rss.php ([834ced9](https://github.com/RaptorXilef/twokinds.4lima.de/commit/834ced90ab3553d2b66db082428e629cab7d8058))
* Archiv-Bilderladen behoben & Debugging aus JS-Dateien entfernt ([8107c96](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8107c968ad5ab7e296e6d7ed15b881c13e6df549)), closes [#1](https://github.com/RaptorXilef/twokinds.4lima.de/issues/1)
* **archiv-editor:** Korrigiere Design-Fehler bei Summernote-Tooltips ([935c4f5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/935c4f527d77060b7f874d1d5d2907f4eaac8053))
* **archive-editor:** Korrigiere Titel-Feld zu einfachem Text-Input ([312889d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/312889d74e1026b4df0f017357c7665f4cd7d5f8)), closes [#23](https://github.com/RaptorXilef/twokinds.4lima.de/issues/23)
* **archive:** Filtert leere Kapitel ohne zugeordnete Comics ([94b90a8](https://github.com/RaptorXilef/twokinds.4lima.de/commit/94b90a88034703e7faa949bca46c942534e7efd9)), closes [#18](https://github.com/RaptorXilef/twokinds.4lima.de/issues/18)
* **archive:** Korrigiere Sortierung von Kapiteln mit leerer ID und leerem Titel ([ea8b7ff](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ea8b7ff163d4629754cdc16ae5915d9df45d8770)), closes [#18](https://github.com/RaptorXilef/twokinds.4lima.de/issues/18)
* **archive:** Korrigiere Sortierung von KapMitel-IDs mit Komma als Dezimaltrennzeichen ([da3c2b2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/da3c2b29e5ec58308e635db98fd7ecd3f147d6ee)), closes [#18](https://github.com/RaptorXilef/twokinds.4lima.de/issues/18)
* **archive:** Rendere HTML-Tags in Kapitelbeschreibungen ([185a3cb](https://github.com/RaptorXilef/twokinds.4lima.de/commit/185a3cbbb1bc153b1260fcd8929770a59f903ee5)), closes [#21](https://github.com/RaptorXilef/twokinds.4lima.de/issues/21) [#18](https://github.com/RaptorXilef/twokinds.4lima.de/issues/18)
* **AssetManager:** Aktiviere Autoload und Import der benötigten Bibliotheken ([5ecba05](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5ecba0542f432262a8cea6c303dd43ba98f0cc91))
* **assets:** Behebe Sass [@import](https://github.com/import)-Warnung durch Laden von Fonts via HTML ([99248d2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/99248d2ded602e8cfdd0245437fb64c722eaa769))
* Behebt CORS- und CSP-Fehler beim Laden von Originalbildern ([0b25ca8](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0b25ca8b6af397f26aacc5a59ec6b6520fa03176))
* Behebt das Problem, dass die comic_var.json nicht geladen wird und das Navigationsmenü ausgegratt bleibt. ([d0a1a94](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d0a1a943d3fff821fd5c4a6a42b1e33d2b2da83e)), closes [#2](https://github.com/RaptorXilef/twokinds.4lima.de/issues/2)
* Behebt fehlenden Summernote-Editor auf Index-Seiten ([7499371](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7499371fd2fd9c6e40327b85cee89957b8ceac39))
* Behebung von PHP-Fehlern in generator_rss.php und Code-Verbesserung ([96c51c4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/96c51c41d7b7fadaf9fc2edd661dccb62165c939))
* **bookmarks:** Korrigiere Sortierung der Lesezeichen durch Cache-Invalidierung ([80b2cff](https://github.com/RaptorXilef/twokinds.4lima.de/commit/80b2cff4f28f23e43dda9272e86183cbaae4120b)), closes [#28](https://github.com/RaptorXilef/twokinds.4lima.de/issues/28)
* **charaktere-editor:** Behebe Darstellungsfehler und repariere Bild-Platzhalter und fügt Drag'nDrop zu Charaktergruppen hinzu ([ed9e4aa](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ed9e4aacf5d4965806c587cc0c20dc8be265c8c3))
* **charaktere-editor:** Behebe Design-Bug in der Button-Anzeige ([ba320c2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ba320c24dfdf4bfb7181aaf1357163ebaf81aaea))
* **charaktere-editor:** Behebe Fehler beim Laden der Tabelle und verbessere Button-Layout ([158cb94](https://github.com/RaptorXilef/twokinds.4lima.de/commit/158cb94f18e8df7d9724c6838f158693c6bc798f))
* **charaktere:** Korrigiere Charakter-Profilbild-URL nach Konfigurationsverschiebung ([a104540](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a104540f63968eadd8733d7d5eccf898e4d30121))
* **Charaktere:** Renderer für individuelle PHP-Seiten korrigieren ([63e61d5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/63e61d511fd14d6e35d6c4ac0562faa00d644904))
* **charaktere:** Verbessere Platzhalter-Anzeige auf Charakter-Seiten ([13f78e6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/13f78e6b099af17cc80c1dfede19cb2ddbcfd83e))
* **ci:**  update package and composer lock ([be90c7e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/be90c7e81732b894deb35536e869775ddd152373))
* **ci:** Aktualisierung der PHP-Version auf 8.4 in CI-Workflow ([45fe3ff](https://github.com/RaptorXilef/twokinds.4lima.de/commit/45fe3ff40134b72455bf926ae752f39e7890e0c0))
* **ci:** html lint ([0e6bc8e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0e6bc8e991e0c0a0aa0d9202cd3952620516b3ba))
* **ci:** PHP-Version auf 8.3 aktualisiert ([5be887f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5be887f759a4335223bb14f3032b2ccb12f8e9c1))
* **ci:** PHP-Version in CI-Workflow und Abhängigkeiten auf 8.3 aktualisiert ([2d51d72](https://github.com/RaptorXilef/twokinds.4lima.de/commit/2d51d72ff49d9db7290d1029403448fbee356f87))
* **comic-data-editor:** Behebung Fatal Error und Formularfeld-Breite ([480188e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/480188e48898c49820cf73029f2c8a6d4b7dbb1e))
* **comic-data-editor:** Behebung persistenter Löschungen ([e727149](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e727149a8e205d92f27f54ce713f63effbedaee3))
* **comic-data-editor:** Design-Anpassungen und Behebung von Speicherfehlern ([6e14c65](https://github.com/RaptorXilef/twokinds.4lima.de/commit/6e14c658c3c2adc136f67e14971cdd27660abe1b))
* **comic-data-editor:** Ersatz von TinyMCE durch Summernote und Fehlerbehebungen ([7d9e625](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7d9e625f3e8946a3ea0e1b4a909738588644db22))
* **comic-data-editor:** Paginierungs-Diagnose und UI-Anpassung ([71e023d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/71e023da929f0b40ae0029ccd356bc8d533751a4))
* **comic-data-editor:** Sicherstellung der Anzeige aller JSON-Einträge und Paginierungsdiagnose ([c9ced6c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c9ced6cfd3bf98cd34563d4016862f4cbf287b51))
* **comic-editor:** Ändere die Standardansicht der Bildvorschau zu "Nur Vorschau" ([f9a3a61](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f9a3a61cf57f7e50002c32d2515f6f7864e1a9e3))
* **comic-editor:** Behebe Anzeige- und Zustandsprobleme bei Transkript-Feldern ([922a68e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/922a68e6119ba70498fabdb324f422d3f39f742d))
* **comic-editor:** Behebe Word-Formatierungsprobleme beim Einfügen ([91097b3](https://github.com/RaptorXilef/twokinds.4lima.de/commit/91097b31f4fca62844718d822923bcb855fc26e9))
* **comic-editor:** Behebe Zustandsprobleme bei der gerenderten Transkript-Ansicht ([efcf481](https://github.com/RaptorXilef/twokinds.4lima.de/commit/efcf481c57f5931b9289b40405836a095990a68d))
* **comic-editor:** Bereinige "MsoNormal"-Klasse beim Einfügen aus Word ([ae8a005](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ae8a00589e543370bb7c7a7a75eaa6e5d8d37ac2))
* **comic-editor:** Bereinige Word-Inhalte beim Einfügen in Summernote (Test-Commit) ([506f910](https://github.com/RaptorXilef/twokinds.4lima.de/commit/506f910e9e5cac7568b08eb84ac8a452212298cf))
* **comic-editor:** Korrigiere Anzeige des Status für Thumbnails im Bericht ([6395aec](https://github.com/RaptorXilef/twokinds.4lima.de/commit/6395aec107f15ab3d31e6425fcf293f919a585fd))
* **comic-editor:** Korrigiere Anzeige des Status für Thumbnails im Bericht ([4416955](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4416955e9d1e4a183641071173fa9705689913f5))
* **comic-editor:** Korrigiere Design-Fehler bei Summernote-Tooltips ([4c80e91](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4c80e91213cefe8aea1e7b01066fe835e516ec7e))
* **comic-editor:** Korrigiere Fehler in der Seitenberechnung nach dem Speichern ([cf150d9](https://github.com/RaptorXilef/twokinds.4lima.de/commit/cf150d9b9c1653c240905f72205274471dba4f6f)), closes [#25](https://github.com/RaptorXilef/twokinds.4lima.de/issues/25)
* **comic-editor:** Korrigiere JSON-Formatierung und füge Datumsvariable hinzu ([83e77bf](https://github.com/RaptorXilef/twokinds.4lima.de/commit/83e77bf245a63c5f0421ba6402670724b40a900b))
* **comic-editor:** Korrigiere Seitenberechnung durch Synchronisierung der Datenbasis ([e4a90cc](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e4a90cc875d165c73351bb036b8562368946037e)), closes [#25](https://github.com/RaptorXilef/twokinds.4lima.de/issues/25)
* **comic-editor:** Korrigiere Seitenberechnung und erzwungenes Neuladen ([168429e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/168429e0e80feadd08e77ba613727f58f459de3f))
* **comic:** Behebe mehrere Bugs bei der Bearbeitung von Comic-Daten ([c34443d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c34443d224784e18188488e2354045c93d728d3e))
* **composer:** Repariert rector scripts ([572db52](https://github.com/RaptorXilef/twokinds.4lima.de/commit/572db529756829fe4d4bce2a05cd975805b8e5b7))
* **Core/JS:** Korrekte Meldung bei Session-Timeout (Client-seitig) ([d6a4514](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d6a451481f9a83a19be94e1252bf59c7fbbc4130))
* **Core:** Optimierung der init_admin.php für korrekte Redirects und API-Handling ([5d3cb51](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5d3cb51a1f67a16504ccfb5f5b7c01a11e0dd622))
* **Core:** Stabilisierung der Logout-Logik und Session-Timeouts ([9dc8df1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9dc8df131f612d9a283b125f706cc592327bc8a1))
* **csp:** Behebe CSP-Fehler in admin/index.php und erhöhe Version ([66fcc79](https://github.com/RaptorXilef/twokinds.4lima.de/commit/66fcc79df8abb6b73adc8faf78b4b108f0c5c755))
* **csp:** Behebe CSP-Fehler und füge jQuery-Quelle hinzu ([8fd428c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8fd428c6fb800b1c60fed0445bf41c8cff2ce178))
* **csp:** Behebe CSP-Verstoß in charaktere.php und erhöhe Version ([cc3ac8e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/cc3ac8ebd438fb45f730ed4c53615cbe987ee1d1))
* **csp:** Behebe CSP-Verstoß in charaktere/index.php ([0511b04](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0511b0478b40e8f742c5200f55b32f384dc3d356))
* **csp:** Behebe CSP-Verstoß in datenschutzerklaerung.php und erhöhe Version ([975bfbc](https://github.com/RaptorXilef/twokinds.4lima.de/commit/975bfbc2d1a5f335e2b4758da42814a3fe09e48c))
* **csp:** Behebe CSP-Verstoß in generator_thumbnail.php und erhöhe Version ([eac2dd8](https://github.com/RaptorXilef/twokinds.4lima.de/commit/eac2dd82878915200fbcc45c7e2acc1c60f48fed))
* **csp:** Behebe CSP-Verstoß in lesezeichen.php und erhöhe Version ([893ed88](https://github.com/RaptorXilef/twokinds.4lima.de/commit/893ed8843f7ea5cfd7f2db1ca4d080ffce21b8eb))
* **csp:** Behebe CSP-Verstoß in rss_anleitung.php und erhöhe Version ([f2f67ce](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f2f67ceaae515f36a8569695cd19692d49a02b9c))
* **csp:** Behebe CSP-Verstoß in upload_image.php und erhöhe Version ([231898e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/231898ed54429c9558734c4d9d7be72848f43fe9))
* **csp:** Behebe CSP-Verstöße durch Inline-Styles und erhöhe Version ([775f9a5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/775f9a5132b6661f9308f3eb6a9922fcc250b74c))
* **csp:** Behebe CSP-Verstöße in impressum.php und erhöhe Version ([7d45f00](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7d45f00a57fdeac25be518f7d4f4edfbadeb25c0))
* **csp:** Behebe Konnektivitäts-Fehler in admin_init.php ([3dfe0c5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3dfe0c54a4fffe3ac5cd578f1bbe44230713a969))
* **csp:** Behebe Mixed-Content- und CSP-Fehler ([bdff0fe](https://github.com/RaptorXilef/twokinds.4lima.de/commit/bdff0feee9f2eebbda5f606642036a10cda302eb))
* **csp:** Erweitere CSP um cdn.jsdelivr.net ([0e35d43](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0e35d43749fc2c9d896e26bdf8b82ae0131b5492))
* **csp:** Füge Tailwind CSS zur CSP-Whitelist hinzu für impressum.php ([b4eb9a1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b4eb9a13905ead874c8acdfa943501f4846ec86a))
* **csp:** Füge twokindscomic.com zur CSP-Whitelist hinzu ([c20f98a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c20f98a23dfef17582d7d4f77b4a71261b96490d))
* **csp:** Korrigiere fehlende URLs in admin_init.php ([f8eb18b](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f8eb18bb7da28c945b9810fcfc9c45b2839d491e))
* **css:** Korrigiere Unicode-Zeichen für Hamburger-Icon ([192c255](https://github.com/RaptorXilef/twokinds.4lima.de/commit/192c255e1e263de525549126c186cdf84006732f))
* **data_editor_archiv:** Übergangslösung für das Sumemrnote Problem ([f991240](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f99124085857f709ba82bb794e19b7b28b252b46)), closes [#22](https://github.com/RaptorXilef/twokinds.4lima.de/issues/22)
* **data_editor_comic:** Behebe Fehler in der Charakterbild-Anzeige ([227acf9](https://github.com/RaptorXilef/twokinds.4lima.de/commit/227acf9e418c215235f8e739ffce9a1b081c3347))
* **data_editor_comic:** Übergangslösung für das Sumemrnote Problem ([0ec0242](https://github.com/RaptorXilef/twokinds.4lima.de/commit/0ec024209b4e3686205ba9bcff47f2bed7c263ac))
* **data-editor-charaktere:** Behebe alle bekannten Bugs und verbessert das UI ([aba9678](https://github.com/RaptorXilef/twokinds.4lima.de/commit/aba9678dbdfd747c806bccf24957fb73f847c0c3))
* **data-editor-comic:** Behebe Fehler in der Charakteranzeige im Bearbeitungsmodus ([9400f19](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9400f19494b8ed592378542e9e882fd80962c73e))
* Datumsformat auf Comicseiten-Überschrift auf Deutsch geändert ([7cf8572](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7cf857283bdf1fb887d6873e350fc7db2142f0c5))
* Datumsformat im Seitentitel (Browser-Tab) und H1-Überschrift angepasst ([a846510](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a84651016811a61700043df4f08f3d3dc9d5bcda))
* **dependencies:** Aktualisierung der Paketversionen in composer.lock und package-lock.json ([7409427](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7409427236df60e5a30afa48ef7a9aa1fdbcb8ef))
* **dependencies:** Erhöhung der PHP-Version auf >=8.4 in composer.json und composer.lock ([d6fc403](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d6fc4031da9997ae8bb717ef058eb50bb1a30785))
* **deps:** npm-Abhängigkeiten via Overrides bereinigt ([23c3371](https://github.com/RaptorXilef/twokinds.4lima.de/commit/23c337171e354a0afdcb980fe1811ddfd2c42425))
* **deps:** PHP_CodeSniffer auf Version ^3.13.5 aktualisiert ([6c17588](https://github.com/RaptorXilef/twokinds.4lima.de/commit/6c175882f267fd9852758d001cb20a52ea580374))
* **deps:** phpunit und zusätzliche PHP_CodeSniffer-Standards hinzugefügt ([7a11a0c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7a11a0cd9bdb03ce0edd57a60e4fa7163401d313))
* **design:** Behebe CSS-Designfehler in initial_setup.php und erhöhe Version ([fc0f6ae](https://github.com/RaptorXilef/twokinds.4lima.de/commit/fc0f6aead5f90ba8d50d90f037f9cc56eff84c9c))
* **design:** Dark Mode Anpassung für Bildgeneratoren finalisiert ([91477af](https://github.com/RaptorXilef/twokinds.4lima.de/commit/91477af30a9127076000ea4afdc09770bb7e127f))
* **Design:** Tabellen-Zeilenkontrast im Light Mode erhöht ([78231cd](https://github.com/RaptorXilef/twokinds.4lima.de/commit/78231cdc3a7541954982455cdebf9bf9281db0f3)), closes [#f8f9](https://github.com/RaptorXilef/twokinds.4lima.de/issues/f8f9) [#e9](https://github.com/RaptorXilef/twokinds.4lima.de/issues/e9)
* **design:** Vereinheitliche Design von upload_image.php und behebe Fehler ([6d74572](https://github.com/RaptorXilef/twokinds.4lima.de/commit/6d74572db892d6afd54ee10ecbe448fb38320dc1))
* **editor:** Behebe Fehler beim Speichern in Editoren ([aa0c79d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/aa0c79dfc75c8f463616fd888662246c19693215))
* **editor:** Behebe kritische Fehler im Comic-Daten-Editor und erhöhe Version ([d350c19](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d350c192c1d1f6b848d96ed55547a64f5cc52c58))
* **favicon:** Behebe Fehler beim Laden von Favicons ([63a9cd8](https://github.com/RaptorXilef/twokinds.4lima.de/commit/63a9cd8206946113858aed06fdb770375b4beb7e))
* Fehler bei Ordnerpfaden behoben & Platzhalterfunktion für Bilder verbessert ([c1d4394](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c1d43946a7aec34aa69f7648ba7ca48641929ac7))
* **font:** Füge twokinds.4lima.de zur sicheren Domain für Schriftarten hinzu ([95364b5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/95364b50736067e477ca87cefdcb7a95bd642514))
* **form:** Füge autocomplete-Attribute zu Anmeldeformularen (admin index.php) hinzu ([5587c80](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5587c8001c1348f53d1b3de664faa9f76e55963d))
* **general:** Behebe kleine Fehler in CSS und JS ([14b9877](https://github.com/RaptorXilef/twokinds.4lima.de/commit/14b987745554168752a50a4b4f21a099f2431c04))
* **hotlink:** Erlaube Hotlinking auf localhost ([804dfdd](https://github.com/RaptorXilef/twokinds.4lima.de/commit/804dfddd34d0827e1fb6a2a22f24063f4d7dd8c2))
* **js-loading-theme:** Behebung von Pfadproblemen und Deaktivierung der 'i'-Taste zur Theme-Umschaltung ([d1ce4ff](https://github.com/RaptorXilef/twokinds.4lima.de/commit/d1ce4ffaf11e94cc3128bc56719674184288f74d))
* **js:** Behebe Konsole-Warnung und optimiere JavaScript-Logik ([7287ec8](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7287ec8a9c3e58e450da40939e7d63ff0e2081e7))
* **keep_alive:** Behebe API BUG ([2e958da](https://github.com/RaptorXilef/twokinds.4lima.de/commit/2e958da8900ea51c5b10516ba93e4cbdcb749f6e))
* Kritische Fehler in Charakter- & Comic-Editor beheben ([5e66cff](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5e66cff68da60d45a8376bf6b942c0aaddf96f94))
* **Layout:** Content-Overflow und HTML-Struktur final behoben ([8c37dc6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/8c37dc656de73a9ce3170d090f0b6ace1d5a7d62))
* Lesezeichen-Design und Funktionalität korrigiert ([48865a5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/48865a5b84be78ef83366e9e997290f00a9d1186))
* **lesezeichen:** Korrigiere Hover-Text und Design der Lesezeichen-Seite ([23dbfb0](https://github.com/RaptorXilef/twokinds.4lima.de/commit/23dbfb08e9d29cf759743da86464e133c8c823a2))
* Lesezeichenvorschaubilder auf korrekte Größe angepasst ([117cece](https://github.com/RaptorXilef/twokinds.4lima.de/commit/117cece12aadfd29975ab6e9cabfdc80fca6351c)), closes [#6](https://github.com/RaptorXilef/twokinds.4lima.de/issues/6) [#6](https://github.com/RaptorXilef/twokinds.4lima.de/issues/6)
* **licence:** Korrigiere Mixed-Content-Fehler in lizenz.php ([c56d503](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c56d503637a8870a251ecb03ba9d27bdfe6ef975))
* **Login:** Behebt Race Condition beim Speichern der Session ([3f04188](https://github.com/RaptorXilef/twokinds.4lima.de/commit/3f041884d29f2f02949ee5ef9738bc72f81145e3))
* Logout-Funktion im Adminbereich für alle Seiten korrigiert ([9d93b70](https://github.com/RaptorXilef/twokinds.4lima.de/commit/9d93b70ae729bc3542409834b56a3dc0e342ce26))
* **logout:** Korrigiere Logout-Funktionalität in Management-Seiten ([e5bd6dc](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e5bd6dcafc734e099c37459635fe40c125604eb9))
* **menu:** Füge visuelles Feedback beim Kopieren des RSS-Links hinzu ([36b2e12](https://github.com/RaptorXilef/twokinds.4lima.de/commit/36b2e12b42a953d6172c906271bbbed8c60048a8)), closes [#59](https://github.com/RaptorXilef/twokinds.4lima.de/issues/59) [#57](https://github.com/RaptorXilef/twokinds.4lima.de/issues/57) [#51](https://github.com/RaptorXilef/twokinds.4lima.de/issues/51)
* **menu:** Korrigiere falsche Verlinkung im Menü auf Comic-Seiten ([a442fc5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a442fc5e2b8f91c6c677cc1bd681bd64c08991de)), closes [#4](https://github.com/RaptorXilef/twokinds.4lima.de/issues/4) [#4](https://github.com/RaptorXilef/twokinds.4lima.de/issues/4)
* **meta:** Füge Bugfix-Referenzen hinzu ([4529d7f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4529d7f36d65018db81bc071afe4c33cf500c882)), closes [#65](https://github.com/RaptorXilef/twokinds.4lima.de/issues/65) [#48](https://github.com/RaptorXilef/twokinds.4lima.de/issues/48)
* **navigation:** Doppelte Deklaration von renderNavLink aufgelöst ([4c238fa](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4c238fa51323802ad78c53d90c9d8dc67dcfb4a8))
* **navigation:** Menülinks dynamisch an Basis-URL angepasst ([47e0907](https://github.com/RaptorXilef/twokinds.4lima.de/commit/47e090735d7f991d76ae8686025039c8d0fb43dc))
* **navigation:** Navigation mit baseUrl vereinheitlicht und Fehler behoben ([e6580ba](https://github.com/RaptorXilef/twokinds.4lima.de/commit/e6580bac4a641211bbcf880b925cf7884bb7935e))
* **pagination:** Korrigiere und erweitere die Paginierung des Comic-Editors ([5ef348c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5ef348c87f3b221613585e65f4b0642f084eac87)), closes [#64](https://github.com/RaptorXilef/twokinds.4lima.de/issues/64)
* **path:** Korrigiere Pfad zu keep_alive.php ([cfcd063](https://github.com/RaptorXilef/twokinds.4lima.de/commit/cfcd063e9af8aead3d44662762361d96678a16f4)), closes [#39](https://github.com/RaptorXilef/twokinds.4lima.de/issues/39)
* **path:** Korrigiere Pfad zur version.json ([4d20cbf](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4d20cbf0b069e807390cdbef759e352ab0168d45))
* **php:** Behebung der "Constant already defined"-Warnung ([a4ff977](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a4ff977df212490f7ab290e4e87aadb9b221e8c3))
* **renderer:** Automatisiere Cache-Buster für `comic.js` in `comic_page_renderer.php` ([311fb8e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/311fb8e0e21b2012e5b7cbde129186b0e5bc3b1d))
* **renderer:** Trenne Pfadlogik für Lesezeichen- und Social-Media-Vorschaubilder ([b2f0dfa](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b2f0dfa0b9e351266cdfe066c6ac11c7aaf484fa)), closes [#27](https://github.com/RaptorXilef/twokinds.4lima.de/issues/27)
* Report-Modal im Adminbereich für HTML-Transkripte anpassen ([22163a5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/22163a50224878bbc42e1463217eb828e4b8902a))
* **rss:** Behebe Designfehler in generator_rss.php und erhöhe Version ([03c03e4](https://github.com/RaptorXilef/twokinds.4lima.de/commit/03c03e4b5c87e12e12fb7f9f2103d533d46a6b79))
* **rss:** Korrektur der Basis-URL, anklickbare RSS-URL und Bildvorschau ([fb5cb3f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/fb5cb3f1e3b931646a6d0574f0ecdd8d2e8f9087))
* **rss:** Korrigiere Pfad zum RSS-Icon in rss_anleitung.php ([29557bc](https://github.com/RaptorXilef/twokinds.4lima.de/commit/29557bcfba76da85350d57459cac85a69ec3e974))
* **rss:** RSS-Datum aus Bild-Zeitstempel lesen ([5b15a37](https://github.com/RaptorXilef/twokinds.4lima.de/commit/5b15a37e1a79d01c28cfbfd285fd5b510c1b35da))
* **security:** Korrigiere Timer-Logik und integriere Initial-Setup-Seite ([25737b1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/25737b1dfe4689b744c033a0e672fb954fa52c88)), closes [#39](https://github.com/RaptorXilef/twokinds.4lima.de/issues/39)
* **session:** Behebe Cookie-Probleme und härte das Session-Management ([c2098c8](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c2098c8f05b41706db491aa8a6c9bcac88abe1a6)), closes [#76](https://github.com/RaptorXilef/twokinds.4lima.de/issues/76)
* **sitemap-editor:** Korrekte Dateiexistenzprüfung basierend auf `path` und `name` ([b9a7622](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b9a762243f4fabb2e4374f321170ef05bc1fd51d))
* **sitemap-editor:** Verbessertes Dropdown und Spaltenumbenennung ([a8c1acb](https://github.com/RaptorXilef/twokinds.4lima.de/commit/a8c1acb4d1b53c2ebfde73ffbfa3486f96b676a6))
* **sitemap:** Aggressive Pfad-Bereinigung (Regex) gegen "/./" Fehler. ([281444b](https://github.com/RaptorXilef/twokinds.4lima.de/commit/281444b7a180e5179f88a05fca0def3499878d87))
* Social-Media-Vorschauen in Comic-PHPs auf höhere Auflösung umgestellt ([ce0976a](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ce0976a822a752380b6bcf38e6b5664cc19dac11))
* **social-media:** Korrigiere die Logik für Vorschaubilder ([814ed5e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/814ed5e2855f36e3cdba7301f713c136fdf3a315))
* **style:** Layout und Formular-Design der Anmeldedaten-Verwaltung korrigiert ([932c178](https://github.com/RaptorXilef/twokinds.4lima.de/commit/932c17802fae9f8adc854fb3c40d3ddc31086a16))
* **style:** Upload-Dropzone Design und Modal-Bildanzeige korrigiert ([4cc2679](https://github.com/RaptorXilef/twokinds.4lima.de/commit/4cc26799e3fb0cad1f882df9595d9fdc67c4159e))
* **style:** Zentrierung des Upload-Icons und Erweiterung der Erfolgsmeldung ([7b3160c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7b3160c9cd3c0213b9cc0ffe65c78927672d5648))
* **thumbnails:** Korrekte Auflösung für Thumbnail-Generierung ([b1e524e](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b1e524ec3e9af433753da3b4506fa003bd2c06a6))
* **timeout:** Behebe Session-Timeout-Bug und erhöhe Version ([b4d69b3](https://github.com/RaptorXilef/twokinds.4lima.de/commit/b4d69b3d6e6cc1a9b5fe124bf41e935b0cd16a5d)), closes [#50](https://github.com/RaptorXilef/twokinds.4lima.de/issues/50)
* **timeout:** Zentralisiere Session-Timeout-Logik in keep_alive.php ([bd26f5c](https://github.com/RaptorXilef/twokinds.4lima.de/commit/bd26f5ce7fbcd42d36e67bcb6c6659f82e85f6a3))
* **titles:** Konsistente Titelformatierung "Typ vom Datum" auf allen Comic-Seiten ([c2bf209](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c2bf209ac05c10cbbb15e803dbbd0e153567fe60))
* **typo:** Behebe Rechtschreibfehler in Dateinamen ([ab11f0d](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ab11f0d39ed7ab2e492f91c85b0e6ecdfdb668ad))
* **typo:** Korrigiere Schreibfehler in der Ordnerstruktur ([7a7ead2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7a7ead2faa2a6eb6f28437ccea91c84ee8be8a38))
* **ui:** Ändere Standardverhalten der Comic-URL-Checkbox ([ba10355](https://github.com/RaptorXilef/twokinds.4lima.de/commit/ba10355211d04c18fa09f00c24d6241d88252dc4))
* **ui:** Behebung des Layout-Fehlers im Admin-Bereich ([06094a6](https://github.com/RaptorXilef/twokinds.4lima.de/commit/06094a6fb1260e75e8de3a4d9137ef3dc91a896e))
* **ui:** Korrektur und Vereinheitlichung der Generierungs-Buttons ([7aa38f2](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7aa38f28d7c3f934f57683afeaa45862896506e5))
* **ui:** Sichtbarkeit und Positionierung der Buttons im Bildgenerator korrigiert ([692b1b1](https://github.com/RaptorXilef/twokinds.4lima.de/commit/692b1b175cd77e21d4311710245293e21bac625f))
* **ui:** Verbesserungen der Bildgeneratoren für Dark Mode und UX ([96510e5](https://github.com/RaptorXilef/twokinds.4lima.de/commit/96510e560bd339608e027090b692c34f70b49f91))
* **upload-image:** Behebe Design-, Pfad- und Logikfehler in upload_image.php ([7eeeb58](https://github.com/RaptorXilef/twokinds.4lima.de/commit/7eeeb58f3ccdb7b35979a51fd4c40d18211a7790))
* URL-Routing für Charakternamen mit Leerzeichen korrigiert ([648ab97](https://github.com/RaptorXilef/twokinds.4lima.de/commit/648ab97913e99e3cc926d22e34d9c1e4afd7e505))
* **webp:** Behebe Fehler bei der WebP-Generierung in der Produktionsumgebung ([f2e1eda](https://github.com/RaptorXilef/twokinds.4lima.de/commit/f2e1edaf9fa4f9d3204c8d1191a50d3dd21c9704)), closes [#43](https://github.com/RaptorXilef/twokinds.4lima.de/issues/43)


* **security:** Implementiere Nonce-basierte CSP und umfassenden CSRF-Schutz ([c842412](https://github.com/RaptorXilef/twokinds.4lima.de/commit/c8424129cbf47e85607f3fc2aebeef6eb400a30b)), closes [#44](https://github.com/RaptorXilef/twokinds.4lima.de/issues/44) [#45](https://github.com/RaptorXilef/twokinds.4lima.de/issues/45)


### build

* **assets:** Kompilierung der vereinheitlichten main.css und Löschung alter Stylesheets ([1fc499f](https://github.com/RaptorXilef/twokinds.4lima.de/commit/1fc499f594bfc66edd1e95328a4d8d2e5e259f94))
