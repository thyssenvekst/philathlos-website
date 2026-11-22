#!/usr/bin/env python3
"""
T.C. Philathlos München – Seitengenerator

Erzeugt die fertigen, statischen HTML-Seiten im Website-Ordner aus
  _quellen/seiten/*.html  (nur der Inhalt von <main>, oben ein Kopfblock)
und setzt Kopf, Navigation, Fuß und Metadaten auf allen Seiten einheitlich.

Aufruf (aus dem Website-Ordner oder von überall):
  python3 _quellen/build.py

Kein Framework, keine Abhängigkeiten, nur Python 3.
Die erzeugten .html-Dateien bitte nicht direkt bearbeiten, sondern die Quelle in
_quellen/seiten/ ändern und das Skript erneut ausführen.
"""
import hashlib
import json
import pathlib
import re

WURZEL = pathlib.Path(__file__).resolve().parent.parent
QUELLEN = WURZEL / "_quellen" / "seiten"
DOMAIN = "https://www.philathlos.de"
JAHR = "2026"

NAV = [
    ("verein.html", "Verein"),
    ("anlage.html", "Anlage"),
    ("tennisschule.html", "Tennisschule"),
    ("clubleben.html", "Clubleben"),
    ("aktuelles.html", "Aktuelles"),
    ("mitgliedschaft.html", "Mitgliedschaft"),
    ("galerie.html", "Galerie"),
    ("kontakt.html", "Kontakt"),
]

OSM = "https://www.openstreetmap.org/search?query=F%C3%B6hringer%20Ring%205%2C%2080805%20M%C3%BCnchen"

SYMBOLE = {
    # Keine Pfeile hinter Linktexten (Gestaltungsentscheidung); {{pfeil}} in Quellen wird entfernt.
    "pfeil": '',
    "telefon": '<svg class="symbol" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.6 3.5h2.6l1.5 4-2 1.4a11 11 0 0 0 6.4 6.4l1.4-2 4 1.5v2.6a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4.6 5.7a2 2 0 0 1 2-2.2z"/></svg>',
    "post": '<svg class="symbol" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3.5" y="5.5" width="17" height="13" rx="1"/><path d="m4 6.5 8 6.5 8-6.5"/></svg>',
    "karte": '<svg class="symbol" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21s-6.5-6.2-6.5-11a6.5 6.5 0 0 1 13 0c0 4.8-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></svg>',
    "schliessen": '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 5l14 14M19 5 5 19"/></svg>',
    "zurueck": '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7"/></svg>',
    "vor": '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7"/></svg>',
}


# ---------------------------------------------------------------------------
# Der Platz: Tennisplatz in echten Maßen (Meter), als Linienzeichnung.
# Länge 23,77 m, Breite Doppel 10,97 m, Einzel 8,23 m, Aufschlaglinie 6,40 m
# vom Netz. Auslauf: 3 m hinter der Grundlinie, 2 m neben der Seitenlinie.
# Jede Linie ist ein eigener Pfad mit pathLength="1", damit GSAP sie ziehen kann.
# ---------------------------------------------------------------------------
L, B, E = 23.77, 10.97, 1.37
AUF = 5.485            # Grundlinie bis Aufschlaglinie (11,885 - 6,40)
NETZ = L / 2
MITTE = B / 2
LINIEN = [
    ("grund", (0, 0), (0, B)), ("grund", (L, 0), (L, B)),
    ("seite", (0, 0), (L, 0)), ("seite", (0, B), (L, B)),
    ("seite", (0, E), (L, E)), ("seite", (0, B - E), (L, B - E)),
    ("auf", (AUF, E), (AUF, B - E)), ("auf", (L - AUF, E), (L - AUF, B - E)),
    ("mitte", (AUF, MITTE), (L - AUF, MITTE)),
    ("mark", (0, MITTE), (0.2, MITTE)), ("mark", (L - 0.2, MITTE), (L, MITTE)),
]


