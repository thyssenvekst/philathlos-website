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
    ("tennisschule.html", "Tennisschule"),
    ("clubleben.html", "Clubleben"),
    ("mitgliedschaft.html", "Mitgliedschaft"),
    ("galerie.html", "Galerie"),
    ("kontakt.html", "Kontakt"),
]

OSM = "https://www.openstreetmap.org/search?query=F%C3%B6hringer%20Ring%205%2C%2080805%20M%C3%BCnchen"

SYMBOLE = {
    "pfeil": '<svg class="pfeil" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M2 8h11.5M9 3.5 13.5 8 9 12.5"/></svg>',
    "telefon": '<svg class="symbol" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.6 3.5h2.6l1.5 4-2 1.4a11 11 0 0 0 6.4 6.4l1.4-2 4 1.5v2.6a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4.6 5.7a2 2 0 0 1 2-2.2z"/></svg>',
    "post": '<svg class="symbol" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3.5" y="5.5" width="17" height="13" rx="1"/><path d="m4 6.5 8 6.5 8-6.5"/></svg>',
    "karte": '<svg class="symbol" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21s-6.5-6.2-6.5-11a6.5 6.5 0 0 1 13 0c0 4.8-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></svg>',
    "schliessen": '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 5l14 14M19 5 5 19"/></svg>',
    "zurueck": '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7"/></svg>',
    "vor": '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7"/></svg>',
}
# Draufsicht eines Sandplatzes (Steckbrief, „5 Sandplätze“)
PLATZ_SYMBOL = (
    '<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><symbol id="platz" viewBox="0 0 22 34">'
    '<rect x="0" y="0" width="22" height="34" rx="1" fill="#c8643b"/>'
    '<g fill="none" stroke="#fff" stroke-width=".9"><rect x="2.5" y="2.5" width="17" height="29"/>'
    '<path d="M4.6 2.5v29M17.4 2.5v29M4.6 10h12.8M4.6 24h12.8M11 10v14"/></g>'
    '<path d="M1.5 17h19" stroke="#13213f" stroke-width="1.2"/></symbol></svg>'
)

def ersetze_symbole(text: str) -> str:
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


def nav_liste(aktiv: str, mobil: bool = False) -> str:
    teile = []
    for i, (datei, name) in enumerate(NAV, 1):
        cur = ' aria-current="page"' if datei == aktiv else ""
        zusatz = f" <span>0{i}</span>" if mobil else ""
        teile.append(f'<li><a href="{datei}"{cur}>{name}{zusatz}</a></li>')
    return "".join(teile)


def logo(klein: bool = False) -> str:
    g = 46 if klein else 58
    return (
        f'<a class="logo" href="index.html" aria-label="T.C. Philathlos München, zur Startseite">'
        f'<img src="assets/img/wappen.svg" width="{g}" height="{g}" alt="">'
        f'<span class="logo__text"><span class="logo__name">T.C. Philathlos</span>'
        f'<span class="logo__zusatz">München e.V. · seit 1995</span></span></a>'
    )


