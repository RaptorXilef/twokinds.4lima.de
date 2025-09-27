<?php
/**
 * FAQ-Seite (Häufig gestellte Fragen).
 * 
 * @file      /faq.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
require_once __DIR__ . '/src/components/public_init.php';

// === 2. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'FAQ - Häufig gestellte Fragen';
$siteDescription = 'Antworten auf häufig gestellte Fragen zum TwoKinds-Comic, der deutschen Übersetzung und der Webseite.';
$robotsContent = 'index, follow';

// === 3. HEADER EINBINDEN ===
require_once __DIR__ . "/src/layout/header.php";
?>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    .faq-container {
        max-width: 800px;
        margin: 20px auto;
    }

    .faq-item {
        border-bottom: 1px solid #ddd;
        margin-bottom: 15px;
    }

    body.theme-night .faq-item {
        border-bottom-color: #555;
    }

    .faq-question {
        padding: 15px;
        cursor: pointer;
        font-weight: bold;
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .faq-question::after {
        content: '\25B6';
        /* Right-pointing triangle */
        font-size: 0.8em;
        transition: transform 0.3s ease;
    }

    .faq-item.active .faq-question::after {
        transform: rotate(90deg);
    }

    .faq-answer {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
        padding: 0 15px;
    }

    .faq-item.active .faq-answer {
        max-height: 500px;
        /* Adjust as needed */
        padding: 15px;
        transition: max-height 0.5s ease-in;
    }
</style>

