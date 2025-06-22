<?php
include "includes/design/about_header.php"; ?>
<header hidden>
    <h1 class="page-header">&Uuml;ber</h1>
</header>
<section>
    <h2 class="page-header">&Uuml;ber den Comic</h2>

	<p>
		<b>Name:</b> Twokinds (2kinds)<br>
		<b>Begonnen:</b> 22. Oktober 2003<br>
		<b>Art:</b> Fantasy Manga<br>
        
<?php
$directory = './'; // Verzeichnis, in dem die Dateien gesucht werden sollen
$files = scandir($directory); // Alle Dateien im Verzeichnis auflisten

$count = 0; // Z&auml;hler f&uuml;r die Anzahl der passenden Dateien

foreach ($files as $file) {
    if (is_file($directory . $file) && pathinfo($file, PATHINFO_EXTENSION) == 'php') {
        // &Uuml;berpr&uuml;fen, ob die Datei ein regul&auml;rer Dateityp ist (kein Verzeichnis) und die Dateiendung 'php' hat
        $filename = pathinfo($file, PATHINFO_FILENAME); // Dateinamen ohne Erweiterung erhalten
        if (preg_match('/^\d{8}$/', $filename)) {
            // &Uuml;berpr&uuml;fen, ob der Dateiname aus genau 8 Ziffern besteht
            $count++; // Z&auml;hler erh&ouml;hen
        }
    }
}

//echo 'Anzahl der PHP-Dateien mit einer 8-stelligen Zahl im Namen: ' . $count;
echo '<b>Seiten:</b> ' . $count . ' (inklusive der Bonusseiten)';
?>

        
	</p>

	<p><b>Zusammenfassung:</b> Nachdem er in einer mysteri&ouml;sen Schlacht sein Ged&auml;chtnis verloren hat, findet sich Trace Legacy, ein ehemaliger Anf&uuml;hrer einer Organisation von Magiern namens Templer, in der Gesellschaft von Flora wieder, einem M&auml;dchen mit seltsamen, tiger&auml;hnlichen Merkmalen. W&auml;hrend Trace allm&auml;hlich wieder lernt, was er einst wusste, entdeckt er bald, dass Flora zu einer Rasse von Lebewesen geh&ouml;rt, die Feinde seiner Art sind. Als sich Freundschaft und Rassenunterschiede vermischen, finden sich Trace und Flora in einer Situation wieder, die kritischer ist, als es sich einer von ihnen vorstellen kann.</p>

	<p><b>&Uuml;bersicht:</b> Twokinds ist ein Webcomic, der in einer Welt spielt, die von Rassenspannungen gepr&auml;gt ist, haupts&auml;chlich zwischen den Menschen und den tier&auml;hnlichen Keidran. Sie haben dramatisch unterschiedliche Gesellschaften und Vorstellungen davon, wie sie leben und handeln sollen. Aufgrund dieser Unterschiede befinden sich die beiden Rassen fast immer in irgendeiner Form von Konflikten. Die Geschichte beginnt, als die Welt am Rande eines weiteren Krieges steht. Doch im Gegensatz zu den vorherigen Kriegen scheint dieses Mal ein einzelner Mann die beiden Rassen zu seinem pers&ouml;nlichen Vorteil zu manipulieren - auch wenn er sich nicht mehr daran erinnern kann.</p>

	<p><b>Rassen:</b> Bei Twokind gibt es drei verschiedene Hauptarten von Rassen: <i>Menschen</i>, <i>Keidran</i>, und <i>Basitins</i>. Der gr&ouml;&szlig;te Teil des Konflikts dreht sich jedoch um die ersten beiden. Die Menschen sind die &uuml;blichen primitiven, zweibeinigen, empfindungsf&auml;higen Tiere. Aufgrund ihrer hohen Intelligenz und nat&uuml;rlichen Neugier sind sie den beiden anderen Rassen technologisch &uuml;berlegen.</p>

	<p><i>Keidran</i> sind hunde- und katzenartige, zweibeinige, empfindungsf&auml;hige Tiere. Es gibt sie in einer Vielzahl von Formen und Gr&ouml;&szlig;en, darunter: Tiger, Gro&szlig;katzen, Hunde, W&ouml;lfe und F&uuml;chse. Sie sind eine kurzlebige, aber intensive Rasse, die von starken Urinstinkten geleitet wird. Sie sind sehr territorial und in der Regel j&auml;hzornig. Sie sind jedoch relativ leicht zu unterwerfen und zu dominieren und eignen sich daher ideal als Sklaven f&uuml;r die Menschen. Dies ist seit Jahrhunderten ein Streitpunkt zwischen den beiden Rassen.</p>

	<p><i>Basitins</i> baba sind eine wenig bekannte Rasse von zweibeinigen, langohrigen, braunfelligen, empfindungsf&auml;higen Tieren. Im Gegensatz zu den beiden anderen Rassen leben die Basitins abseits des Festlandes auf einem entfernten Inselkontinent. Aufgrund ihrer Isolation werden sie von den anderen beiden V&ouml;lkern oft vergessen und sich selbst &uuml;berlassen. Sie sind ein kriegerisches Volk, dessen Gesellschaft ausschlie&szlig;lich aus Soldaten besteht. Sie verhalten sich wie ein Bienenstock und befolgen die Befehle ihrer Vorgesetzten, ohne sie in Frage zu stellen. In der Regel sind sie ausgeglichen und diszipliniert.</p>
