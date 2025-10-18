<?php
/**
 * Diese Datei enthält Informationen über den Comic, den Künstler und den Übersetzer.
 * 
 * @file      ROOT/public/ueber_den_comic.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../src/components/init_public.php';

// === 2. LADE-SKRIPTE & DATEN (Jetzt mit der Path-Klasse) ===
require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'load_comic_data.php';

// === 3. DATENVERARBEITUNG ===
// Ermittelt die Anzahl der Comic-Seiten und Lückenfüller.
$comicPageCount = 0;
$fillerPageCount = 0;
foreach ($comicData as $details) {
	if (isset($details['type'])) {
		if ($details['type'] === 'Comicseite') {
			$comicPageCount++;
		} elseif ($details['type'] === 'Lückenfüller') {
			$fillerPageCount++;
		}
	}
}

// Berechnet das Alter der Personen.
$today = new DateTime();
$birthdateTom = new DateTime('1987-07-28');
$ageTom = $today->diff($birthdateTom)->y;

$birthdateFelix = new DateTime('1993-03-29');
$ageFelix = $today->diff($birthdateFelix)->y;

// Bild-Pfad für den Übersetzer mit Cache-Buster (NEUE METHODE)
$felixImageWebUrl = Url::getImg('about/Felix.webp');
$felixImagePathOnServer = DIRECTORY_PUBLIC_IMG_ABOUT . DIRECTORY_SEPARATOR . 'Felix.webp';
if (file_exists($felixImagePathOnServer)) {
	$felixImageWebUrl .= '?c=' . filemtime($felixImagePathOnServer);
}

// === 4. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Über den Comic';
$siteDescription = 'Erfahre alles über den Webcomic TwoKinds, den Künstler Tom Fischbach und den deutschen Übersetzer Felix Maywald.';
$robotsContent = 'index, follow';

// === 5. HEADER EINBINDEN (Jetzt mit Path-Klasse) ===
require_once Path::getTemplatePartial('header.php');
?>
<section>
	<h2 class="page-header">Über den Comic</h2>

	<p>
		<b>Name:</b> TwoKinds (2kinds)<br>
		<b>Begonnen:</b> 22. Oktober 2003<br>
		<b>Übersetzt seit:</b> September 2016 <br>
		<b>Auf twokinds.4lima.de seit:</b> 2020<br>
		<b>Art:</b> Fantasy Manga<br>
		<b>Comicseiten:</b> <?php echo $comicPageCount; ?><br>
		<b>Lückenfüller:</b> <?php echo $fillerPageCount; ?>
	</p>

	<p><b>Zusammenfassung:</b> Nachdem er in einer mysteriösen Schlacht sein Gedächtnis verloren hat, findet sich Trace
		Legacy, ein ehemaliger Anführer einer Organisation von Magiern namens Templer, in der Gesellschaft von Flora
		wieder, einem Mädchen mit seltsamen, tigerähnlichen Merkmalen. Während Trace allmählich wieder lernt, was er
		einst wusste, entdeckt er bald, dass Flora zu einer Rasse von Lebewesen gehört, die Feinde seiner Art sind. Als
		sich Freundschaft und Rassenunterschiede vermischen, finden sich Trace und Flora in einer Situation wieder, die
		kritischer ist, als es sich einer von ihnen vorstellen kann.</p>

	<p><b>Übersicht:</b> TwoKinds ist ein Webcomic, der in einer Welt spielt, die von Rassenspannungen geprägt ist,
		hauptsächlich zwischen den Menschen und den tierähnlichen Keidran. Sie haben dramatisch unterschiedliche
		Gesellschaften und Vorstellungen davon, wie sie leben und handeln sollen. Aufgrund dieser Unterschiede befinden
		sich die beiden Rassen fast immer in irgendeiner Form von Konflikten. Die Geschichte beginnt, als die Welt am
		Rande eines weiteren Krieges steht. Doch im Gegensatz zu den vorherigen Kriegen scheint dieses Mal ein einzelner
		Mann die beiden Rassen zu seinem persönlichen Vorteil zu manipulieren - auch wenn er sich nicht mehr daran
		erinnern kann.</p>

	<p><b>Rassen:</b> Bei TwoKind gibt es drei verschiedene Hauptarten von Rassen: <i>Menschen</i>, <i>Keidran</i>, und
		<i>Basitins</i>. Der größte Teil des Konflikts dreht sich jedoch um die ersten beiden. Die Menschen sind die
		üblichen primitiven, zweibeinigen, empfindungsfähigen Tiere. Aufgrund ihrer hohen Intelligenz und natürlichen
		Neugier sind sie den beiden anderen Rassen technologisch überlegen.
	</p>

	<p><i>Keidran</i> sind hunde- und katzenartige, zweibeinige, empfindungsfähige Tiere. Es gibt sie in einer Vielzahl
		von Formen und Größen, darunter: Tiger, Großkatzen, Hunde, Wölfe und Füchse. Sie sind eine kurzlebige, aber
		intensive Rasse, die von starken Urinstinkten geleitet wird. Sie sind sehr territorial und in der Regel
		jähzornig. Sie sind jedoch relativ leicht zu unterwerfen und zu dominieren und eignen sich daher ideal als
		Sklaven für die Menschen. Dies ist seit Jahrhunderten ein Streitpunkt zwischen den beiden Rassen.</p>

	<p><i>Basitins</i> sind eine wenig bekannte Rasse von zweibeinigen, langohrigen, empfindungsfähigen Tieren mit
		braunem Fell. Im Gegensatz zu den beiden anderen Rassen leben die Basitins abseits des Festlandes
		auf einem entfernten Inselkontinent. Aufgrund ihrer Isolation werden sie von den anderen beiden Völkern oft
		vergessen und sich selbst überlassen. Sie sind ein kriegerisches Volk, dessen Gesellschaft ausschließlich aus
		Soldaten besteht. Sie verhalten sich wie ein Bienenstock und befolgen die Befehle ihrer Vorgesetzten, ohne sie
		in Frage zu stellen. In der Regel sind sie ausgeglichen und diszipliniert.</p>
</section>

<section>
	<h2 class="page-header">Über den Künstler</h2>

	<img class="float-left" src="https://cdn.twokinds.keenspot.com/img/mugshot.jpg" alt="Tom" height="275">

	<p>
		<b>Name:</b> Tom Fischbach<br>
		<b>Geboren:</b> 28 Juli 1987<br>
		<b>Alter:</b> <?php echo $ageTom; ?> Jahre<br>
		<b>Ethnie:</b> Asiatisch (Koreanisch)<br>
		<b>Nationalität:</b> Amerikanisch<br>
	</p>

	<p>Ich bin seit über 6 Jahren als Künstler tätig und habe im Alter von 14 Jahren angefangen. Der erste Anime, den
		ich gesehen habe, war Mein Nachbar Totoro. Ich beschloss, mit dem Zeichnen anzufangen, nachdem ich durch eine
		zufällige Websuche auf Webcomics gestoßen war. Der erste Webcomic, den ich las, war Vet on the Net, und das war
		nur der Erste.</p>

	<p>Ich wurde zum Schreiben von TwoKinds inspiriert, nachdem ich in meiner Schule eine Menge Rassendiskriminierung
		durch überwiegend weiße Schüler erlebt hatte. Meine Schreibfähigkeiten waren damals nicht sehr gut, aber ich
		hatte trotzdem das Gefühl, etwas Gutes zu tun, indem ich versuchte, durch mein Schreiben eine gute Botschaft zu
		vermitteln. Nachdem ich mich ein Jahr lang mit der Erstellung eines Buches abgemüht hatte, beschloss ich, dass
		ein Comic die bessere Wahl sein könnte. Nachdem ich noch einige Jahre an meiner Kunst gearhbeitet hatte, brachte
		ich schließlich am 22. Oktober 2003 TwoKinds heraus.</p>

	<p>Heute bin ich Student am Raymond Walters College, das zur Universität von Cincinnati gehört. Ich studiere, um
		Strahlentherapeut zu werden, während ich nebenbei Kunst erstelle.</p>
</section>

<section>
	<h2 class="page-header">Über den Übersetzer</h2>

	<img class="float-left" src="<?php echo htmlspecialchars($felixImageWebUrl); ?>" alt="Felix" height="275">

	<p>
		<b>Name:</b> Felix Maywald<br>
		<b>Geboren:</b> März 1993<br>
		<b>Alter:</b> <?php echo $ageFelix; ?> Jahre<br>
		<b>Ethnie:</b> Deutsch<br>
		<b>Nationalität:</b> Deutsch<br>
	</p>

	<p>Seit 2022 übersetze ich als Hobby Webcomics in deutsche. Alles begann, als ich bedauerlicherweise im Jahr 2021
		feststellen musste, dass Cornelius damit aufgehört hatte, mein Lieblings-Webcomic TwoKinds zu übersetzen. Das
		ließ mich nicht kalt, denn ich wollte weiterhin anderen Fans die Möglichkeit geben, diesen großartigen Comic in
		ihrer Sprache genießen zu können. Also beschloss ich kurzerhand, mich selbst als Übersetzer zu versuchen.</p>

	<p>TwoKinds war tatsächlich der erste Webcomic, den ich von Anfang bis Ende gelesen habe. Seit 2009 begleitet er
		mich, und ich habe die Charaktere lieben gelernt und mit ihnen mitgefiebert. Die Geschichte und die wunderbaren
		Zeichnungen, die sich immer weiterentwickelten, haben mich von Anfang an gefesselt und ich wollte unbedingt,
		dass auch andere Menschen mit meiner Muttersprache die gleiche Begeisterung teilen können.</p>

	<p>Als echter Fan habe ich natürlich sämtliche Bücher von TwoKinds, eines sogar signiert. :D</p>

	<p>Aber nicht nur TwoKinds fasziniert mich. Als ich mich in die Welt der Webcomics vertieft habe, habe ich viele
		andere großartige Werke entdeckt, die es verdient haben, in andere Sprachen übersetzt zu werden. So habe ich
		mich kürzlich daran gemacht, <a href="https://www.twindragonscomic.com/">TwinDragons von Robin Dassen</a> zu <a
			href="https://inkbunny.net/submissionsviewall.php?rid=b55ccb4c75&mode=pool&pool_id=81411&page=1&orderby=pool_order&random=no&success=">übersetzen</a>.
		Es ist eine spannende Aufgabe, die mir viel Freude bereitet und mich immer wieder dazu anspornt, mein Bestes zu
		geben.</p>

	<p>Neben meinem Hobby als Übersetzer bin ich heute als Medienpädagoge tätig. Ich arbeite gerne mit Menschen und
		liebe es, mein Wissen und meine Begeisterung für Medien weiterzugeben. Fotografie ist ein weiteres Hobby, dem
		ich leidenschaftlich nachgehe. Es erlaubt mir, die Schönheit der Welt einzufangen und meine eigene kreative
		Perspektive auszudrücken. In meiner Freizeit bearbeite ich auch gerne meine eigenen Bilder und <a
			href="https://felixmaywaldfotografie.myportfolio.com/home">teile</a> und <a
			href="https://stock.adobe.com/de/contributor/207896179/Felix">verkaufe</a> sie in kleinem Rahmen online.</p>

	<p>Das Übersetzen von Webcomics hat sich zu einer wunderbaren Ergänzung meines Lebens entwickelt. Es erfüllt mich
		mit Freude, andere Menschen in meiner Muttersprache an den Geschichten und Charakteren teilhaben zu lassen, die
		mich so sehr begeistern. Ich bin gespannt darauf, welche neuen Webcomics ich noch entdecken und übersetzen darf
		und werde, und ich freue mich darauf, weiterhin meine Leidenschaft für Medien, Fotografie und kreative Projekte
		ausleben zu können.</p>
</section>

<?php require_once Path::getTemplatePartial('footer.php'); ?>