def platz_svg(hoch: bool = False) -> str:
    def pt(x, y):
        return (round(y, 3), round(x, 3)) if hoch else (round(x, 3), round(y, 3))
    box = "-2 -3 14.97 29.77" if hoch else "-3 -2 29.77 14.97"
    teile = []
    for art, a, b in LINIEN:
        (x1, y1), (x2, y2) = pt(*a), pt(*b)
        teile.append(f'<path class="platz__linie platz__linie--{art}" d="M{x1} {y1}L{x2} {y2}" pathLength="1"/>')
    (x1, y1), (x2, y2) = pt(NETZ, -0.914), pt(NETZ, B + 0.914)
    teile.append(f'<path class="platz__netz" d="M{x1} {y1}L{x2} {y2}" pathLength="1"/>')
    form = "hoch" if hoch else "quer"
    return (f'<svg class="platz__zeichnung platz__zeichnung--{form}" viewBox="{box}" '
            f'aria-hidden="true" focusable="false">{"".join(teile)}</svg>')


def ersetze_symbole(text: str) -> str:
    text = re.sub(r"\s*\{\{pfeil\}\}", "", text)
    text = text.replace("{{platz_quer}}", platz_svg()).replace("{{platz_hoch}}", platz_svg(hoch=True))
    return re.sub(r"\{\{(\w+)\}\}", lambda m: SYMBOLE.get(m.group(1), m.group(0)), text)


def kopfblock(text: str):
    """Liest den Kopfblock <!-- schluessel: wert --> am Anfang einer Quelldatei."""
    m = re.match(r"\s*<!--(.*?)-->\s*", text, re.S)
    meta = {}
    if m:
        for zeile in m.group(1).strip().splitlines():
            if ":" in zeile:
                k, v = zeile.split(":", 1)
                meta[k.strip()] = v.strip()
        text = text[m.end():]
    return meta, text


def nav_liste(aktiv: str) -> str:
    teile = []
    for datei, name in NAV:
        cur = ' aria-current="page"' if datei == aktiv else ""
        teile.append(f'<li><a href="{datei}"{cur}>{name}</a></li>')
    return "".join(teile)


def logo(gross: int = 44) -> str:
    return (
        f'<a class="logo" href="index.html" aria-label="T.C. Philathlos München, zur Startseite">'
        f'<img src="assets/img/wappen.svg" width="{gross}" height="{gross}" alt="">'
        f'<span class="logo__text"><span class="logo__name">T.C. Philathlos</span>'
        f'<span class="logo__zusatz">München, seit 1995</span></span></a>'
    )


def kopf(aktiv: str) -> str:
    return f"""<a class="sprung" href="#inhalt">Zum Inhalt springen</a>
<header class="kopf">
  <div class="kopf__innen">
    {logo()}
    <nav class="hauptnav" aria-label="Hauptnavigation"><ul>{nav_liste(aktiv)}</ul></nav>
    <div class="kopf__aktion">
      <a class="knopf knopf--gelb knopf--klein kopf__schnuppern" href="mitgliedschaft.html#schnuppern"><span>Schnupperstunde</span></a>
      <a class="kopf__anruf" href="tel:+491729138965" aria-label="Christos anrufen, 0172 913 89 65">{SYMBOLE["telefon"].replace('class="symbol" ', '')}<span>Anrufen</span></a>
      <button class="menue-knopf" type="button" aria-expanded="false" aria-controls="menue"><span class="menue-knopf__striche" aria-hidden="true"><i></i><i></i></span><span class="menue-knopf__text">Menü</span></button>
    </div>
  </div>
</header>
<dialog class="menue" id="menue" aria-label="Menü">
  <div class="menue__innen">
    <div class="menue__kopf">
      {logo(40)}
      <button class="schliessen" type="button">Schließen {SYMBOLE["schliessen"]}</button>
    </div>
    <nav class="menue__nav" aria-label="Hauptnavigation, Menü"><ul><li><a href="index.html"{' aria-current="page"' if aktiv == "index.html" else ""}>Start</a></li>{nav_liste(aktiv)}</ul></nav>
    <div class="menue__kontakt">
      <a class="knopf knopf--gelb" href="mitgliedschaft.html#schnuppern"><span>Schnupperstunde vereinbaren</span></a>
      <p><a href="tel:+491729138965">0172 913 89 65</a> <span>Christos, Tennisschule</span></p>
      <p><a href="tel:+498932210100">089 32 21 01 00</a> <span>Club</span></p>
      <p><a href="mailto:tc@philathlos.de">tc@philathlos.de</a></p>
      <p class="menue__adresse">Föhringer Ring 5, 80805 München<br>U6, Haltestelle Studentenstadt</p>
    </div>
  </div>
</dialog>"""