def kopf(aktiv: str) -> str:
    return f"""<a class="sprung" href="#inhalt">Zum Inhalt springen</a>
<div class="leiste">
  <div class="rahmen">
    <p>Tennisverein in der Beach-Arena · Föhringer Ring 5 · U6 Studentenstadt</p>
    <ul>
      <li><span>Club</span><a href="tel:+498932210100">089 32 21 01 00</a></li>
      <li><span>Christos</span><a href="tel:+491729138965">0172 913 89 65</a></li>
      <li><a href="mailto:tc@philathlos.de">tc@philathlos.de</a></li>
    </ul>
  </div>
</div>
<header class="kopf">
  <div class="rahmen">
    {logo()}
    <nav class="hauptnav" aria-label="Hauptnavigation"><ul>{nav_liste(aktiv)}</ul></nav>
    <div class="kopf__aktion">
      <a class="knopf knopf--voll" href="mitgliedschaft.html#schnuppern">Schnuppern</a>
      <a class="anruf-knopf" href="tel:+491729138965" aria-label="Christos anrufen, 0172 913 89 65">{SYMBOLE["telefon"].replace('class="symbol" ', '')}</a>
      <button class="menue-knopf" type="button" aria-expanded="false" aria-controls="menue" aria-label="Menü öffnen"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3.5 8h17M3.5 16h17"/></svg><span>Menü</span></button>
    </div>
  </div>
</header>
<dialog class="menue" id="menue" aria-label="Menü">
  <div class="rahmen">
    <div class="menue__kopf">
      {logo(klein=True)}
      <button class="schliessen" type="button">Schließen {SYMBOLE["schliessen"]}</button>
    </div>
    <nav aria-label="Hauptnavigation"><ul>{nav_liste(aktiv, mobil=True)}</ul></nav>
    <div class="menue__kontakt">
      <a class="knopf knopf--voll" href="tel:+491729138965">Christos anrufen <span>0172 913 89 65</span></a>
      <a class="knopf knopf--linie" href="tel:+498932210100">Club anrufen <span>089 32 21 01 00</span></a>
      <a class="knopf knopf--linie" href="mailto:tc@philathlos.de">E-Mail schreiben <span>tc@philathlos.de</span></a>
      <p class="menue__adresse">Föhringer Ring 5, 80805 München · U6 Studentenstadt</p>
    </div>
  </div>
</dialog>"""


def fuss() -> str:
    seiten = "".join(f'<li><a href="{d}">{n}</a></li>' for d, n in NAV)
    return f"""<footer class="fuss">
  <div class="rahmen">
    <div class="fuss__raster">
      <div class="fuss__marke">
        <img src="assets/img/wappen.svg" width="68" height="68" alt="" loading="lazy">
        <div><b>T.C. Philathlos München e.V.</b><p>Tennisverein in der Beach-Arena, gegründet 1995.</p></div>
      </div>
      <div>
        <h2>Anlage</h2>
        <address>Föhringer Ring 5<br>80805 München<br>U6 Studentenstadt</address>
        <p style="margin-top:.75rem"><a href="{OSM}" rel="noopener">Route planen</a></p>
      </div>
      <div>
        <h2>Kontakt</h2>
        <ul>
          <li>Club <a href="tel:+498932210100">089 32 21 01 00</a></li>
          <li>Mobil <a href="tel:+491729138965">0172 913 89 65</a></li>
          <li>Fax 089 32 21 01 01</li>
          <li><a href="mailto:tc@philathlos.de">tc@philathlos.de</a></li>
        </ul>
      </div>
      <nav id="seiten" aria-label="Alle Seiten">
        <h2>Seiten</h2>
        <ul><li><a href="index.html">Startseite</a></li>{seiten}</ul>
      </nav>
    </div>
    <div class="fuss__unten">
      <p>© {JAHR} T.C. Philathlos München e.V. · BLSV 13425 · BTV 01115 · DTB 2611</p>
      <ul><li><a href="impressum.html">Impressum</a></li><li><a href="datenschutz.html">Datenschutz</a></li></ul>
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
    extra = PLATZ_SYMBOL if "#platz" in inhalt else ""
    return f"""<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{voll}</title>
<meta name="description" content="{beschreibung}">
{robots}{kanon}<meta name="theme-color" content="#f8f5ee">
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
<link rel="preload" href="assets/fonts/newsreader.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="assets/fonts/schibsted-grotesk.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/css/site.css?v={version("assets/css/site.css")}">
<script>if("IntersectionObserver"in window&&!matchMedia("(prefers-reduced-motion: reduce)").matches)document.documentElement.classList.add("js")</script>
<script src="assets/js/site.js?v={version("assets/js/site.js")}" defer></script>
{ld}</head>
<body>
{extra}{kopf(aktiv)}
<main id="inhalt">
{inhalt.strip()}
</main>
{fuss()}
</body>
</html>
"""


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
    print("Erzeugt:", ", ".join(erzeugt))


if __name__ == "__main__":
    main()
