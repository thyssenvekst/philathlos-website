# Designsystem „Platzraster“ – T.C. Philathlos München e.V.

Stand: November 2026. Gilt für alle Seiten in diesem Ordner. Technische Quelle der Wahrheit sind die Tokens in `assets/css/site.css` (`:root`); dieses Dokument erklärt, warum sie so sind und wie man sie einsetzt.

---

## 1. Leitidee

**Der Platz ist das Raster.** Philathlos ist kein Club mit Stadion und Geschichte in Bronze, sondern fünf Sandplätze, Beachfelder mit Palmen und ein Biergarten an der U6. Die Identität kommt deshalb aus dem Ort selbst, nicht aus einem Lifestyle-Versprechen:

- **Ziegelmehl** – die Oberfläche der fünf Sandplätze. Sie erscheint als große, flache Fläche, nie als Akzentfarbe in Knöpfen oder Links.
- **Kreidelinie** – die weißen Linien des Platzes. Sie gliedern die wichtigsten Flächen (Startseite, Beiträge, Fehlerseite) in echten Platzmaßen: 23,77 × 10,97 m, Aufschlaglinie 6,40 m vom Netz.
- **Tanne** – das Dunkelgrün von Windschutz und Bäumen rund um die Anlage. Abend, Fuß, Menü.
- **Wappengelb** – der Ring des Vereinswappens. Einzige Signalfarbe: Hauptknopf, Fokus, Markierung.
- **Kreide** – kühles, leicht grünliches Weiß als Grundfläche. Bewusst kein Creme-Ton.

Haltung: sportlich ohne Aggressivität, herzlich ohne Urlaubskitsch, zurückhaltend selbstbewusst. Griechisch ist der Name (φίλαθλος, „Freund des Sports“), nicht die Kulisse: Der griechische Bezug erscheint als Wort und im Wappen, nicht als Säulen, Mäander oder Flaggenblau.

**Ein mutiger Moment pro Website:** der Platz im Kopf der Startseite. Alles andere bleibt ruhig, typografisch und fotografisch.

---

## 2. Schrift

| Rolle | Schrift | Einstellung |
|---|---|---|
| Überschriften H1–H3, große Adressen, Zahlen | **Archivo** (variabel, Achsen `wdth` 62–125, `wght` 100–900) | breit (`wdth` 112), `wght` 520, Laufweite −0,02 em, Zeilenabstand 1,02–1,1 |
| Navigation, Knöpfe, Tabellen, Formular, kleine Angaben | Archivo | leicht schmal (`wdth` 92), `wght` 560, normale Groß-/Kleinschreibung |
| Fließtext, Leads, Zitate | **Newsreader** (variabel, `opsz` 6–72, `wght` 200–800, Kursive) | 18 px / 1,6; Leads `opsz` automatisch, `wght` 380 |
| Griechisches Wort φίλαθλος | Commissioner, nur griechische Glyphen (`unicode-range`) | ausgestellte Strichenden (`FLAR` 100), wie Steininschrift |

Warum: Die breite Archivo erinnert an aufgemalte Platz- und Vereinsbeschriftung, wirkt sportlich und architektonisch, ohne laut zu sein. Newsreader trägt den Text wie ein gut gesetztes Vereinsheft. Zwei klar verschiedene Familien, kein Serifen-Display-Klischee.

Alle Schriften liegen selbst gehostet in `assets/fonts/` (SIL Open Font License, Lizenzen daneben). Kein Google-Fonts-Server.

**Regeln**
- Keine Versalien-Etiketten über Überschriften. Kleine Angaben stehen in normaler Schreibung.
- Keine einzelnen Wörter in Überschriften farbig oder kursiv hervorheben.
- Kursiv (Newsreader) nur für Zitate und das Vereinsmotto.
- Zeilenlänge Fließtext max. 68 Zeichen (`--mass-text: 34em` bei 18 px).
- Zahlen in Tabellen: `font-variant-numeric: tabular-nums`.

### Typo-Skala

| Token | Größe | Einsatz |
|---|---|---|
| `--t-h1` | `clamp(2.4rem, 1.5rem + 3.8vw, 4.75rem)` | Seitentitel |
| `--t-h2` | `clamp(1.9rem, 1.35rem + 2.2vw, 3.2rem)` | Abschnitte |
| `--t-h3` | `clamp(1.3rem, 1.15rem + .6vw, 1.7rem)` | Unterpunkte, Listenköpfe |
| `--t-gross` | `clamp(1.3rem, 1.1rem + .8vw, 1.75rem)` | Leads, Zitate (Newsreader) |
| `--t-text` | `1.125rem` | Fließtext |
| `--t-ui` | `.9375rem` | Navigation, Knöpfe, Tabellen |
| `--t-klein` | `.8125rem` | Hinweise, Rechtliches |

Hierarchie: genau eine H1 pro Seite, H2 je Abschnitt, H3 darunter. Keine Ebene wird übersprungen.

---

## 3. Farben

