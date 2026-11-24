# Designsystem „Pergola“ – T.C. Philathlos München e.V.

Stand: November 2026. Ersetzt die Entwürfe „Ägäis“ und „Platzraster“ vollständig. Technische Quelle der Wahrheit sind die Tokens in `assets/css/site.css` (`:root`). Dieses Dokument erklärt, warum sie so sind und wie man sie einsetzt.

---

## 1. Leitidee

**Die Fotos führen, das Wappen gibt die Farben.** Philathlos besteht aus fünf Sandplätzen, Beachfeldern mit Palmen und einem Biergarten unter Bäumen. Die Vereinsfotos zeigen genau das, und sie zeigen schon die Vereinsfarben: die blaue Pergola am Bistro, die gelben Bänke im Biergarten. Dieselben zwei Farben trägt das Wappen (blaues Feld, gelber Ring). Die Website erfindet deshalb keine Farbwelt, sondern nimmt diese beiden Farben und lässt den Fotos den Raum.

Was die Gestaltung von iphitos.de gelernt hat (ohne Elemente zu übernehmen):

- Ein Verein wirkt etabliert, wenn er nicht „designt“ wirkt: weiße Fläche, eine ruhige Antiqua, echte Fotos groß, Überschriften in normaler Schreibung und vernünftiger Größe.
- Die Vereinsfarbe wird funktional eingesetzt (Kopf, Knöpfe, Fuß), nicht als Dekoration.
- Der Inhalt wird vollständig gezeigt, nicht in Karten zerlegt.

Was Philathlos anders macht als iphitos.de: Fotos in wechselnden, asymmetrischen Kompositionen statt Kachelreihen, ein klares Satzraster mit einer Lesespalte und ein einziger inszenierter Bewegungsmoment.

**Bewusst nicht mehr vorhanden** (gegenüber „Platzraster“): gezeichneter Tennisplatz, Linien und Kreidestriche, farbige Abschnittsbänder in Grün und Ziegel, breite Grotesk-Überschriften, Index-Liste mit wechselndem Bild, Seitenkopf-Signatur.

---

## 2. Farbe

| Token | Wert | Herkunft | Einsatz |
|---|---|---|---|
| `--papier` | `#ffffff` | – | Grundfläche aller Seiten |
| `--stein` | `#f1f2ef` | hellgrau, neutral | eine Fläche pro Seite (Besuch, Beiträge), Formularfelder |
| `--tinte` | `#171c26` | – | Text |
| `--tinte-leise` | `#464d57` | – | Nebentext (8,6 : 1 auf Weiß, 7,3 : 1 auf Stein) |
| `--pergola` | `#1b3f94` | Wappen, Pergola am Bistro | Links, Überschrift-Akzente nie; Fuß, Menü (Weiß darauf 9,6 : 1) |
| `--pergola-tief` | `#132e6e` | dunkler | Hover auf Blau |
| `--bank` | `#f5dc3a` | Wappenring, Biergartenbänke | Hauptknopf, Schlussband, Fokusring auf Blau (Tinte darauf 12 : 1) |
| `--fokus` | `#1b3f94` | – | Fokusring auf Weiß; Textlinks bekommen beim Tastaturfokus zusätzlich eine gelbe Markierung |

Regeln: keine Verläufe, keine Schatten, keine Transparenzflächen über Fotos. Gelb ist nie Textfarbe auf Weiß. Pro Seite höchstens eine Steinfläche; das gelbe Schlussband und der blaue Fuß bilden zusammen den Abschluss (wie Ring und Feld im Wappen).

---

## 3. Schrift

| Rolle | Schrift | Einstellung |
|---|---|---|
| Überschriften, Fließtext, Leads, Adressen | **Vollkorn** (variabel, `wght` 400–900, Kursive; griechisches Teilset für φίλαθλος) | Überschriften `wght` 450, normale Schreibung, Laufweite −0,01 em; Text 1,1875 rem / 1,62 |
| Navigation, Knöpfe, Tabellen, Formular, kleine Angaben | **Fira Sans** (400, 500, 600) | 500, 0,9375–1 rem, normale Schreibung, keine Versalien-Labels. Humanistische Grotesk aus der Tradition der deutschen Informationsgestaltung (Spiekermann), passt zu Vollkorn und wirkt nicht nach Start-up |

Beide Schriften liegen unter `assets/fonts/` (OFL), keine externen Dienste.

Skala (rem, fließend mit `clamp`): H1 2,25 → 3,5 · H2 1,75 → 2,5 · H3 1,25 → 1,5 · Lead 1,25 → 1,5 · Text 1,0625 → 1,1875 · UI 0,9375 · klein 0,875.

