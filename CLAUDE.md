# T.C. Philathlos München – Kontext für Claude Code

Antworte auf Deutsch, kurz und direkt.

## Worum es geht
Website des T.C. Philathlos München e.V. (Tennisverein in der Beach-Arena, Föhringer Ring 5, 80805 München, gegründet 1995).
Relaunch 2026, Vorgängerversionen liegen in den Nachbarordnern `philathlos 1–5` und `philathlos-website 1–3`, die Live-Seite ist http://www.philathlos.de.

## Arbeitsweise
- Inhalte nur in `_quellen/seiten/*.html` ändern, dann `python3 _quellen/build.py`. Die `.html` im Wurzelordner werden überschrieben.
- Kopf, Navigation, Fuß, Domain, Jahr: zentral in `_quellen/build.py`.
- Vor dem Abschluss lokal prüfen (`python3 -m http.server`), schmal (390 px) und breit; kein horizontales Scrollen.
- Mit Git sichern (`git add -A && git commit`).

## Verbindliche Regeln
- **Nichts erfinden:** keine Zahlen, Termine, Kundenstimmen, Leistungen. Unsichere Angaben als offenen Punkt in `README.md` führen.
- Rechtstexte: Platzhalter mit `<span class="offen">…</span>` markieren, nichts juristisch erfinden.
- Keine Cookies, kein Tracking, keine externen Skripte, Schriften oder Karten-Einbettungen. Datenschutzseite muss dazu passen.
- Design: Farben und Schriften nur aus `assets/css/site.css` (Tokens in `:root`). Keine Verläufe, keine Schatten-Karten, keine Pill-Buttons, keine Emojis, keine Icon-Raster.
- Kursiv-Akzente nur im Seitentitel (H1) und sehr sparsam.
- Animationen nur einmal und dezent, `prefers-reduced-motion` respektieren. Wichtige Inhalte nie per Animation unsichtbar starten lassen.
- Bilder: Breite und Höhe im HTML angeben, unterhalb des ersten Bildschirms `loading="lazy"`.
