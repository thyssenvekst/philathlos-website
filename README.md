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
| `assets/fonts/` | Archivo (Überschriften, Bedienung), Newsreader (Text), Commissioner nur für griechische Buchstaben; alle selbst gehostet, SIL Open Font License |
| `assets/img/` | Fotos, Wappen (`wappen.svg`), Galerie mit Vorschaubildern (`galerie/klein/`) |
| `.htaccess` | 301-Weiterleitungen der alten Adressen, Caching, Sicherheits-Header |
| `ueber_uns.html`, `partner.htm`, `impressum.htm`, `Integration.html` | Weiterleitungsseiten für alte Adressen (falls der Server `.htaccess` nicht kennt). `aktuelles.html` ist jetzt wieder eine echte Seite |
| `DESIGN.md` | Designsystem „Platzraster“: Leitidee, Schrift, Farben, Raster, Bilder, Komponenten, Bewegung, Responsive |

## Seiten bearbeiten

1. Text in `_quellen/seiten/<seite>.html` ändern.
2. Im Website-Ordner ausführen: `python3 _quellen/build.py`
3. Lokal ansehen: `python3 -m http.server 8000`, dann <http://localhost:8000> öffnen.

Navigation, Kontaktdaten im Kopf und Fuß, Domain und Jahr stehen zentral oben in `_quellen/build.py`.
CSS und JS bekommen beim Bauen automatisch eine Versionsnummer, damit Besucher nach Änderungen sofort die neue Fassung sehen.

## Designsystem in Kürze

**Idee „Platzraster“:** Der Platz ist das Raster. Ziegelmehl (Sandplatz), Kreidelinie, Tanne (Windschutz und Bäume) und Wappengelb. Auf der Startseite ist der Kopf ein Tennisplatz in echten Maßen, dessen Linien beim Laden gezogen werden; das Foto liegt im rechten Feld zwischen Netz und Grundlinie. Ausführlich in `DESIGN.md`.

**Farben** (Tokens in `:root` von `assets/css/site.css`): Kreide `#f2f3ee`, Tinte `#13241b`, Tanne `#1c3a2b`, Ziegel `#b0461f`, Wappengelb `#f3d23c` (nur Hauptknopf, Fokus, Markierung).

**Schrift:** Archivo breit für Überschriften und Bedienung, Newsreader für Text, φίλαθλος in Commissioner.

**Seiten:** Start, Verein, Anlage (neu), Tennisschule, Clubleben, Aktuelles (neu), Mitgliedschaft, Galerie, Kontakt, Impressum, Datenschutz, 404. Jede Unterseite hat einen eigenen Seitenkopf.

**Bewegung (GSAP 3.12.5, selbst gehostet):** Linien des Platzes, Seitenköpfe, einmalige Text- und Bild-Reveals, leichte Parallaxe ab 900 px, Kreidelinien bei den Beiträgen, Menü, Galerie-Filter, Lichtbild; Seitenwechsel per View Transition. Bei „Bewegung reduzieren“ ist alles sofort sichtbar.

## Veröffentlichen

- Alles außer `_quellen/`, `.git/`, `README.md`, `CLAUDE.md` hochladen (die `.htaccess` sperrt diese Pfade zusätzlich).
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
