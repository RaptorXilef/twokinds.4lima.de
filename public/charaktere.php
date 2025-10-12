<?php
/**
 * Diese Datei zeigt Details zu den Charakteren des Comics an.
 * Version 2.0: Vollständig an die neue, sichere Architektur mit
 * public_init.php und einem zentralen Bild-Helfer angepasst.
 * 
 * @file      ROOT/public/charaktere.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     2.0.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../src/components/public_init.php';

// === 2. LADE-SKRIPTE & DATEN (Jetzt mit der Path-Klasse) ===
require_once Path::getComponent('character_image_helper.php');

// === 3. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Charaktere';
$siteDescription = 'Lerne die Hauptcharaktere von TwoKinds kennen. Detaillierte Biografien und Referenzbilder von Trace, Flora, Keith, Natani und vielen mehr.';
$robotsContent = 'index, follow';

// Füge die charaktere.js mit Cache-Busting und Nonce hinzu
$charaktereJsPathOnServer = DIRECTORY_PUBLIC_JS . DIRECTORY_SEPARATOR . 'charaktere.js';
$charaktereJsWebUrl = Path::getJsUrl('charaktere.js');
$cacheBuster = file_exists($charaktereJsPathOnServer) ? '?c=' . filemtime($charaktereJsPathOnServer) : '';

$additionalScripts = '
    <script nonce="' . htmlspecialchars($nonce) . '">
        window.phpDebugMode = ' . ($debugMode ? 'true' : 'false') . ';
    </script>
    <script nonce="' . htmlspecialchars($nonce) . '" type="text/javascript" src="' . htmlspecialchars($charaktereJsWebUrl . $cacheBuster) . '"></script>
';

// CSS für das initiale Ausblenden der Lazy-Load-Bilder und die Korrekturen für die Bösewichte-Sektion
$additionalHeadContent = '
    <style nonce="' . htmlspecialchars($nonce) . '">
        img.lazy-char-img {
            opacity: 0;
            transition: opacity 0.5s ease-in;
        }
        img.lazy-char-img.loaded {
            opacity: 1;
        }
        .villain-image-container {
            margin-bottom: 10px;
        }
        .villain-info {
            width: 800px;
        }
        .villain-group-image {
            width: 800px;
        }
    </style>
';

// === 4. HEADER EINBINDEN (Jetzt mit Path-Klasse) ===
require_once Path::getTemplatePartial('header.php');
?>
<header hidden>
    <h1 class="page-header">Charaktere</h1>
</header>
<section>
    <div class="char-head">
        <!-- Diese kleinen Icons werden direkt von der Originalseite geladen -->
        <a href="#trace"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_trace.gif" alt="Trace"></a>
        <a href="#flora"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_flora.gif" alt="Flora"></a>
        <a href="#keith"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_keith.gif" alt="Keith"></a>
        <a href="#natani"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_natani.gif" alt="Natani"></a>
        <a href="#zen"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_zen.gif" alt="Zen"></a>
        <a href="#sythe"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_sythe.gif" alt="Sythe"></a>
        <a href="#mrsnibbly"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_nibbly.gif"
                alt="Nibbly the Great and Powerful"></a>
        <a href="#raine"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_raine.gif" alt="Raine"></a>
        <a href="#laura"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_laura.gif" alt="Laura"></a>
        <a href="#saria"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_saria.gif" alt="Saria"></a>
        <a href="#eric"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_eric.gif" alt="Eric"></a>
        <a href="#kathrin"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_kathrin.gif" alt="Kathrin"></a>
        <a href="#mike"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_mike.gif" alt="Mike"></a>
        <a href="#evals"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_evals.gif" alt="Evals"></a>
        <a href="#maddie"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_maddie.gif"
                alt="Madelyn Adelaide"></a>
        <a href="#maren"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_maren.gif" alt="Maren"></a>
        <a href="#karen"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_karen.gif" alt="Karen"></a>
        <a href="#red"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_red.gif" alt="Red"></a>
        <a href="#alaric"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_alaric.gif" alt="Alaric"></a>
        <a href="#nora"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_nora.gif" alt="Nora"></a>
        <a href="#reni"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_reni.gif" alt="Reni"></a>
        <a href="#adira"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_adira.gif" alt="Adira"></a>
        <a href="#maeve"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_maeve.gif" alt="Maeve"></a>
        <a href="#mask"><img src="https://cdn.twokinds.keenspot.com/img/faces/icon_mask.gif" alt="Maske"></a>
    </div>
    <header>
        <h2 class="page-header">Hauptcharaktere</h2>
    </header>

    <section class="char-detail" id="trace">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Trace2025', 'portrait'); ?>" alt="Trace"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/TraceSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/trace-reference-28691421" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('traceref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
            <a href="https://www.patreon.com/posts/tiger-trace-22635887" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('tigertraceref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Trace Legacy</h3>
            <p>
                <b>Geschlecht:</b> Männlich<br>
                <b>Alter:</b> 24<br>
                <b>Klasse:</b> Großtempler-Magier<br>
                <b>Spezies:</b> Mensch<br>
                <b>Sprachen:</b> Mensch<br>
            </p>
            <p><b>Persönlichkeit:</b> Trace ist normalerweise freundlich, schüchtern und ein wenig naiv. Allerdings
                verändert sich seine Persönlichkeit drastisch, wenn er sich an vergangene Erinnerungen erinnert.</p>
            <p>Trace wurde in eine arme Bauernfamilie hineingeboren, doch seine magischen Talente wurden schnell erkannt
                und schon in jungen Jahren wurde er von den Templern rekrutiert. Er stieg in den Rängen auf und übernahm
                schließlich nach dem Tod seiner ersten Frau die Leitung der Organisation als Großtempel. Er wurde
                schnell als Tyrann bekannt, der von Menschen und Keidran gleichermaßen gefürchtet wurde. Seine
                unglaubliche Macht ermöglichte es ihm, alle zu dominieren, die sich ihm widersetzten.</p>
            <p>Allerdings wurden ihm seine Erinnerungen und sein tief verwurzelter Hass auf Keidran gestohlen. Er reist
                jetzt an der Seite von Flora, einer Keidranerin, mit der er sich angefreundet hat, ohne zu wissen, wie
                er einst über sie dachte. Doch langsam kehren seine Erinnerungen zurück und mit ihnen das Wissen um eine
                Vergangenheit, die er lieber vergessen hätte bleiben wollen.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="flora">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Flora2025', 'portrait'); ?>" alt="Flora"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/FloraSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/flora-character-127534701" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('floraref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
            <a href="https://www.patreon.com/posts/flora-ref-sheet-26619874" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('flora-oldref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Flora des Regenwald-Tigerstammes</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> ~11 (26 äquivalent)<br>
                <b>Klasse:</b> Ex-Sklave<br>
                <b>Spezies:</b> Tiger Keidran<br>
                <b>Sprachen:</b> Keidran, Mensch<br>
            </p>
            <p><b>Persönlichkeit:</b> Flora ist kontaktfreudig und willensstark, aber auch romantisch. Seit ihrer
                Kindheit träumt sie davon, die „wahre Liebe“ zu finden.</p>
            <p>Flora wurde in den südlichen Regenwäldern ihres Stammes geboren. Doch im Alter von drei Jahren wurde ihr
                Haus von Sklavenhändlern angegriffen. Ihre Eltern wurden getötet und sie wurde in die Sklaverei
                verkauft. Flora hatte dabei genug Glück, von einer freundlichen Menschenfamilie gekauft zu werden, die
                sich dazu entschloss, sie zur Dienstmagd zu machen, um die Schulden zu begleichen. Sie hat keine
                Erinnerung an den Tag, an dem ihr Stamm angegriffen wurde.</p>
            <p>Flora wurde schließlich vorzeitig aus ihrer Knechtschaft entlassen und durfte zu ihrem Volk zurückkehren.
                Aufgrund ihrer Kenntnis der menschlichen Natur und Sprache wurde sie als Vertreterin des Tigerclans
                ausgewählt und eine Ehe zwischen ihr und einem Prinzen des Wolfclans arrangiert. Die Vereinbarung
                scheiterte jedoch, als ihre Karawane angegriffen wurde. Sie wurde von Trace, der erst kürzlich sein
                Gedächtnis verloren hatte, vor den menschlichen Angreifern gerettet.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="keith">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Keith2025', 'portrait'); ?>" alt="Keith"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/KeithSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/keith-ref-sheet-26845156" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('keithref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Keith Keiser</h3>
            <p>
                <b>Geschlecht:</b> Männlich<br>
                <b>Alter:</b> 19<br>
                <b>Klasse:</b> Verbannter Krieger<br>
                <b>Spezies:</b> Ostbasitin<br>
                <b>Sprachen:</b> Keidran, Mensch, Basitin<br>
            </p>
            <p><b>Persönlichkeit:</b> Keith verhält sich die meiste Zeit ernst oder gleichgültig. Tief im Inneren ist er
                jedoch wirklich fürsorglich und beschützerisch.</p>
            <p>Keith wurde auf den Basidian-Inseln geboren, einer großen Landmasse in der Nähe des Festlandes. Sein
                Vater war General des Militärs. Seine Mutter war Soldatin, wie alle Basitins, aber sie schützte ihn die
                meiste Zeit seiner Kindheit vor dem Militärleben. Wie alle Basitins trat er schließlich im Alter von 8
                Jahren dem Militär bei, was für die meisten Basitins spät war. Keith wurde im Alter von 13 Jahren für
                den Tod seiner Eltern verantwortlich gemacht und von den Inseln verbannt. Ihm wurde befohlen, nicht
                zurückzukehren, bis er den Großtempel zurückgebracht hatte.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="natani">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Natani2025', 'portrait'); ?>" alt="Natani"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/NataniSwatch.gif"
                alt="Color Swatch">
            <a href="https://www.patreon.com/posts/natani-ref-sheet-25812950" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('nataniref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Natani</h3>
            <p>
                <b>Geschlecht:</b> Männlich<br>
                <b>Alter:</b> ~13 (32 äquivalent)<br>
                <b>Klasse:</b> Assassine<br>
                <b>Spezies:</b> Wolf Keidran<br>
                <b>Sprachen:</b> Keidran<br>
            </p>
            <p><b>Persönlichkeit:</b> Dreist, aggressiv und insgeheim einsam. Natani stößt alle bis auf ein paar
                emotional beiseite.</p>
            <p>Der jüngere der Assassinenbrüder. Natani war gezwungen, mit seinem Bruder Zen als Obdachlose zu leben,
                nachdem ihre Eltern und ihr Stamm von menschlichen Templern zerstört worden waren. Aus Verzweiflung
                schlossen er und Zen sich einer Assassinengilde an, um über die Runden zu kommen. Aufgrund eines Unfalls
                während eines Attentats wurde Natani auf magische Weise und dauerhaft mit dem Geist seines Bruders
                verbunden. Sie können nun über ihre Verbindung aktiv die Gedanken und Gefühle des anderen lesen und so
                über große Entfernungen kommunizieren. Diese Verbindung ist zwar ein großer Vorteil auf diesem Gebiet,
                hat aber auch Nachteile. Sie können niemals getrennt werden und es ist unbekannt, was passieren wird,
                wenn einer stirbt.</p>
            <p>Natani hatte nie eine formelle Ausbildung und ist daher Analphabet. Er kann in keiner Sprache schreiben
                und nur Keidran sprechen, obwohl er eine Handvoll menschlicher Wörter verstehen kann. Dennoch tappt
                Natani bei Gesprächen zwischen Spezies oft im Dunkeln.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="zen">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Zen2025', 'portrait'); ?>" alt="Zen"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/ZenSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/zen-ref-sheet-82651895" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('zenref_thumbnail', 'ref_sheet'); ?>" alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Zen</h3>
            <p>
                <b>Geschlecht:</b> Männlich<br>
                <b>Alter:</b> ~14 (35 äquivalent)<br>
                <b>Klasse:</b> Assassine<br>
                <b>Spezies:</b> Wolf Keidran<br>
                <b>Sprachen:</b> Keidran, (sehr wenig) menschlich<br>
            </p>
            <p><b>Persönlichkeit:</b> Unbeschwert und optimistisch, durchaus. Genießt es, sein jüngeres Geschwister
                unermüdlich zu necken.</p>
            <p>Der ältere der Assassinenbrüder. Zen ist sehr beschützerisch gegenüber seinen Geschwistern, der einzigen
                Familie, die er noch hat, nachdem der Angriff beide zu Waisen gemacht hat. Die Brüder schlossen sich
                beide der Assassinengilde an und nutzten ihre einzigartige mentale Verbindung, um ihnen bei der
                Eroberung ansonsten unmöglicher Ziele zu helfen. Trotz des Könnens und des guten Rufs des
                Assassinen-Bruders sterben fast alle Ziele, die sie töten sollen, auf indirekte Weise, oft durch Zufall
                oder auf eine Art und Weise, die das Duo ursprünglich nicht beabsichtigt hatte. Irgendwie scheint ihr
                allgemeines Pech über einen Stellvertreter auf andere abzufärben.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="sythe">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Sythe2025', 'portrait'); ?>" alt="Sythe"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/SytheSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/sythe-reference-34204330" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('sytheref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Sythe</h3>
            <p>
                <b>Geschlecht:</b> Männlich<br>
                <b>Alter:</b> ~11 (27 äquivalent)<br>
                <b>Klasse:</b> Feiger Diplomat<br>
                <b>Spezies:</b> Wolf Keidran<br>
                <b>Sprachen:</b> Keidran, Mensch<br>
            </p>
            <p><b>Persönlichkeit:</b> Extrem pessimistisch und in stressigen Umgebungen leicht ängstlich.</p>
            <p>Sythe ist ein Wolf mit Pferdeschwanz, der sich fernab von Schlachtfeldern und Konflikten am wohlsten
                fühlt. Er wurde von seinem Onkel erzogen und sein ganzes Leben lang zum Diplomaten zwischen Menschen und
                Keidran ausgebildet – er verbrachte sogar über ein Jahr unter Menschen, bevor die Grenzen geschlossen
                wurden. Bedauerlicherweise sind seine perfekte menschliche Sprache und seine jahrelange Ausbildung in
                Kulturen und Politik zwischen den Spezies seit Ausbruch des Krieges völlig verloren gegangen. Intern hat
                Sythe eine sehr negative Einstellung zu seinesgleichen – er glaubt, dass der Krieg größtenteils auf die
                Wölfe zurückzuführen ist, die die Menschen überhaupt provoziert haben. Letztendlich wünscht er sich
                nichts sehnlicher, als dass sein Leben wieder normal wird.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="mrsnibbly">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Nibbly2025', 'portrait'); ?>" alt="Mrs Nibbly"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/MrsNibblySwatch.gif"
                alt="Color Swatch">
        </div>
        <div class="char-info">
            <h3>Mrs. Nibbly</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> ~6<br>
                <b>Klasse:</b> Eichhörnchen<br>
                <b>Spezies:</b> Eichhörnchen<br>
                <b>Sprachen:</b> Eichhörnchen<br>
            </p>
            <p><b>Persönlichkeit:</b> Ein neugieriges kleines Eichhörnchen ohne Selbsterhaltungstrieb.</p>
            <p><i>„Das Leben ist nicht wie einer deiner Romane, Liebes.“ Ihre Freunde würden spotten. „Geh zu einem
                    dieser Monster und wirst bei lebendigem Leibe gefressen!“ Oder zumindest hätten sie das gesagt, wenn
                    Eichhörnchen sprechen könnten. Frau Nibbly wusste jedoch, dass sie eines Tages mehr als eine
                    gewöhnliche Hausfrau sein würde. Ihre nächtlichen Träume waren voller Bilder von Abenteuern und
                    Romantik unter den Riesen. Nachdem sie im Winter zuvor ihren Mann verloren hat, beschließt sie, dass
                    es endlich an der Zeit ist, ihre Träume zu verwirklichen.</i></p>
            <p><i>Obwohl sie verängstigt war, blieb sie standhaft, als sich das zweibeinige Tier näherte, und erlaubte
                    ihm nicht, ihre Angst zu bemerken. Und es war genau so, wie sie es sich immer vorgestellt hatte! Er
                    hat sie angenommen! Als sie auf die Kreatur kletterte, wusste Mrs. Nibbly, dass sich ihr Leben bald
                    ändern würde. Welche Sehenswürdigkeiten würde sie sehen? Sie wusste es nicht und es kümmerte sie
                    auch nicht. All sie wusste war, dass sie es mit ihm teilen würde – ihrem neuen, lebenslangen Freund
                    und Begleiter. Und vielleicht eines Tages... mehr?</i></p>
            <p>Dieser Abschnitt wurde von Raine verfasst.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="raine">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Raine2025', 'portrait'); ?>" alt="Raine"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/RaineSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/raine-reference-25826733" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('raineref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Raine</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> 18<br>
                <b>Klasse:</b> Gestaltwandler / Alchemist<br>
                <b>Spezies:</b> Mensch / Wolf Keidran<br>
                <b>Sprachen:</b> Mensch, Keidran<br>
            </p>
            <p><b>Persönlichkeit:</b> In menschlicher Form ist Raine ein schüchternes und leicht naives junges Mädchen.
                Aufgrund mangelnder Erfahrung als Keidranerin verliert sich Raine jedoch manchmal in der wilden Natur
                ihrer anderen Form.</p>
            <p>Raine ist die Tochter des ehemaligen Großtemplers. Sie wurde unter ungewöhnlichen und unnatürlichen
                Umständen geboren und verfügt daher über unvorhersehbare magische Kräfte. Die wilde Natur ihrer Magie
                führt dazu, dass sie physische Transformationen zwischen Keidran- und menschlichen Formen erlebt. Diese
                Transformationen werden normalerweise durch Artefakte in Schach gehalten, die verzaubert sind, um ihre
                Magie zu unterdrücken. Ihr ganzes Leben lang hat Raines Mutter ihre Keidran-Form vor anderen Mitgliedern
                des Templerordens und sogar vor Raine selbst geheim gehalten. Doch je reifer Raine wird, desto unruhiger
                wird ihre bestialische Seite aufgrund jahrelanger Unterdrückung.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="laura">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Laura2025', 'portrait'); ?>" alt="Laura"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/LauraSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/laura-reference-30562240" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('lauraref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Laura vom Stamm der Küstenfuchse</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> ~16 (44 äquivalent)<br>
                <b>Klasse:</b> Verlobte<br>
                <b>Spezies:</b> Fuchs Keidran<br>
                <b>Sprachen:</b> Keidran, Mensch<br>
            </p>
            <p><b>Persönlichkeit:</b> Laura hat sehr wenig Selbstvertrauen. Sie ist schüchtern, schüchtern und emotional
                zerbrechlich. Sie versucht Konflikte zu vermeiden und stets höflich zu sein.</p>
            <p>Lauras Familie fand Keith hinter ihrem Haus an Land gespült, nachdem er verbannt worden war. Laura
                verliebte sich in Keith und sie sollten heiraten, doch die Ereignisse verschworen sich gegen sie und
                Keith verließ schließlich das Land und begab sich auf menschliches Territorium, dem sie nicht folgen
                konnte. Nach mehreren Jahren beschloss sie, Keith auf die einzige Weise aufzusuchen, die sie kannte:
                indem sie zu den Basitin-Inseln reiste.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="saria">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/Saria.jpg" alt="Saria"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/SariaSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/saria-ref-sheet-60925868" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('sariaref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Saria au Gruhen</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> N/A<br>
                <b>Klasse:</b> Schmied / Freiheitskämpfer<br>
                <b>Spezies:</b> Mensch<br>
                <b>Sprachen:</b> Mensch, Keidran<br>
            </p>
            <p><b>Persönlichkeit:</b> Saria spricht leise und ist normalerweise schüchtern, hat aber keine Angst, sich
                zu äußern, wenn es nötig ist.</p>
            <p>Saria war eine bürgerliche Tochter eines Schmieds. Sie lernte schon früh, auf sich selbst aufzupassen.
                Sie arbeitet lieber für sich selbst und war nie auf die Hilfe von Sklaven angewiesen. Ihr Vater brachte
                sie zur Templerakademie, um an der Herstellung von Rüstungen für die Templer zu arbeiten. Dort lernte
                sie Trace kennen und verliebte sich in sie. Sie waren ein Jahr lang glücklich verheiratet. Ihre Ehe war
                jedoch nur von kurzer Dauer.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="eric">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/Eric.jpg" alt="Eric"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/EricSwatch.gif" alt="Color Swatch">
            <!-- Kein Ref Sheet Link in der alten Datei vorhanden -->
        </div>
        <div class="char-info">
            <h3>Eric Vaughan</h3>
            <p>
                <b>Geschlecht:</b> Männlich<br>
                <b>Alter:</b> 20<br>
                <b>Klasse:</b> Händler / Unternehmer / Sammler<br>
                <b>Spezies:</b> Mensch<br>
                <b>Sprachen:</b> Mensch, Keidran, Basitin<br>
            </p>
            <p><b>Persönlichkeit:</b> Eric mag Keidran. Das fasst es ziemlich gut zusammen.</p>
            <p>Eric wurde in eine Adelsfamilie hineingeboren. Er ist äußerst wohlhabend, versucht aber, bescheiden mit
                seinem Geld umzugehen. Eric hat eine große Affinität zu Keidran und nutzt seinen riesigen Reichtum, um
                in Städten auf dem ganzen Festland verschiedene Keidran-Sklaven zu kaufen. Eric ist sehr belesen, aber
                er ist auf Keidran-Anatomie und -Physiologie spezialisiert. Eric behandelt alle seine Sklaven sehr gut
                und die meisten sind ihm auf ewig ergeben, obwohl sie versklavt sind.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="kathrin">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/Kathrin.jpg" alt="Kathrin"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/KathrinSwatch.gif"
                alt="Color Swatch">
            <a href="https://www.patreon.com/posts/kathrin-ref-26592787" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('kathrinref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Kathrin "Spots" Vaughan</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> ~10 (24 äquivalent)<br>
                <b>Klasse:</b> Schneider<br>
                <b>Spezies:</b> Mischling Keidran<br>
                <b>Sprachen:</b> Mensch, Keidran<br>
            </p>
            <p><b>Persönlichkeit:</b> Kathrin hat eine fröhliche Persönlichkeit. Sie ist in den meisten Dingen etwas
                naiv, da sie ihr ganzes Leben lang als Sklavin untergebracht war.</p>
            <p>Kathrin wurde selektiv aus mehreren Generationen von Keidran-Sklaven gezüchtet, um körperlich
                ansprechender für Menschen zu sein. Ironischerweise ist Kathrin, obwohl sie als Sexsklavin konzipiert
                wurde, unglaublich naiv, wenn es um Sex geht. Eric lässt ihr viel mehr Freiheiten als seine anderen
                Sklaven, aber sie genießt es wirklich, ihm zu dienen.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="mike">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/Mike.jpg" alt="Mike"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/MikeSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/mike-and-evals-37671238" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('mikeandevalsref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Mike</h3>
            <p>
                <b>Geschlecht:</b> Männlich / Weiblich?<br>
                <b>Alter:</b> ~12 (28 äquivalent)<br>
                <b>Klasse:</b> Sklave<br>
                <b>Spezies:</b> Fuchs Keidran<br>
                <b>Sprachen:</b> Mensch, Keidran<br>
            </p>
            <p><b>Persönlichkeit:</b> Er war früher vollkommen zufrieden mit seinem Schicksal, bis er an dem
                schicksalhaften Tag einem Basitin begegnete, der schwarze Magie beherrschte.</p>
            <p>Mike ist einer von mehreren Sklaven, die unter Eric arbeiten. Seine Aufgabe besteht hauptsächlich darin,
                Erics Schiff zu warten. Er ist gut mit Evals befreundet, einem weiteren Sklaven auf Erics Schiff. Obwohl
                Evals größer ist als Mike, ist Mike normalerweise derjenige, der die Führung übernimmt.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="evals">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/Evals.jpg" alt="Evals"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/EvalsSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/mike-and-evals-37671238" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('mikeandevalsref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Evals</h3>
            <p>
                <b>Geschlecht:</b> Männlich<br>
                <b>Alter:</b> ~13 (30 äquivalent)<br>
                <b>Klasse:</b> Sklave<br>
                <b>Spezies:</b> Hund Keidran<br>
                <b>Sprachen:</b> Mensch, Keidran<br>
            </p>
            <p><b>Persönlichkeit:</b> Ein typischer Keidran-Sklave.</p>
            <p>Evals ist einer von mehreren Sklaven, die unter Eric arbeiten. Seine Aufgabe besteht hauptsächlich darin,
                Erics Schiff zu warten. Er ist gut mit Mike befreundet, einem weiteren Sklaven auf Erics Schiff. Obwohl
                Evals größer ist als Mike, ist Mike normalerweise derjenige, der die Führung übernimmt.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="maddie">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/Maddie.png" alt="Madelyn Adelaide"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/MaddySwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/madelyn-ref-34828699" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('madelynref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Madelyn Adelaide</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> ???<br>
                <b>Klasse:</b> Spion<br>
                <b>Spezies:</b> Ostbasitin<br>
                <b>Sprachen:</b> Mensch, Basitin, Keidran<br>
            </p>
            <p><b>Persönlichkeit:</b> Hyperaktiv und lebenslustig, wenn sie nicht im Einsatz ist. Illusorisch und
                stalkerhaft, wenn nötig.</p>
            <p>Das ursprüngliche Stalker Girl. Madelyn Adelaide ist eine der besten Spione, die die Basitin zu bieten
                haben. Mit ihrer geringen Größe und unheimlichen Geschwindigkeit kann sie typischerweise durch jede
                Falle oder jeden Käfig schlüpfen ... solange niemand hinschaut. Wenn sie erwischt wird, ist sie sehr gut
                darin, die Gefühle anderer zu ihrem Vorteil zu manipulieren. Ihr kindliches Aussehen macht sie für
                diejenigen gefährlich, die sie unterschätzen. Im Gegensatz zu den meisten Basitin ist sie jedoch in
                einem Frontalkampf fast völlig nutzlos, da sie auf jegliches Kampftraining für Tarnung verzichtet hat.
            </p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="maren">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/Maren.jpg" alt="Maren"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/MarenSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/maren-and-karen-36114522" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('marenkarenref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Maren Taverndatter</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> 20<br>
                <b>Klasse:</b> Tavern Maid<br>
                <b>Spezies:</b> Mensch<br>
                <b>Sprachen:</b> Mensch<br>
            </p>
            <p><b>Persönlichkeit:</b> Maren ist herrisch und bevorzugt, dass die Dinge nach ihrem Kopf gehen.</p>
            <p>Maren ist die herrische Älteste der Taverndatter-Schwestern. Sie ist eine von Traces aktuellen
                "Freundinnen", obwohl Trace und sie nie ernsthaft involviert waren. Sie trafen sich in ihrer Taverne,
                als Trace während seiner Missionen vorbeikam. Sie wusste, dass Trace seine Frau, die vor Jahren
                verstorben war, immer noch liebte; und sie wusste, dass sie in Wirklichkeit nur von Trace als momentane
                Ablenkung benutzt wurde. Aber sie blieb trotzdem bei ihm.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="karen">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/Karen.jpg" alt="Karen"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/KarenSwatch.gif" alt="Color Swatch">
            <a href="https://www.patreon.com/posts/maren-and-karen-36114522" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('marenkarenref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Karen Taverndatter</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> 16<br>
                <b>Klasse:</b> Tavern Assistant<br>
                <b>Spezies:</b> Mensch<br>
                <b>Sprachen:</b> Mensch<br>
            </p>
            <p><b>Persönlichkeit:</b> Karen ist kontaktfreudig und aufgeregt; sie hasst Langeweile und wird sich alle
                Mühe geben, die Dinge interessant zu machen.</p>
            <p>Karen ist die hyperaktive jüngere der Taverndatter-Schwestern. Sie kümmert sich nicht um Rassen, Kriege
                oder Religionen. Sie genießt einfach interessante Dinge, und wenn nichts Interessantes passiert, neigt
                sie dazu, es zu verursachen. Karens zusätzliche Keidran-Ohren wurden ihr als Fluch von Trace gegeben,
                den sie nervte. Der Fluch ging jedoch nach hinten los, als Karen beschloss, dass sie die Ohren liebte
                und sie behalten wollte.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="red">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/RedHairedGuy.jpg" alt="Red Haired Guy"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/RedHairedGuySwatch.gif"
                alt="Color Swatch">
            <a href="https://www.patreon.com/posts/red-ref-sheet-90801686" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('redref_thumbnail', 'ref_sheet'); ?>" alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Red</h3>
            <p>
                <b>Geschlecht:</b> Männlich<br>
                <b>Alter:</b> 25<br>
                <b>Klasse:</b> Lokale Miliz<br>
                <b>Spezies:</b> Mensch<br>
                <b>Sprachen:</b> Mensch<br>
            </p>
            <p><b>Persönlichkeit:</b> Red ist ein bisschen ein Muskelprotz. Er versucht sein Bestes, um
                alle seine Probleme mit seinem Schwert zu lösen.</p>
            <p>Red war einer von Traces Freunden von der Templerakademie. Allerdings brach er das Studium
                nach ein paar Jahren ab, als er kaum eine magische Entwicklung zeigte. Später schloss er sich der
                örtlichen Miliz seiner Heimatstadt an.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="alaric">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/Alaric.jpg" alt="Alaric"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/AlaricSwatch.gif"
                alt="Color Swatch">
            <!-- Kein Ref Sheet Link in der alten Datei vorhanden -->
        </div>
        <div class="char-info">
            <h3>Nickolai Alaric</h3>
            <p>
                <b>Geschlecht:</b> Männlich<br>
                <b>Alter:</b> 19<br>
                <b>Klasse:</b> Generalmeister<br>
                <b>Spezies:</b> Ostbasitin<br>
                <b>Sprachen:</b> Mensch, Keidran, Basitin<br>
            </p>
            <p><b>Persönlichkeit:</b> Alaric ist ungewöhnlich für einen Basitin, er drückt seine Zuneigung offen aus und
                missachtet Regeln und Gesetze, wann immer es möglich ist.</p>
            <p>Alaric ist der Generalmeister des östlichen Basitin-Militärs. Er ist nach dem König selbst einer der
                mächtigsten Männer der Basitin-Gesellschaft. Obwohl er immer noch nicht in der Lage ist, Gesetze oder
                Regeln der Gesellschaft zu brechen, hat er sie ausreichend studiert, um sie so weit wie möglich zu
                umgehen. Er liebt Keith sehr, mit dem er seit seiner Kindheit befreundet ist.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="nora">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/LadyNora.jpg" alt="Nora"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/LadyNoraSwatch.gif"
                alt="Color Swatch">
            <a href="https://www.patreon.com/posts/lady-nora-ref-26898478" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('noraref_thumbnail', 'ref_sheet'); ?>" alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Lady Nora</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> ~2000<br>
                <b>Klasse:</b> Gestaltwandler<br>
                <b>Spezies:</b> Weißer Drache<br>
                <b>Sprachen:</b> Alle (telepathisch)<br>
            </p>
            <p><b>Persönlichkeit:</b> Nora mischt sich gerne in die Angelegenheiten der Sterblichen ein.</p>
            <p>Lady Nora ist etwas über 20 Jahrhunderte alt und damit einer der ältesten und größten Drachen ihrer Zeit.
                Im Gegensatz zu den meisten Drachen, die wenig Interesse an Sterblichen haben, genießt sie es, Menschen,
                Keidran und Basitin beim Fehden untereinander zuzusehen. Normalerweise mischt sie sich nur ein, um zu
                sehen, was passieren könnte. Nora kann ihre Gestalt verändern, aber sie hat Schwierigkeiten, auf zwei
                Beinen zu balancieren. Aus diesem Grund verwandelt sie sich im Allgemeinen nur in andere vierbeinige
                Tiere und selten in eine humanoide Form.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="reni">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Reni2025', 'portrait'); ?>" alt="Reni"><br>
            <a href="https://www.patreon.com/posts/reni-ref-sheet-50534633" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('reniref_thumbnail', 'ref_sheet'); ?>" alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Prinzessin Reni</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> 20<br>
                <b>Klasse:</b> Prinzessin<br>
                <b>Spezies:</b> Schwarzer Drache<br>
                <b>Sprachen:</b> Alle (telepathisch)<br>
            </p>
            <p><b>Persönlichkeit:</b> Reni ist ein sanfter und gutmütiger Drache, der versucht, sein Bestes zu tun, um
                seine Macht und Autorität zu nutzen, um jedem zu helfen, dem er kann.</p>
            <p>Reni ist ein junger Drache, erst 20 Jahre alt, aber nach Drachen- und Menschenstandards als erwachsen
                angesehen. Obwohl sie den Titel "königlicher Drache" trägt und bei Menschen als Prinzessin anerkannt
                ist, ist sie aufgrund ihrer nicht-menschlichen Herkunft nicht für den Thron berechtigt. Sie besitzt
                jedoch immer noch königliche Autorität, die ihr Einfluss in politischen Angelegenheiten ermöglicht.</p>
            <p>Obwohl ihr die magische Begabung eines alten Drachen wie Nora fehlt, besitzt Reni angeborene
                Drachenfähigkeiten, einschließlich Telepathie und Gestaltwandlung in eine menschliche Form – eine Form,
                die sie akribisch entworfen hat, um sich anzupassen. Da sie unter Menschen aufgewachsen ist, betrachtet
                sie sich als Teil ihrer Gesellschaft und ist zutiefst beschützend ihnen gegenüber.</p>
            <p>Renis größter Fehler ist ihre Unerfahrenheit. Behütet und bestrebt, zu gefallen, neigt sie dazu, dem zu
                glauben, was ihr gesagt wird, und zu folgen, anstatt zu führen, oft gibt sie anderen trotz ihres
                königlichen Status nach. Ihre Naivität macht sie anfällig für Manipulationen, obwohl ihr starker
                moralischer Kompass und ihre Loyalität zur Menschheit sie dazu antreiben, im Laufe der Zeit unabhängiger
                und fähiger zu werden.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="adira">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Adira2025', 'portrait'); ?>" alt="Adira"><br>
            <a href="https://www.patreon.com/posts/adira-reference-27882970" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('adiramaeveref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Adira von der Riftwall</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> 14 (36 äquivalent)<br>
                <b>Klasse:</b> Tavernenbesitzerin<br>
                <b>Spezies:</b> Schneeleopard Keidran<br>
                <b>Sprachen:</b> Mensch, Keidran<br>
            </p>
            <p><b>Persönlichkeit:</b> Adira ist eine pragmatische Geschäftsfrau, die versucht, optimistisch zu bleiben,
                während sie unter Menschen lebt.</p>
            <p>Adira ist eine Bewohnerin der Menschenstadt Edinmire. Doch trotz dessen ist Adira keine Sklavin, sondern
                eine der wenigen Keidran, die als legale Bürger innerhalb des Imperiums existieren. Ihre Familie besitzt
                und betreibt die Riftwall-Taverne seit Generationen, noch bevor das Land menschliches Territorium wurde.
                Der Familie wurde erlaubt, den Betrieb fortzusetzen, im Wesentlichen durch Bestandsschutz.</p>
            <p>Schneeleoparden-Keidran sind ein verstreutes Volk, ohne bekanntes zentralisiertes Königreich. Es wird
                angenommen, dass die Spezies ausstirbt, was Adira zu einem seltenen Anblick macht. Adira hat den starken
                Wunsch, sich mit dem Rest ihrer Sippe wiederzuvereinigen und ihre Blutlinie fortzusetzen. Sie hat eine
                Tochter, Maeve.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="maeve">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('Maeve2025', 'portrait'); ?>" alt="Maeve"><br>
            <a href="https://www.patreon.com/posts/adira-reference-27882970" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('adiramaeveref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Maeve von der Riftwall</h3>
            <p>
                <b>Geschlecht:</b> Weiblich<br>
                <b>Alter:</b> 6 (13 äquivalent)<br>
                <b>Klasse:</b> Kleines Kätzchen<br>
                <b>Spezies:</b> Schneeleopard Keidran<br>
                <b>Sprachen:</b> Mensch, Keidran<br>
            </p>
            <p><b>Persönlichkeit:</b> Maeve ist Maeve.</p>
            <p>Maeve ist die Tochter von Adira.</p>
        </div>
        <div class="clear"></div>
    </section>

    <header>
        <h2 class="page-header">Bösewichte</h2>
    </header>

    <section class="char-detail" id="mask">
        <div class="center villain-image-container">
            <img class="lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('TheMaskedGods2025', 'portrait'); ?>" alt="Die Masken">
        </div>
        <div class="char-info villain-info">
            <h3>Die Masken</h3>
            <p>
                <b>Klasse:</b> Korrumpierte planetare Wächter<br>
                <b>Spezies:</b> Maske<br>
            </p>
            <p>Die Masken sind uralte, empfindungsfähige Artefakte, die geschaffen wurden, um das Gleichgewicht der
                Welt
                zu wahren. Im Laufe der Jahrhunderte wurden sie jedoch korrumpiert und verfolgen nun ihre eigenen,
                finsteren Ziele, oft indem sie Sterbliche als ihre Wirte benutzen und manipulieren.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail">
        <div class="center villain-image-container">
            <img class="lazy-char-img villain-group-image"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="<?php echo get_char_image_path('TemplarChart2025', 'portrait'); ?>" alt="Bösewichte">
            <?php /* data-src="https://cdn.twokinds.keenspot.com/img/characters/Villians.jpg" */ ?>
        </div>
        <div class="char-info villain-info">
            <h3>Die Meistertempler: Meisterspion, Architekt, Stratege, Seher und Magier.</h3>
            <p>
                <b>Klasse:</b> Meistertempler<br>
                <b>Spezies:</b> Mensch<br>
            </p>
            <p>Diese fünf Männer wurden von Großtempler Trace angestellt, um nach seiner Übernahme die Ordnung im
                Tempel
                aufrechtzuerhalten. Er ließ sie die bisherigen sechs Meistertempler des vorherigen Großtempels
                ersetzen.
                Nach Traces Verschwinden hielt der Tempelmeister den Orden bis zu seiner Rückkehr weiterhin unter
                Kontrolle. Einige suchen Trace, damit er zurückkehren kann, während andere planen, Trace zu töten
                und
                seine Macht an sich zu reißen. Wieder andere planen, ihn fernzuhalten und zu beschäftigen, damit er
                möglicherweise überhaupt nicht zurückkehrt.</p>
        </div>
        <div class="clear"></div>
    </section>

    <section class="char-detail" id="evil-trace">
        <div class="char-img">
            <img class="portrait lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/EvilTrace.jpg" alt="Böser Trace"><br>
            <img class="char-swatch lazy-char-img"
                src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                data-src="https://cdn.twokinds.keenspot.com/img/characters/swatches/TraceSwatch.gif"
                alt="Color Swatch"><br>
            <a href="https://www.patreon.com/posts/trace-reference-28691421" target="_blank"><img
                    class="char-swatch lazy-char-img"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                    data-src="<?php echo get_char_image_path('traceref_thumbnail', 'ref_sheet'); ?>"
                    alt="Ref Sheet"></a>
        </div>
        <div class="char-info">
            <h3>Trace Legacy (Böse)</h3>
            <p>
                <b>Geschlecht:</b> Männlich<br>
                <b>Alter:</b> 24<br>
                <b>Klasse:</b> Großtempel<br>
                <b>Spezies:</b> Mensch<br>
                <b>Sprachen:</b> Mensch, Keidran, Basitin<br>
            </p>
            <p><b>Persönlichkeit:</b> Trace ist ein herzloser Killer, der nichts Geringeres als den Tod aller
                Menschen
                will. Ihm ist das Leben egal.</p>
            <p>Trace wurde in eine erbärmliche Bauernfamilie hineingeboren, doch seine offensichtlichen natürlichen
                Talente in der Magie wurden schnell erkannt und in jungen Jahren wurde er in den Templerorden
                rekrutiert. Er stieg in den Rängen auf und übernahm unweigerlich die Organisation als Großtempel
                nach
                dem Tod seiner ersten Frau. Er wurde schnell als großer Herrscher bekannt, der von Menschen und
                Keidran
                gleichermaßen gefürchtet wurde. Seine unglaubliche Macht ermöglichte es ihm, alle zu dominieren, die
                sich ihm widersetzten.</p>
            <p>Ephemural raubte ihm jedoch seine Erinnerungen. Jetzt muss er seine Erinnerungen und seine
                Machtposition
                wiedererlangen, bevor er sich zu sehr auf das Leben des erbärmlichen Keidran einlässt, mit dem er
                reist.
            </p>
        </div>
        <div class="clear"></div>
    </section>

</section>

<?php require_once Path::getTemplatePartial('footer.php'); ?>