</section>

<section>
	<h2 class="page-header">&Uuml;ber den K&uuml;nstler</h2>

	<!--<img class="float-left" src="https://cdn.twokinds.keenspot.com/img/mugshot.jpg" alt="Tom">-->
    <img class="float-left" src="./includes/originaldateien/cdn.twokinds.keenspot.com/img/charaktere/Tom.webp" alt="Tom" height="275">

	<p>
		<b>Name:</b> Tom Fischbach<br>
		<b>Geboren:</b> 28 Juli 1987<br>
        
<?php
$Geboren = '28.07.1987'; // Geburtsdatum im Format DD.MM.YYYY

// Geburtsdatum in ein Format umwandeln, das von der Funktion strtotime() erkannt wird (YYYY-MM-DD)
$geburtstag = date('Y-m-d', strtotime(str_replace('.', '-', $Geboren)));

// Aktuelles Datum
$heute = date('Y-m-d');

// Alter berechnen
$alter = date_diff(date_create($geburtstag), date_create($heute))->y;

echo '<b>Alter:</b> ' . $alter . ' Jahre<br>';
?>

		<b>Ethnie:</b> Asiatisch (Koreanisch)<br>
		<b>Nationalit&auml;t:</b> Amerikanisch<br>
	</p>

	<p>Ich bin seit &uuml;ber 6 Jahren als K&uuml;nstler t&auml;tig und habe im Alter von 14 Jahren angefangen. Der erste Anime, den ich gesehen habe, war Mein Nachbar Totoro. Ich beschloss, mit dem Zeichnen anzufangen, nachdem ich durch eine zuf&auml;llige Websuche auf Webcomics gesto&szlig;en war. Der erste Webcomic, den ich las, war Vet on the Net, und das war nur der Erste.</p>

	<p>Ich wurde zum Schreiben von Twokinds inspiriert, nachdem ich in meiner Schule eine Menge Rassendiskriminierung durch &uuml;berwiegend wei&szlig;e Sch&uuml;ler erlebt hatte. Meine Schreibf&auml;higkeiten waren damals nicht sehr gut, aber ich hatte trotzdem das Gef&uuml;hl, etwas Gutes zu tun, indem ich versuchte, durch mein Schreiben eine gute Botschaft zu vermitteln. Nachdem ich mich ein Jahr lang mit der Erstellung eines Buches abgem&uuml;ht hatte, beschloss ich, dass ein Comic die bessere Wahl sein k&ouml;nnte. Nachdem ich noch einige Jahre an meiner Kunst gearbeitet hatte, brachte ich schlie&szlig;lich am 22. Oktober 2003 Twokinds heraus.</p>

	<p>Heute bin ich Student am Raymond Walters College, das zur Universit&auml;t von Cincinnati geh&ouml;rt. Ich studiere, um Strahlentherapeut zu werden, w&auml;hrend ich nebenbei Kunst erstelle.</p>
</section>


<?php
include "includes/design/about_footer.php";
?>
