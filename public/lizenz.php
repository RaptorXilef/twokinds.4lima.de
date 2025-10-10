<?php
/**
 * Diese Datei erklärt die Lizenzbedingungen für die Nutzung der Comic-Inhalte.
 * 
 * @file      /lizenz.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
require_once __DIR__ . '/src/components/public_init.php';

// === 2. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Lizenz';
$siteDescription = 'Informationen zu den Creative Commons-Lizenzen, unter denen der TwoKinds-Comic und diese Webseite stehen.';
$robotsContent = 'index, follow';

// === 3. HEADER EINBINDEN ===
require_once __DIR__ . "/src/layout/header.php";
?>

<article>
	<header>
		<h1 class="page-header">Lizenz</h1>
	</header>

	<p>Von Zeit zu Zeit kommen einige Leute auf Tom zu und berichten, dass eines Seiner Bilder von jemand anderem
		„gestohlen“ wurde. Normalerweise ist es eine bearbeitete Seite aus dem Comic oder ein neu eingefärbtes Bild aus
		Seiner Galerie. Daher wollte er den Sachverhalt klarstellen, damit er in Zukunft hoffentlich weitere dieser
		„Berichte“ vermeiden kann.</p>
	<p>Toms öffentliche Arbeit unterliegt hiermit einer <a
			href="https://creativecommons.org/licenses/by-nc-sa/3.0/de/deed.de">Creative Commons-Lizenz</a>, sofern
		nicht
		anders angegeben. </p>

	<div class="center">
		<a href="https://creativecommons.org/licenses/by-nc-sa/3.0/de/">
			<img src="https://i.creativecommons.org/l/by-nc-sa/3.0/de/88x31.png" alt="Creative Commons Lizenz" />
		</a>
	</div>

	<p>Was bedeutet das also? Das bedeutet, dass es jedem freisteht:</p>
	<ul>
		<li><b>Toms Arbeit zu teilen</b> – Sie können Toms Arbeit speichern, ausdrucken und anderen Leuten zeigen. Sie
			können es in Foren, Imageboards und Chatrooms veröffentlichen. Sie könnten sogar den gesamten Comic
			komprimieren
			und ihn als Torrent verteilen, wenn Sie Lust dazu hätten.</li>
		<li><b>Toms Arbeit anpassen</b> – Das bedeutet, dass es den Leuten freisteht, Toms Arbeit zu ändern. Sie können
			die
			Comicseiten in eine andere Sprache übersetzen, (Wie auf dieser Webseite geschehen!) etwas aus seiner Galerie
			neu
			einfärben, Fankunst oder Fiktion von Toms Charaktere oder seiner Welt erstellen, Ihren eigenen Comic oder
			Ihr
			eigenes Kunstwerk basierend auf seine Arbeit erstellen usw. </li>
	</ul>

	<p>Sie dürfen alles davon tun, solange Sie:</p>
	<ul>
		<li><b>Quellenangaben angeben</b> – Sie müssen Tom als Quelle angeben. Beanspruchen Sie seine Arbeit nicht als
			Ihre
			eigene.</li>
		<li><b>Nicht kommerziell</b> – Sie können seine Arbeit nicht verkaufen oder seine Arbeit in etwas zum Verkauf
			einbeziehen, es sei denn, Sie treffen spezielle Vereinbarungen mit ihm.</li>
		<li><b>Weitergabe unter gleichen Bedingungen</b> – Wenn Sie seine Arbeit ändern, sollte sie ebenfalls unter
			derselben Lizenz oder einer gleichwertigen Lizenz fallen.</li>
	</ul>

	<p>Diese Lizenz gilt für alle seine öffentlich zugänglichen Arbeiten. Dies gilt nicht für seine kommerziellen
		Arbeiten
		wie Comics und Broschüren.</p>
	<p>Warum macht er das? Weil es seiner Meinung nach so ist, wie es sein sollte. Er stellt seine Arbeit öffentlich zur
		Verfügung, damit die Leute sie genießen können, und es stört ihn nicht, wenn Leute beschließen, seine Arbeit auf
		andere Weise zu ergänzen oder anzupassen. Das ist also seine offizielle Art zu verkünden, dass er damit
		einverstanden ist. Er verzichtet nicht auf alle Rechte an seiner Arbeit, aber er gibt Ihnen einige davon frei.
	</p>


	Quelle: <a href="https://twokinds.keenspot.com/license/"> https://twokinds.keenspot.com/license/ </a>
</article>

<?php
// Binde den gemeinsamen Footer ein.
require_once __DIR__ . "/src/layout/footer.php";
?>