| Token | Wert | Einsatz | Kontrast |
|---|---|---|---|
| `--kreide` | `#f2f3ee` | Grundfläche | – |
| `--kreide-tief` | `#e4e7de` | Wechselfläche | – |
| `--tinte` | `#13241b` | Text, Überschriften | 14,5 : 1 auf Kreide |
| `--tinte-leise` | `#46544b` | Nebentext | 7,2 : 1 auf Kreide |
| `--tanne` | `#1c3a2b` | dunkle Flächen (Abend, Menü, Fuß) | Text `--auf-tanne` 9,4 : 1 |
| `--ziegel` | `#b0461f` | Platzfläche | Weiß darauf 5,6 : 1 |
| `--ziegel-text` | `#a8441d` | seltene Textakzente auf Kreide | 5,4 : 1 |
| `--gelb` | `#f3d23c` | Hauptknopf, Fokusring, Markierung | Tinte darauf 10,9 : 1 |
| `--linie` | `rgb(19 36 27 / .15)` | Trennlinien | – |

Keine Verläufe, keine Schatten unter Flächen, keine Transparenz-Effekte über Fotos.

---

## 4. Raster, Abstände, Breiten

- **12 Spalten**, Spaltenabstand `--spalt: clamp(1rem, 2vw, 2rem)`.
- **Rahmen**: `--max: 88rem` (1408 px), Seitenrand `--rand: clamp(1.25rem, 5vw, 5rem)`.
- **Inhaltsbreiten** wechseln bewusst, damit nicht jeder Abschnitt gleich aussieht:
  - *voll* – ganze Fensterbreite (Platz, Abend)
  - *weit* – 12 Spalten
  - *versetzt* – Inhalt beginnt bei Spalte 4, 5 oder 7 (Editorial-Einzug)
  - *schmal* – max. `--mass-text` für Rechtstexte und lange Absätze
- **Vertikale Abstände**: `--luft: clamp(5rem, 11vw, 10rem)` zwischen Abschnitten, `--luft-klein: clamp(3rem, 6vw, 5rem)` innerhalb. Abstände kommen nur aus Tokens.
- Eine Randspalte (Spalte 1–3) trägt auf Unterseiten die Abschnittsüberschrift, der Inhalt läuft ab Spalte 4 oder 5. Sie wechselt nicht die Seite, damit man sich orientiert; die Komposition rechts davon wechselt.

---

## 5. Bilder

Die Vereinsfotos sind echt, aber ältere Kompaktkamera-Aufnahmen (ca. 800 px breit). Deshalb:

- Fotos **nie größer als ca. 1000 px CSS-Breite**, kein Vollbild-Foto. Größe entsteht über Fläche und Komposition, nicht über Pixel.
- Formate: **4 : 3** (Original, Standard), **3 : 4** (Hochformat-Ausschnitt), **2 : 1** (Panorama). Ausschnitte per `object-position`, nie verzerrt.
- Keine Rundungen, keine Schatten, keine Filter-Looks. Leichte Vereinheitlichung über `--foto-filter: saturate(.92) contrast(1.03)`.
- Bildunterschriften nur, wenn sie Information tragen (Ort, Anlass), in Archivo `--t-klein`.
- Jede Seite nutzt ihre eigenen Hauptbilder; Wiederholungen nur in der Galerie.
- Breite und Höhe stehen im HTML, unterhalb des ersten Bildschirms `loading="lazy"`.

---

## 6. Komponenten

| Komponente | Klasse | Beschreibung |
|---|---|---|
| Kopf | `.kopf` | Wappen + Wortmarke links, Navigation, gelber Knopf „Schnupperstunde“. Verschwindet beim Herunterscrollen, kommt beim Hochscrollen zurück |
| Menü | `.menue` | natives `<dialog>`, Fläche Tanne, Links groß in Archivo, Kontakt unten |
| Platz | `.platz` | Platzfläche mit Linien in echten Maßen (SVG). Quer ab 1100 px, Text im linken Feld; darunter Text über dem Platz; unter 600 px hochkant |
| Seitenkopf | `.kopf-*` | jede Unterseite hat einen eigenen Aufbau (siehe Abschnitt 8) |
| Knopf | `.knopf` | eckig, Archivo. `--gelb` (Hauptaktion), `--rand` (Linie), `--hell` (auf dunkel). Hover: Fläche schiebt sich ein; Druck: 0,98 |
| Textlink | `.weiter` | Unterstrich zeichnet sich beim Hover von links |
| Index | `.index` | Liste großer Links mit Bild, das beim Hover/Fokus wechselt (Startseite „Was es bei uns gibt“) |
| Abschnittskopf | `.abschnitt__kopf` | H2 in der Randspalte, optional Einleitung |
| Tabelle | `.preise` | Beiträge, Zeilen mit Kreidelinien, Beträge rechtsbündig |
| Zeitleiste | `.chronik` | waagerecht ab Tablet, senkrecht auf dem Telefon; nur echte Jahreszahlen |
| Schritte | `.schritte` | nummeriert, weil es eine Reihenfolge ist |
| Verzeichnis | `.verzeichnis` | Telefon, E-Mail, Adresse als große Archivo-Zeilen |
| Formular | `.formular` | Beschriftungen sichtbar über dem Feld, Fehler direkt unter dem Feld |
| Galerie | `.galerie` | Filter + ungleichmäßiges Raster, Lichtbild als `<dialog>` |
| Fuß | `.fuss` | Tanne, Adresse groß, Kontakt, Seiten, Verbandsnummern |

