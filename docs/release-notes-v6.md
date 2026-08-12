# 🚀 Das größte Update aller Zeiten: TwoKinds auf Deutsch wurde komplett neu gebaut

Hey zusammen!
Es gibt Neuigkeiten auf [https://twokinds.4lima.de/](https://twokinds.4lima.de/)

Ich muss euch unbedingt erzählen, woran ich in den letzten Wochen (und unzählige Nächte) gearbeitet habe. Ich habe bei twokinds.4lima.de nicht nur einen kleinen "Tapetenwechsel" vorgenommen, sondern das komplette Fundament der Webseite eingerissen und einen echten, hochmodernen Wolkenkratzer darauf gebaut!

Vorher war die Seite funktional, aber unter der Haube ziemlich laienhaft programmiert und etwas in die Jahre gekommen. Jetzt ist sie ein absolutes Vorzeigeprojekt. Bevor wir tief in die Technik abtauchen, hier direkt das Wichtigste für euch:

---

## ⭐ Die absoluten Highlights für angemeldete Nutzer

[Login-Bereich](https://twokinds.4lima.de/login)

Wenn ihr euch einen kostenlosen Account erstellt (euch einloggt), profitiert ihr am meisten von diesem Update. Hier sind die besten Neuerungen auf einen Blick:

* **⚡ Extremer Speed & Daten-Ersparnis:**
  Die Seite lädt jetzt (bei gutem Internet) in 0,2 bis 1 Sekunde (vorher 2-8 Sekunden). Eine Comicseite verbraucht nur noch ca. 200 bis 400 KB bei gleichbleibender (und teils besserer) Qualität - vorher waren das 2 bis 4 MB! Euer mobiles Datenvolumen wird es euch danken.

* **📱 Echte Smartphone-Optimierung:**
  Die Seite ist jetzt zu 100 % für Handys optimiert. Ihr könnt im Comic-Reader ab sofort ganz intuitiv per Wischgeste (Swipe nach links/rechts) zur nächsten oder vorherigen Seite blättern!

* **☁️ Cloud-Lesezeichen:**
  Eure gemerkten Seiten werden nicht mehr nur lokal im Browser, sondern sicher in eurem Account gespeichert. Lest am Handy in der Bahn, loggt euch abends am PC ein und macht genau da weiter, wo ihr aufgehört habt.
  *Oder gebt eure Lesezeichen über euer Profil sogar frei (optional).*
  [Zu euren Lesezeichen](https://twokinds.4lima.de/lesezeichen)
  *Hinweis zur Migration:* Die Art und Weise wie die Lesezeichen gespeichert und verarbeitet werden hat sich grundlegend geändert. Aber keine Sorge, beim ersten Aufrufen der neuen Webseite werden eure alten Lesezeichen ins neue Format konvertiert und sind nach wenigen Sekunden direkt auf der neuen Webseite verfügbar!

* **📧 Das 3-Stufen Newsletter-System:**
  Ihr könnt in eurem Profil nun millimetergenau einstellen, worüber ihr benachrichtigt werden wollt:
  * **Comic-Seite (Bild):** Mail, sobald die fertige übersetzte Seite online ist.
  * **Vorab-Transkripte (Spoiler!):** Für die Ungeduldigen! Mail mit dem deutschen Text, noch bevor das Bild hochgeladen wurde.
  * **Report-Updates:** Eine Info-Mail, sobald ein von euch gemeldeter Fehler von mir behoben wurde.
  * *Gar kein Newsletter:* Diese Option gibt es natürlich auch. :-)

* **🍀 Mithelfen lohnt sich jetzt:**
  Wenn Ihr maßgeblich beim Finden eines Fehlers, einer Verbesserung einer Übersetzung, oder ähnlichem beteiligt wart, wird dir (optional) unten auf der Seite Tribut gezollt. Dein Name und Profilbild werden zu sehen sein. Klickt man dann auf dein Bild, sieht man genau, wo du überall mitgeholfen hast!

---

## 🎉 Alle neuen Frontend-Features im Detail (Öffentlich erreichbare Seiten)

Für euch hat sich noch viel mehr getan. Die Seite ist interaktiver, detaillierter und intelligenter geworden:

* **Das Charakter-Wiki *NEU*** ([Zur Übersicht](https://twokinds.4lima.de/charaktere))
  Die Charakterübersicht ist nun fast ein vollwertiges Wiki! Jeder Charakter hat eine eigene Seite mit Biografie, Farbschemata (Swatches), Reference-Sheets und all seinen Comic-Auftritten. *(An diesem Teil arbeite ich gerade noch aktiv. Einige Infos sind noch durcheinander. Gebt mir hier einfach noch ein paar Tage Zeit. :D)*

* **Intelligente Filter *NEU***
  Sucht Charaktere blitzschnell nach Spezies, Alter, Geschlecht oder Fraktion - und das ohne, dass die Seite neu laden muss!

* **Fehler-Melde-Werkzeug *NEU***
  Findet ihr einen Tippfehler? Klickt einfach auf "Fehler melden". Ihr könnt einen Screenshot anhängen oder den Text direkt im Editor für mich korrigieren. Angemeldete User werden sogar als Helfer verewigt (optional).

* **Erweiterte Profile *NEU***
  Passt euer Profilbild mit einem interaktiven Zuschneide-Werkzeug direkt im Browser an, schreibt eine Biografie, teilt Social-Media-Links und zeigt eure Lesezeichen als "Favoriten" öffentlich auf eurem Profil.

* **Sicherheit & Magic Links *NEU***
  Wenn ihr. die Seite zu lange ungenutzt offen lasst, warnt euch der neue Session Timer und loggt euch zum Schutz aus. Mails und Passwörter werden über hochsichere "Magic Links" verifiziert.

* **RSS-Feed Tool *NEU*** ([Zum Feed](https://twokinds.4lima.de/rss.xml))
  Ihr nutzt Feedly oder Thunderbird? Nutzt den "RSS-Feed kopieren"-Button, um kein Update zu verpassen. (Ab jetzt plattformunabhängiges RSS auf Mac, iOS, Windows, Android, Linux).

* **Performance (Lazy-Loading) *NEU***
  Bilder werden erst geladen, wenn ihr auch wirklich dorthin scrollt. Das spart extrem viel Ladezeit und schont die Server und euer Datenvolumen.

---

## 🛠️ Was ist neu für MICH? (Die Admin-Features)

Damit ich euch in Zukunft noch schneller mit neuen Seiten versorgen kann, habe ich mir das Backend wie das Cockpit eines Raumschiffs ausgebaut. Hier hat sich die harte Arbeit besonders gelohnt:

* **Automatisches Deployment via deploy.yml *NEU***
  Pure Magie! Wenn ich neuen Code auf GitHub lade, erkennt der Server das, komprimiert die Daten und schiebt das Update vollautomatisch auf die Live-Seite. Keine manuelle FTP-Arbeit mehr!

* **Massen-Upload & WebP-Magie *NEU***
  Ich ziehe einfach dutzende hochauflösende Comic-Seiten per Drag & Drop in den Browser. Das System skaliert sie, wandelt sie in das moderne WebP-Format um und erstellt automatisch alle Thumbnails. Das bedeutet, verbessere ich eine Comicseite, muss ich nur noch das Bild auf den Browser ziehen und es sortiert sich automatisch ein und ersetzt das alte Bild. Genial oder? :-)

* **Social-Media Cropper *NEU***
  Das System schneidet Comicseiten für Facebook/Twitter automatisch auf das perfekte Format (1.91:1) zu. Stimmt der Fokus nicht, kann ich das Bild direkt im Browser manuell zuschneiden! Das bedeutet für dich, wenn du den Link/URL zu einer Comicseite teilst, senden meine Server automatisch ein Vorschaubild an den geteilten Post.

* **E-Mail Warteschlange (Queue) *NEU***
  Mails an hunderte von euch blockieren nicht mehr den Server. Sie wandern in eine intelligente Warteschlange und werden von einem CronJob im Hintergrund nach Wichtigkeit sortiert Stück für Stück verschickt.

* **Rechte- und Rollensystem *NEU***
  Ich kann jetzt detaillierte Rechtegruppen anlegen (z.B. Redakteure, die nur Texte anpassen, aber keine Comics löschen dürfen). So wird es in Zukunft möglich, dass ich auch mit jemandem zusammenarbeiten kann. :-)

* **Papierkorb & Zeitmaschine *NEU***
  Ändere ich einen Comic, speichert das System die alte Version. Ich kann Fehler mit einem Klick rückgängig machen (Undo) oder komplett gelöschte Comics aus einem Papierkorb wiederherstellen. (Das wird mir so einiges an Nerven sparen!)

* **Sicherheits-Backups *NEU***
  Der Server sichert die Datenbank vollautomatisch und sicher verschlüsselt. Bei Problemen kann ich exakte Speicherstände mit einem Klick wiederherstellen. Eure Daten sind also mehrfach abgesichert.

---

## ⚙️ Für die Nerds: Die neue Architektur

Wer sich für Programmierung interessiert, wird das hier lieben:

[GitHub-Repository](https://github.com/RaptorXilef/twokinds.4lima.de)

Das System läuft jetzt auf **OOP (Objektorientierte Programmierung)** und **DDD (Domain-Driven Design)**. Vorher war es eher "Spaghetti-Code", jetzt ist alles in saubere Objekte und logische Bausteine unterteilt. Die Regeln der echten Welt sind fest einprogrammiert. Ein Lesezeichen muss einem User gehören, sonst streikt das Programm sofort.

Mein ganzer Stolz: Der Code erreicht **PHPStan Level 10 (mit 0 Fehlern)**. Das ist die absolut höchste und strengste Stufe, die es zur Code-Analyse gibt. Der Code ist mathematisch bewiesen extrem robust. Versteckte Abstürze gehören der Vergangenheit an!

Was hat sich am Code geändert? Kurz gesagt: alles xD
Wen es genauer interessiert, kann hier nachschauen: [Vergleich zum V5-Release](https://github.com/RaptorXilef/twokinds.4lima.de/compare/v5.0.0-alpha.23...v6.19.5)

---

Ich bin wahnsinnig stolz auf dieses Update und hoffe, euch gefallen die neuen Funktionen genauso gut wie mir. Loggt euch ein, testet das neue Profil, probiert die Wischgesten am Handy aus und sagt mir, was ihr denkt!

Viel Spaß auf [https://twokinds.4lima.de/](https://twokinds.4lima.de/) Version 6
Die neue Webseite ist ab genau jetzt online. :-)

Euer Felix