Haltung: Überschriften sind Sätze, keine Schlagworte. Kursiv nur für das Vereinsmotto und den Lead im Seitenkopf. Ziffern im Text als Mediävalziffern, in Tabellen als Tabellenziffern (`font-variant-numeric`).

---

## 4. Raster und Abstand

- 12 Spalten, Außenrand `clamp(1.25rem, 4.5vw, 4.5rem)`, Spaltenabstand `clamp(1rem, 2vw, 2rem)`, Inhaltsbreite bis 90 rem.
- **Lesespalte:** Text steht immer linksbündig in einer schmalen Spalte (höchstens 34 em), nie zentriert.
- **Abschnitte** werden nur durch Weißraum getrennt (`--luft`: 5,5 → 10 rem), nie durch Linien.
- Haarlinien gibt es nur dort, wo sie Information tragen: Zeilen in Tabellen und Listen.

---

## 5. Fotografie

Die Vereinsfotos sind Schnappschüsse, nur rund 800 px breit. Darum: groß, aber nie größer als etwa 1000 CSS-Pixel; Wirkung entsteht durch Anzahl, Anschnitt und Komposition, nicht durch ein einzelnes hochskaliertes Riesenbild.

Keine Rundungen, keine Rahmen, keine Filter, keine Schrift auf Fotos, keine Bildunterschriften (Ausnahme: eine Angabe, die eine Tatsache trägt).

| Komposition | Aufbau | Wo |
|---|---|---|
| **Diptychon** | zwei Fotos über die volle Fensterbreite im Verhältnis 5 : 3, Tag groß, Abend schmal und hoch, 4 px Fuge; darunter Titel, Einleitung und Aktion einspaltig | Kopf der Startseite |
| **Anschnitt** | ein Foto läuft bis an den linken oder rechten Fensterrand, Text in der freien Seite, vertikal versetzt | Verein, Anlage, Startseite |
| **Trio** | drei Fotos in drei Größen (4:5, 1:1, 4:3), Oberkanten auf einer Linie, jedes ein Link | Startseite „Auf der Anlage“ |
| **Hoch und klein** | ein hohes 4:5-Foto, daneben Text und ein kleines Quadrat, Unterkanten bündig | Startseite Jugend, Tennisschule |
| **Paar** | zwei Fotos gleicher Proportion, Oberkanten bündig | Abend, Clubleben |
| **Wand** | viele Fotos dicht an dicht, 4 px Fuge | Galerie, Clubleben |

Zwei benachbarte Abschnitte haben nie dieselbe Komposition.

---

## 6. Bausteine

| Baustein | Beschreibung |
|---|---|
| Kopf (`.kopf`) | geteilte Navigation: vier Wörter links, Wappen und Name in der Mitte, vier Wörter rechts. Unter 1180 px: Wappen links, „Menü“ rechts. Verschwindet beim Herunterscrollen, kommt beim Hochscrollen. |
| Telefonleiste (`.leiste`) | nur mobil: „Anrufen“ und „Schnupperstunde“ fest am unteren Rand, erst nach dem ersten Bildschirm. |
| Knopf (`.knopf`) | rechteckig, Fira Sans 500. Gelb mit Tinte (Hauptaktion), Blau mit Weiß, Umriss. Gibt beim Drücken 1 px nach. |
| Textlink (`.weiter`) | Tinte, Unterstrich in Blau, der sich beim Hover von links neu zieht. |
| Zeilenliste (`.zeilen`) | Haarlinie unter jeder Zeile; für Beiträge, Aushang, Kontakt, Qualifikationen, die drei Schritte. |
| Abschnittskopf (`.kopfzeile`) | Überschrift, darunter ein Satz und ein Link in der Lesespalte. Nie Überschrift links und Einleitung rechts. |
| Adresse groß (`.adresse-gross`) | der einzige große Schriftmoment: „Föhringer Ring 5“ in Vollkorn, verlinkt zur Route. Nur auf der Kontaktseite. |
| Motto (`.motto`) | „Wo Spaß im Spiel ist, kommt das Interesse von allein.“ Wort für Wort beim Scrollen von grau zu Tinte. |
| Schlussband (`.schluss`) | gelbe Fläche mit einer Einladung zur Schnupperstunde und einem quadratischen Foto. |
| Fuß (`.fuss`) | blaue Fläche: Adresse groß, Weg, Telefon, Post, Seiten, Verbandsnummern, Recht. |

---

## 7. Bewegung (GSAP, selbst gehostet)