<article>
    <header>
        <h1 class="page-header">Häufig gestellte Fragen (FAQ)</h1>
    </header>

    <div class="faq-container">
        <p>Nachdem Tom jahrelang immer wieder dieselben Fragen beantworten musste, hat er sich schließlich dazu
            entschlossen, einen FAQ-Bereich zu erstellen, welchen ich für euch übersetzt habe. Hoffentlich hilft dies
            bei der Beantwortung aller allgemeinen Fragen, die Sie haben könnten, und gibt Tom eine Pause davon, diese
            in Zukunft zu beantworten.</p>

        <div class="faq-item">
            <div class="faq-question">Wie oft aktualisiert Tom das Comic?</div>
            <div class="faq-answer">
                <p>Tom: Derzeit aktualisiert Tom jeden Mittwoch eine neue Seite, normalerweise morgens US-amerikanischer
                    Zeit.</p>
                <p>Felix: Ich übersetze dann die je 4 neuen Seiten meist ein mal im Monat, meist zum letzten Wochenende
                    des Monats.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Wie ist Tom auf TwoKinds gekommen?</div>
            <div class="faq-answer">
                <p>TwoKinds war nur eine von vielen spontanen Comic-Ideen, die Tom während seiner frühen
                    High-School-Jahre einfielen. Es ist das Ergebnis jahrelanger Arbeit und hat sich zu dem entwickelt,
                    was es heute ist.
                </p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Warum Tiermenschen?</div>
            <div class="faq-answer">
                <p>Tom wollte eine Geschichte über Rassenthemen und darüber schreiben, was es bedeutet, ein Mensch zu
                    sein. Er entschied, dass die Verwendung von Nicht-Menschen einen größeren Kontrast zu den Themen
                    schaffen und die Dinge interessanter machen würde.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Wie hat Tom das Zeichnen gelernt?</div>
            <div class="faq-answer">
                <p>Er begann, wie die meisten Künstler es taten, indem er es sich selbst beibrachte. Er studierte andere
                    Cartoons und Comics. Er hat Online-Tutorials geschaut und kopierte Grafiken aus anderen Webcomics,
                    um zu lernen, was er jetzt weiß. Viel später erhielt er eine formelle Ausbildung in Kunsttechniken
                    und -prinzipien, die ihm bei der Verfeinerung seiner Arbeit hilfreich war.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Hat Tom eine formelle Ausbildung?</div>
            <div class="faq-answer">
                <p>Er ist dabei, einen Abschluss in Animation zu erwerben. Außerhalb der Künste arbeitet er auch an
                    einem Abschluss im medizinischen Bereich – insbesondere Strahlentechnologie.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Hat Tom schonmal darüber nachgedacht, ein TwoKinds-Videospiel oder einen Cartoon
                zu machen?</div>
            <div class="faq-answer">
                <p>Natürlich hat er darüber nachgedacht. Aber selbst wenn so etwas möglich wäre, würde er den
                    TwoKinds-Webcomic
                    wahrscheinlich sowieso nicht als Drehbuch dafür verwenden. Der Comic wurde nie im Hinblick auf
                    Animationen oder Spiele formatiert. Er hat andere TwoKinds-bezogene Skripte im Hinterkopf, die für
                    solche Dinge
                    viel besser geeignet wären, wenn sie jemals machbar wären. Vielleicht eines Tages.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Würde Tom etwas für mich zeichnen?</div>
            <div class="faq-answer">
                <p>Ich fürchte, er hat bei all der Arbeit, die er durchmachen muss, keine Zeit, Anfragen oder
                    Kunsttausch
                    entgegenzunehmen.</p>
                <p>Wenn du Tom auf <a href="https://www.patreon.com/twokinds">Patreon</a> unterstützt, kannst du jedoch
                    einen
                    begrenzten Einfluss auf die dort entstehenden Kunstwerke nehmen.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Kann Tom meine Figur in seinen Comic aufnehmen?</div>
            <div class="faq-answer">
                <p>Nein, tut mir leid.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Wie sende ich Tom Fanart?</div>
            <div class="faq-answer">
                <p>Sie können ihm Ihr Fanart als Datei oder Link per E-Mail senden. Der Fanart-Bereich wird von Hand
                    aktualisiert, daher kann es einige Zeit dauern, bis er dazu kommt, Ihren zu veröffentlichen.</p>
                <p>Allerdings hat Tom den Bereich schon seit Jahren nicht mehr aktualisiert. Einfacher ist es deine
                    Fanart bei
                    <a href="https://www.deviantart.com/twokinds">Deviantart</a> hochzuladen und Tom/TwoKinds zu
                    verlinken oder
                    als Hashtag anzugeben. So sieht auch Tom dein Kunstwerk, wenn er mal Zeit hat in Deviantart zu
                    stöbern. So
                    hab ich das zumindest vor einigen Jahren gemacht :.) <a
                        href="https://www.deviantart.com/raptorxilef/art/Mandie-am-Strand-2014-12-27-635022310">Beispiel</a>
                </p>
                <p>Aber mir liegt Zeichnen nicht so, ich bleibe lieber beim Übersetzen und Bearbeiten. Die Fanarts
                    überlasse ich
                    euch. :D</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Kann Tom mir das Zeichnen beibringen, mir Tipps geben oder sie kritisieren?</div>
            <div class="faq-answer">
                <p>Nein, tut mir leid. Er mag es nicht, Ratschläge zu geben oder Leuten zu sagen, was sie tun sollen. Es
                    wäre
                    wahrscheinlich das Beste für Sie, das zu tun, was er getan hat: online nach Tutorials und Tipps zu
                    suchen.
                    Versuchen Sie es mit Google und DeviantART.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Kann ich Toms Arbeit anpassen?</div>
            <div class="faq-answer">
                <p>Ja. Sie dürfen seine nichtkommerziellen Arbeiten ändern, übersetzen oder weiterverbreiten. Sie können
                    seine
                    Charaktere und Einstellungen verwenden oder sich für Ihre eigenen Arbeiten inspirieren lassen. Alle
                    Details
                    und Einschränkungen finden Sie auf seiner <a
                        href="https://twokinds.keenspot.com/license/">Lizenzierungsseite</a>.</p>
                <p>Oder bei mir in deutsch: <a
                        href="<?php echo htmlspecialchars($baseUrl); ?>lizenz.php">Lizenzierungsseite</a></p>
            </div>
        </div>
    </div>
</article>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const faqItems = document.querySelectorAll('.faq-item');

        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            question.addEventListener('click', () => {
                const isActive = item.classList.contains('active');

                faqItems.forEach(i => i.classList.remove('active'));

                if (!isActive) {
                    item.classList.add('active');
                }
            });
        });
    });
</script>

<?php
require_once __DIR__ . "/src/layout/footer.php";
?>