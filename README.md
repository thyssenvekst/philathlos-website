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
| `assets/fonts/` | Piazzolla und Schibsted Grotesk (selbst gehostet, SIL Open Font License) |
| `assets/img/` | Fotos, Wappen (`wappen.svg`), Galerie mit Vorschaubildern (`galerie/klein/`) |
| `.htaccess` | 301-Weiterleitungen der alten Adressen, Caching, Sicherheits-Header |
| `ueber_uns.html`, `partner.htm`, `impressum.htm`, `Integration.html`, `aktuelles.html` | Weiterleitungsseiten für alte Adressen (falls der Server `.htaccess` nicht kennt) |

## Seiten bearbeiten

1. Text in `_quellen/seiten/<seite>.html` ändern.
2. Im Website-Ordner ausführen: `python3 _quellen/build.py`
3. Lokal ansehen: `python3 -m http.server 8000`, dann <http://localhost:8000> öffnen.

Navigation, Kontaktdaten im Kopf und Fuß, Domain und Jahr stehen zentral oben in `_quellen/build.py`.
CSS und JS bekommen beim Bauen automatisch eine Versionsnummer, damit Besucher nach Änderungen sofort die neue Fassung sehen.

## Designsystem in Kürze

**Idee „Grundlinie“:** Der Sandplatz ist die Architektur der Seite. Auf der Startseite steht ein maßstäblich gezeichneter Platz (23,77 × 10,97 m) als Bühne, das Foto sitzt in der linken Platzhälfte. Die Grundlinie mit Mittelzeichen ordnet Listen und Abschnitte.

**Farben** (Tokens in `:root` von `assets/css/site.css`):

| Name | Wert | Einsatz |
|---|---|---|
| Papier | `#f8f7f2` | Grundfläche |
| Leinen | `#ece6d8` | Wechselflächen |
| Tinte | `#101a33` | Überschriften |
| Text | `#384054` | Fließtext |
| Wappenblau | `#1d3f93` | Links, Knöpfe |
| Nacht | `#0e1d42` | Startbereich, Abendabschnitt, Fuß |
| Ziegel | `#b65c3d` | nur als Platzfläche |
| Ball | `#ead544` | nur als Signal (Ballpunkte, Hauptknopf auf Nacht) |

**Schriften:** Piazzolla (Überschriften und Leittexte, mit griechischen Zeichen für φίλος und άθλος), Schibsted Grotesk für Fließtext und Bedienung.

**Bewusst weggelassen:** Großbuchstaben-Rubriken über jeder Überschrift, angehängte Pfeile an Links, Nummern ohne Reihenfolge, einzelne farbige oder kursive Akzentwörter, Schatten-Karten, Verläufe.

**Bewegung (GSAP):** Beim Laden zeichnet sich der Platz, die Überschrift steigt zeilenweise auf. Beim Scrollen blenden Inhalte einmalig ein, Bilder öffnen sich von unten; sanfte Parallaxe nur ab 900 px Breite. Nichts läuft dauerhaft. Wer „Bewegung reduzieren“ eingestellt hat, sieht alles sofort. Lädt GSAP nicht, ist trotzdem alles sichtbar.

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
11. **Termine:** Für Feriencamps und Turniere gibt es keine Daten auf der alten Seite. Sobald Termine feststehen, können sie im Abschnitt „Am Aushang“ auf der Startseite ergänzt werden.
12. **Aufnahmeantrag/Beitragsordnung:** falls vorhanden, als PDF bereitstellen und auf der Seite Mitgliedschaft verlinken.