Keine Karten mit Schatten, keine Icon-Raster, keine Drei-Spalten-Kachelreihen.

---

## 7. Bewegung (GSAP 3.12.5, selbst gehostet)

Grundsatz: **ein inszenierter Moment, danach nur Antworten auf das Scrollen und auf die Hand.**

| Wo | Was |
|---|---|
| Startseite, Platz | Linien werden gezogen wie nach dem Abziehen des Platzes (SVG-Strichlänge), Überschrift und Foto folgen. Einmal beim Laden |
| Seitenköpfe | Titel steigt auf, Bild öffnet sich (Clip-Path). Einmal beim Laden |
| Bilder im Inhalt | öffnen sich beim ersten Erscheinen von unten, leichte Parallaxe (±5 %) nur ab 900 px |
| Überschriften H2 | heben sich einmal um 24 px |
| Index | Bild wechselt weich (Hover/Fokus), Maus- und Tastaturbedienung |
| Beiträge | Kreidelinien zeichnen sich einmal |
| Kopf | blendet beim Herunterscrollen aus |
| Menü | Links steigen gestaffelt auf |
| Galerie | Filter blendet Kacheln ein, Lichtbild gleitet in Blätterrichtung |
| Seitenwechsel | Cross-Document View Transition (Überblendung), wo der Browser sie kann |
| Mikro | Knopf-Fläche, Unterstrich, Foto-Zoom 1,03 im Rahmen |

Regeln: Nur `transform`, `opacity`, `clip-path`. Inhalte starten nie unsichtbar ohne JS-Absicherung (`html.bewegt` wird nach 2,5 s ohne GSAP entfernt). `prefers-reduced-motion: reduce` → keine GSAP-Bewegung, CSS-Übergänge auf 0,01 ms, View Transitions aus.

---

## 8. Seitendramaturgie

| Seite | Kopf | Danach |
|---|---|---|
| Start | Platz mit Linien, Foto im rechten Feld | φίλαθλος → Index „Was es bei uns gibt“ → Kinder und Jugend → Abend (Tanne) → Beiträge (Ziegel) → Am Aushang → Anfahrt |
| Verein | Wappen groß + Titel | Name → Ziele → Chronik (waagerecht) → Auf einen Blick → Partner |
| Anlage | Panorama in „weit“, Kennzahlen darunter | Sandplätze → Beachfelder → Clubhaus → Sommer/Winter → Anreise |
| Tennisschule | Titel weit, Foto versetzt | Trainer → Programm in zwei Spalten → Feriencamps → Mannschaften → Kontakt |
| Clubleben | Bildkomposition aus drei Fotos | Sieben Sportarten → Bistro (Tanne) → Feiern bei uns |
| Aktuelles | typografischer Aushang auf Tanne | Sommerferien → Turniere → Gesellig → Kontakt für Termine |
| Mitgliedschaft | Titel + Kernaussage | Beiträge auf Ziegel → drei Schritte → Schnupperstunde |
| Galerie | knapper Titel | Filter + Raster + Lichtbild |
| Kontakt | Telefonnummern als Typografie | Formular → Anfahrt |
| Impressum, Datenschutz | schlichter Titel | Rechtstext in schmaler Spalte |
| 404 | „Netzball“ auf Platzfläche | Links |

---

## 9. Responsive

| Breite | Verhalten |
|---|---|
| ≥ 1280 | volles 12er-Raster, Randspalte, Index mit stehendem Bild |
| 1100–1279 | wie oben, engere Abstände; Telefonnummer im Kopf entfällt |
| 900–1099 | Navigation ins Menü, Platz: Text über dem Platz, Parallaxe aus unter 900 |
| 600–899 | zweispaltig wo sinnvoll, Randspalte wird zur Zeile über dem Inhalt |
| < 600 | einspaltig, Platz hochkant, Knöpfe volle Breite, Index-Bilder in der Zeile, Chronik senkrecht |

Keine horizontale Scrollbar bei 320 px. Tippziele mindestens 44 px.

---

## 10. Technik

- Statisch, ohne Framework. Inhalte in `_quellen/seiten/*.html`, Kopf/Fuß/Meta in `_quellen/build.py`, danach `python3 _quellen/build.py`.
- Kein Cookie, kein Tracking, keine externen Server. OpenStreetMap nur als Link.
- Semantik: `header`, `nav`, `main`, `section` mit `aria-labelledby`, `footer`; Sprunglink; sichtbarer Fokus in Wappengelb.
