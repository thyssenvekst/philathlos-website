# T.C. Philathlos München e.V. – Website (Relaunch 2026)

Statische Website ohne Framework, ohne Cookies, ohne Tracking, ohne externe Server.
Läuft auf jedem Webspace (Apache empfohlen wegen `.htaccess`).

## Ordner

| Pfad | Inhalt |
|---|---|
| `*.html` | fertige Seiten (werden erzeugt, **nicht direkt bearbeiten**) |
| `_quellen/seiten/*.html` | Inhalt jeder Seite (nur `<main>`), oben ein Kopfblock mit Titel und Beschreibung |
| `_quellen/build.py` | setzt Kopf, Navigation, Fuß, Metadaten, Sitemap und Weiterleitungen zusammen |
| `assets/css/site.css` | komplettes Designsystem |
| `assets/js/site.js` | Menü, Galerie mit Lichtbild, Kontaktformular, Bewegung mit GSAP |
| `assets/js/lib/` | GSAP 3.12.5 und ScrollTrigger (selbst gehostet, kein CDN) |
| `assets/fonts/` | Vollkorn (Überschriften und Text, mit griechischem Teilset für φίλαθλος), Fira Sans (Bedienung, Tabellen, kleine Angaben); alle selbst gehostet, SIL Open Font License |
| `assets/img/` | Fotos, Wappen (`wappen.svg`), Galerie mit Vorschaubildern (`galerie/klein/`) |
| `.htaccess` | 301-Weiterleitungen der alten Adressen, Caching, Sicherheits-Header |
| `ueber_uns.html`, `partner.htm`, `impressum.htm`, `Integration.html` | Weiterleitungsseiten für alte Adressen (falls der Server `.htaccess` nicht kennt). `aktuelles.html` ist jetzt wieder eine echte Seite |
| `verwaltung/`, `api/`, `medien/` | Verwaltungsbereich für den Vorstand (PHP + SQLite): schwarzes Brett, Termine, Bilder und PDFs, Hinweis auf der Startseite, Benutzer, Sicherung. Einrichtung und Bedienung in `VERWALTUNG.md` |
| `VERWALTUNG.md` | Entscheidung, Voraussetzungen beim Hoster, Einrichtung, Sicherheit, Kurzanleitung für den Vorstand, Sicherung |
| `DESIGN.md` | Designsystem „Pergola“: Leitidee, Farbe, Schrift, Raster, Fotografie, Bausteine, Bewegung, Seiten, Responsive |

## Seiten bearbeiten

1. Text in `_quellen/seiten/<seite>.html` ändern.
2. Im Website-Ordner ausführen: `python3 _quellen/build.py`
3. Lokal ansehen: `python3 -m http.server 8000`, dann <http://localhost:8000> öffnen.

Navigation, Kontaktdaten im Kopf und Fuß, Domain und Jahr stehen zentral oben in `_quellen/build.py`.
CSS und JS bekommen beim Bauen automatisch eine Versionsnummer, damit Besucher nach Änderungen sofort die neue Fassung sehen.

## Designsystem in Kürze

**Idee „Pergola“:** Die Fotos führen, das Wappen gibt die Farben. Weiße Fläche, Vereinsfotos in wechselnden Kompositionen (Diptychon, Anschnitt, Trio, Hoch und klein, Paar, Wand), Blau und Gelb aus Wappen, Pergola und Biergartenbänken. Ersetzt die Entwürfe „Ägäis“ und „Platzraster“. Ausführlich in `DESIGN.md`.

**Farben** (Tokens in `:root` von `assets/css/site.css`): Papier `#ffffff`, Stein `#f1f2ef`, Tinte `#171c26`, Pergola-Blau `#1b3f94` (Fuß, Menü, Links), Bank-Gelb `#f5dc3a` (Hauptknopf, Schlussband).

**Schrift:** Vollkorn für Überschriften und Text, Fira Sans für Bedienung und kleine Angaben.

**Seiten:** Start, Verein, Anlage, Tennisschule, Clubleben, Aktuelles, Mitgliedschaft, Galerie, Kontakt, Impressum, Datenschutz, 404. Jede Seite beginnt mit einer eigenen Bildkomposition.

**Bewegung (GSAP 3.12.5, selbst gehostet):** Parallaxe im Diptychon der Startseite, Rundgang über die Anlage als gestapelte Stationen, Fotos im Seitenkopf öffnen sich beim Laden, Fotos im Inhalt öffnen sich einmal leicht beim Scrollen, das Vereinsmotto wird Wort für Wort dunkel; dazu Menü, Galerie-Filter, Lichtbild und Seitenwechsel per View Transition. Texte bewegen sich beim Scrollen nicht. Bei „Bewegung reduzieren“ ist alles sofort sichtbar.

## Verwaltung (schwarzes Brett und Termine)