def fuss() -> str:
    seiten = "".join(f'<li><a href="{d}">{n}</a></li>' for d, n in NAV)
    return f"""<footer class="fuss">
  <div class="rahmen">
    <div class="fuss__oben">
      <img class="fuss__wappen" src="assets/img/wappen.svg" width="96" height="96" alt="Wappen des T.C. Philathlos München" loading="lazy">
      <p class="fuss__adresse">Föhringer Ring 5<br>80805 München</p>
      <p class="fuss__weg">Tennisverein in der Beach-Arena.<br>U6, Haltestelle Studentenstadt. Parkplätze an der Anlage.<br><a class="weiter" href="{OSM}" rel="noopener">Route auf OpenStreetMap</a></p>
    </div>
    <div class="fuss__raster">
      <div>
        <h2>Telefon und E-Mail</h2>
        <ul>
          <li><a href="tel:+491729138965">0172 913 89 65</a> <span>Christos, mobil</span></li>
          <li><a href="tel:+498932210100">089 32 21 01 00</a> <span>Club</span></li>
          <li>089 32 21 01 01 <span>Fax</span></li>
          <li><a href="mailto:tc@philathlos.de">tc@philathlos.de</a></li>
        </ul>
      </div>
      <div>
        <h2>Post</h2>
        <address>T.C. Philathlos München e.V.<br>c/o Christos Theodosiadis<br>Marianne-Brandt-Str. 20<br>80807 München</address>
      </div>
      <nav id="seiten" aria-label="Alle Seiten">
        <h2>Seiten</h2>
        <ul class="fuss__seiten"><li><a href="index.html">Start</a></li>{seiten}</ul>
      </nav>
    </div>
    <div class="fuss__unten">
      <p>© {JAHR} T.C. Philathlos München e.V.</p>
      <ul class="fuss__verbaende" aria-label="Vereinsnummern"><li>BLSV 13425</li><li>BTV 01115</li><li>DTB 2611</li></ul>
      <ul class="fuss__recht"><li><a href="impressum.html">Impressum</a></li><li><a href="datenschutz.html">Datenschutz</a></li></ul>
    </div>
  </div>
</footer>"""


def strukturdaten() -> str:
    daten = {
        "@context": "https://schema.org",
        "@type": "SportsClub",
        "name": "T.C. Philathlos München e.V.",
        "alternateName": "Tennisverein in der Beach-Arena",
        "url": DOMAIN + "/",
        "logo": DOMAIN + "/assets/img/wappen.svg",
        "image": DOMAIN + "/assets/img/plaetze-panorama.jpg",
        "foundingDate": "1995",
        "sport": ["Tennis", "Beachtennis", "Beachvolleyball"],
        "email": "tc@philathlos.de",
        "telephone": "+49 89 32210100",
        "faxNumber": "+49 89 32210101",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Föhringer Ring 5",
            "postalCode": "80805",
            "addressLocality": "München",
            "addressCountry": "DE",
        },
    }
    return '<script type="application/ld+json">' + json.dumps(daten, ensure_ascii=False) + "</script>"


def version(pfad: str) -> str:
    """Kurzer Inhalts-Hash, damit Browser nach Änderungen die neue Datei laden."""
    return hashlib.md5((WURZEL / pfad).read_bytes()).hexdigest()[:8]