| Moment | Was passiert | Wann |
|---|---|---|
| Startseite | die beiden Fotos des Diptychons öffnen sich nacheinander von unten, danach erscheint der Titel | einmal beim Laden |
| Seitenköpfe | erstes Foto öffnet sich von unten | einmal beim Laden |
| Fotos im Inhalt | öffnen sich leicht (Ausschnitt von 8 % auf 0), Bild skaliert von 1,06 auf 1 | einmal beim Hineinscrollen |
| Motto | Wörter werden mit dem Scrollen dunkel | an die Scrollposition gebunden |
| Parallaxe im Diptychon | das Tagfoto wandert langsamer als die Seite, das Abendfoto etwas schneller: Tiefe statt Animation | an die Scrollposition gebunden, ab 900 px |
| Rundgang (gestapelte Stationen) | Sandplätze, Beachfelder, Bistro schieben sich beim Scrollen übereinander wie Zettel am Aushang; die bedeckte Station tritt um 3,5 % zurück | `position: sticky` plus GSAP, ab 900 px Breite und 640 px Höhe; darunter normale Abfolge |
| Seitenwechsel | Kopf bleibt stehen, Inhalt blendet mit 14 px Hub ein | CSS View Transitions, ohne Bewegungswunsch aus |
| Hover | verlinkte Fotos zoomen auf 1,03, Unterstriche ziehen sich, Knöpfe geben nach | auf Zeiger |

Texte bewegen sich beim Scrollen nicht.

**Papierstruktur:** Stein- und Gelbflächen tragen ein sehr feines, statisches Rauschen (SVG, ca. 1 KB), damit sie nach Papier wirken statt nach Bildschirmfläche. Keine Verläufe.

**Bewusst nicht verwendet:** Logo-Laufband (es gibt keine Partnerlogos, ein laufender Text wäre reine Dekoration), Dithering und Halbton (würden die echten Vereinsfotos verfremden), eigener Mauszeiger, Text-Scramble, Sound. `prefers-reduced-motion`: keine Animation, alles sofort sichtbar, das Motto in voller Tinte. Ohne JavaScript ist alles sichtbar (Schutzklasse `html.bewegt` wird nach spätestens 2,5 s entfernt).

---

## 8. Seiten

| Seite | Einstieg | Abfolge der Kompositionen |
|---|---|---|
| Start | Diptychon Tag/Abend, darunter Titel und Schnupperstunde | Verein mit Anschnitt → Motto → Trio Anlage (Steinfläche) → Hoch und klein Jugend → Paar Abend → Beiträge → Aushang → Schlussband |
| Verein | Titel, dann Anschnitt rechts (Gruppenfoto) | Name → Ziele → Chronik (echte Abfolge, daher mit Jahreszahlen) → Auf einen Blick → Partner |
| Anlage | Titel, Panorama im Anschnitt links | Paar Sandplätze → Hoch Beachfelder → Anschnitt Bistro → Sommer/Winter → Anreise |
| Tennisschule | Hoch und klein mit Titel | Christos → Programm → Camps → Mannschaften → Kontakt |
| Clubleben | Wand aus vier Fotos | Sportarten → Bistro (Anschnitt) → Feste → Feiern |
| Aktuelles | Titel und Aushang-Hinweis | schwarzes Brett und Termine (aus der Verwaltung) → was jedes Jahr stattfindet → Archiv (aufklappbar) → Kontakt mit Foto |
| Mitgliedschaft | Titel, „Keine Aufnahmegebühr“, Anschnitt | Beiträge (Steinfläche) → drei Schritte (echte Abfolge, daher nummeriert) |
| Galerie | Titel, Filter | Wand mit Lichtbild |
| Kontakt | Adresse groß, Nummern | Formular → Anfahrt mit Foto |
| Impressum, Datenschutz | Titel | Lesespalte |

---

## 9. Responsive

| Breite | Verhalten |
|---|---|
| ab 1180 px | geteilte Navigation, alle Kompositionen wie beschrieben |
| 900–1179 px | Kopf mit Menü-Knopf, Kompositionen bleiben, Spalten enger |
| 600–899 px | Text über den Fotos, Paare bleiben nebeneinander, Trio wird 1 + 2 |
| unter 600 px | Diptychon als zwei Hochformate nebeneinander, alles andere einspaltig, Telefonleiste unten |

---

## 10. Technik

- Statischer Generator: Inhalte in `_quellen/seiten/*.html`, dann `python3 _quellen/build.py`.
- Kein Framework, keine Cookies, kein Tracking, keine externen Schriften oder Skripte.
- Native `<dialog>` für Menü und Lichtbild; Kontaktformular öffnet einen E-Mail-Entwurf.
- CSS Cross-Document View Transitions für sanfte Seitenwechsel, bei reduzierter Bewegung aus.