Der Vorstand pflegt Mitteilungen, Termine, Dateien und den Hinweis auf der Startseite selbst unter `/verwaltung/`. Die Inhalte erscheinen sofort auf der Startseite („Am Aushang“, Hinweisband) und unter *Aktuelles* (schwarzes Brett, Termine, Archiv). Voraussetzung ist PHP 8.1+ mit SQLite beim Hoster; ohne PHP bleiben die festen Texte stehen. Alles Weitere in `VERWALTUNG.md`.

Lokal testen: `php -S 127.0.0.1:8770 _quellen/dev-router.php` im Website-Ordner, dazu eine `verwaltung/konfiguration.php` mit `'https_erzwingen' => false`.

## Veröffentlichen

- Alles außer `_quellen/`, `.git/` und den `*.md`-Dateien hochladen (die `.htaccess` sperrt diese Pfade zusätzlich). `verwaltung/konfiguration.php` wird auf dem Server angelegt, siehe `VERWALTUNG.md`.
- **HTTPS:** Die bisherige Seite ist nur per `http://` erreichbar. Beim Hoster ein SSL-Zertifikat (z. B. Let’s Encrypt) aktivieren, danach in der `.htaccess` die drei auskommentierten Zeilen für HTTPS/www einschalten.
- Nach dem Umzug die Sitemap (`https://www.philathlos.de/sitemap.xml`) in der Google Search Console einreichen.

## Offene Punkte für den Verein

Alle Inhalte stammen von der bisherigen Website, aus dem Projektordner oder von der Website der Tennisschule (rsc-tennis.de). Erfunden wurde nichts. Folgendes sollte der Verein prüfen oder liefern:

1. **Impressum:** vertretungsberechtigter Vorstand, Registernummer (VR), verantwortliche Person nach § 18 MStV, Klärung der Nummer „143/222/60661“ (stand als USt-IdNr. da, ist dem Format nach eine Steuernummer).
2. **Datenschutz:** Hosting-Anbieter und Speicherdauer der Logfiles eintragen, Text rechtlich prüfen lassen.
3. **Beiträge:** Jahresbeiträge stammen von der bisherigen Website. Bitte bestätigen.
4. **Mannschaften:** Liste ist die zuletzt veröffentlichte Meldung (Spieljahr 2014). Aktuelle Mannschaften und Links zu den BTV-Spielplänen ergänzen.
5. **Mitgliederzahl:** 296 (davon 156 Kinder und Jugendliche) ist ein älterer Stand. Aktualisieren oder streichen.
6. **Winterhalle:** „Tennishalle bei Iphitos, Aumeisterweg 10“ stammt von der Website der Tennisschule (Stand 2014). Bitte bestätigen.
7. **Partner:** Liste (Christos Tennisschule, Beach-Arena, Babolat, dbtv, H:G Hochschule) von der bisherigen Website. Aktuelle Partner bestätigen, gern mit Logos als SVG.
8. **Aussage „einziger Tennisverein in München mit so vielen Beachsportarten“:** stand so auf der bisherigen Website. Bitte prüfen, ob sie noch zutrifft.
9. **Fotos:** Die vorhandenen Bilder sind authentisch, aber älter und nur ca. 800 Pixel breit. Ein halber Tag mit einer Fotografin oder einem Fotografen im Sommer (Plätze, Beachfelder, Training, Biergarten am Abend) würde die Seite deutlich aufwerten. Die Seite ist so gebaut, dass neue Bilder einfach ersetzt werden können (gleicher Dateiname, gern 2000 Pixel breit).
10. **Einwilligungen:** Auf einigen Galeriebildern sind Kinder erkennbar (z. B. Gruppenfoto). Die Bilder waren schon öffentlich, trotzdem bitte prüfen, ob Einwilligungen der Eltern vorliegen.
11. **Termine:** Für Feriencamps und Turniere gibt es keine Daten auf der alten Seite. Die neue Seite „Aktuelles“ (`_quellen/seiten/aktuelles.html`) führt deshalb nur, was regelmäßig stattfindet. Sobald Termine feststehen, gehören sie in die Spalte „termine__wann“ der jeweiligen Zeile.
12. **Aufnahmeantrag/Beitragsordnung:** falls vorhanden, als PDF bereitstellen und auf der Seite Mitgliedschaft verlinken.
13. **Seite „Anlage“:** Neu zusammengestellt aus bekannten Angaben (fünf Sandplätze, Beachfelder, Bistro, Service, Parkplätze, U6, Winterhalle). Eine Platzbeschreibung (z. B. Flutlicht, Platznummern, Öffnungszeiten) gibt es nicht, weil dazu keine Quelle vorliegt.
14. **Foto „Mach mit“ auf der Vereinsseite:** Bildunterschrift nennt das Projektjahr 2011 laut Projektbeschreibung. Bitte bestätigen, dass das Foto aus dieser Saison stammt.