def seite(datei: str, meta: dict, inhalt: str) -> str:
    titel = meta.get("titel", "")
    voll = meta.get("titel_voll") or (f"{titel} – T.C. Philathlos München e.V." if titel else "T.C. Philathlos München e.V.")
    beschreibung = meta.get("beschreibung", "")
    pfad = "" if datei == "index.html" else datei
    kanonisch = f"{DOMAIN}/{pfad}"
    og_bild = meta.get("bild", "plaetze-panorama.jpg")
    robots = '<meta name="robots" content="noindex">\n' if meta.get("index") == "nein" else ""
    kanon = "" if meta.get("index") == "nein" else f'<link rel="canonical" href="{kanonisch}">\n'
    ld = strukturdaten() if datei == "index.html" else ""
    aktiv = meta.get("nav", datei)
    return f"""<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{voll}</title>
<meta name="description" content="{beschreibung}">
{robots}{kanon}<meta name="theme-color" content="#1c3a2b">
<meta property="og:type" content="website">
<meta property="og:locale" content="de_DE">
<meta property="og:site_name" content="T.C. Philathlos München e.V.">
<meta property="og:title" content="{voll}">
<meta property="og:description" content="{beschreibung}">
<meta property="og:url" content="{kanonisch}">
<meta property="og:image" content="{DOMAIN}/assets/img/{og_bild}">
<link rel="icon" href="favicon.svg" type="image/svg+xml">
<link rel="icon" href="favicon-32.png" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="preload" href="assets/fonts/archivo.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="assets/fonts/newsreader.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/css/site.css?v={version("assets/css/site.css")}">
<script>(function(h){{if(matchMedia("(prefers-reduced-motion: no-preference)").matches){{h.classList.add("bewegt");setTimeout(function(){{if(!window.gsap)h.classList.remove("bewegt")}},2500)}}}})(document.documentElement)</script>
<script src="assets/js/lib/gsap.min.js?v={version("assets/js/lib/gsap.min.js")}" defer></script>
<script src="assets/js/lib/ScrollTrigger.min.js?v={version("assets/js/lib/ScrollTrigger.min.js")}" defer></script>
<script src="assets/js/site.js?v={version("assets/js/site.js")}" defer></script>
{ld}</head>
<body class="seite-{datei.split(".")[0]}">
{kopf(aktiv)}
<main id="inhalt">
{inhalt.strip()}
</main>
{fuss()}
</body>
</html>
"""


# Adressen der bisherigen Website, die auf neue Seiten weiterleiten.
# (Galerie.html fehlt bewusst: Auf macOS kollidiert sie mit galerie.html. Sie wird über .htaccess umgeleitet.)
WEITERLEITUNGEN = {
    "ueber_uns.html": "verein.html",
    "partner.htm": "verein.html#partner",
    "impressum.htm": "impressum.html",
    "Integration.html": "verein.html#projekte",
}


def weiterleitungen():
    for alt, neu in WEITERLEITUNGEN.items():
        html = (
            '<!doctype html>\n<html lang="de">\n<head>\n<meta charset="utf-8">\n'
            f'<title>Weiterleitung – T.C. Philathlos München e.V.</title>\n'
            '<meta name="robots" content="noindex">\n'
            f'<link rel="canonical" href="{DOMAIN}/{neu.split("#")[0] if neu.split("#")[0] != "index.html" else ""}">\n'
            f'<meta http-equiv="refresh" content="0; url={neu}">\n'
            '</head>\n<body>\n'
            f'<p>Diese Seite ist umgezogen: <a href="{neu}">weiter zur neuen Seite</a>.</p>\n'
            '</body>\n</html>\n'
        )
        (WURZEL / alt).write_text(html, encoding="utf-8")


def sitemap(dateien):
    zeilen = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">']
    for d in dateien:
        pfad = "" if d == "index.html" else d
        prio = "1.0" if d == "index.html" else ("0.3" if d in ("impressum.html", "datenschutz.html") else "0.8")
        zeilen.append(f"  <url><loc>{DOMAIN}/{pfad}</loc><priority>{prio}</priority></url>")
    zeilen.append("</urlset>")
    (WURZEL / "sitemap.xml").write_text("\n".join(zeilen) + "\n", encoding="utf-8")


def main():
    erzeugt = []
    for quelle in sorted(QUELLEN.glob("*.html")):
        meta, inhalt = kopfblock(quelle.read_text(encoding="utf-8"))
        inhalt = ersetze_symbole(inhalt).replace("{{osm}}", OSM)
        html = seite(quelle.name, meta, inhalt)
        if quelle.name == "404.html":
            # Die Fehlerseite wird unter beliebigen Adressen ausgeliefert: absolute Pfade verwenden.
            html = re.sub(r'(href|src)="(?!https?:|mailto:|tel:|#|/)', r'\1="/', html)
        (WURZEL / quelle.name).write_text(html, encoding="utf-8")
        erzeugt.append(quelle.name)
    reihenfolge = ["index.html"] + [d for d, _ in NAV] + ["impressum.html", "datenschutz.html"]
    sitemap([d for d in reihenfolge if d in erzeugt])
    weiterleitungen()
    print("Erzeugt:", ", ".join(erzeugt))


if __name__ == "__main__":
    main()
