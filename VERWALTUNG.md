# Verwaltung der Website – schwarzes Brett, Termine, Startseite

Stand: November 2026. Diese Anleitung richtet sich an die Person, die die Website einrichtet (Abschnitte 1 bis 4), und an den Vorstand (Abschnitt 5).

---

## 1. Entscheidung: warum PHP und SQLite auf dem eigenen Webspace

| Möglichkeit | Bewertung |
|---|---|
| **PHP + SQLite auf dem Webspace des Vereins (gewählt)** | Läuft auf fast jedem deutschen Webspace-Paket (IONOS, Strato, all-inkl, Hetzner, netcup …). Keine zusätzlichen Kosten, kein externer Dienst, keine Datenbank-Einrichtung, Daten bleiben beim Verein (DSGVO). Änderungen erscheinen sofort. Eine Datei als Datenbank, leicht zu sichern. |
| Fertiges CMS (WordPress, Kirby, Craft) | Würde die eigens gestaltete Website ersetzen oder aufwendig nachbauen; WordPress braucht laufende Updates und ist Ziel vieler Angriffe; Kirby/Craft kosten Lizenzgebühren. |
| Git-basiertes CMS (Decap, Tina) | Braucht GitHub-Konten, Build-Prozess und meist Netlify. Für technisch unerfahrene Vorstandsmitglieder zu umständlich, Änderungen erscheinen erst nach einem Build. |
| Externer Dienst (Firebase, Supabase, Headless-SaaS) | Laufende Kosten oder Datenabfluss an Dritte, Auftragsverarbeitung und Drittlandtransfer zu prüfen. |

**Wichtig:** Der aktuelle Hoster von philathlos.de ist nicht bekannt. Vor dem Umzug bitte die Voraussetzungen in Abschnitt 2 prüfen.

---

## 2. Voraussetzungen beim Hoster

- Apache-Webspace mit `.htaccess`-Unterstützung (bei nginx siehe Abschnitt 6)
- **PHP 8.1 oder neuer** mit den Erweiterungen `pdo_sqlite`, `gd` (mit JPEG-Unterstützung), `fileinfo`, `session`; für den Download der Gesamtsicherung zusätzlich `zip`
- **SSL-Zertifikat (HTTPS)** – zwingend, die Verwaltung lässt sich sonst nicht aufrufen
- Schreibrechte für PHP auf den Datenordner und den Ordner `medien/`
- Upload-Grenze mindestens 10 MB (`upload_max_filesize`, `post_max_size`)

Prüfen lässt sich das im Kundenmenü des Hosters (PHP-Version, PHP-Erweiterungen) oder mit einer Testdatei `info.php` mit dem Inhalt `<?php phpinfo();` – danach sofort wieder löschen.

---

## 3. Einrichtung (einmalig, ca. 20 Minuten)

1. **Website hochladen** wie in `README.md` beschrieben (alles außer `_quellen/`, `.git/`, `*.md`). Die Ordner `verwaltung/`, `api/` und `medien/` gehören dazu.
2. **Datenordner anlegen:** am besten **außerhalb** des öffentlichen Webordners, z. B. `/home/<kunde>/philathlos-daten`. Wenn der Hoster das nicht erlaubt, legt die Verwaltung selbst den Ordner `daten/` im Webordner an und sperrt ihn per `.htaccess`.
3. **Konfiguration:** `verwaltung/konfiguration.beispiel.php` als `verwaltung/konfiguration.php` kopieren und eintragen:
   - `daten_pfad` – Pfad aus Schritt 2
   - `einrichtungs_schluessel` – ein langer Zufallswert (z. B. 30 Zeichen aus einem Passwortmanager)
   - `https_erzwingen` bleibt `true`
4. **HTTPS aktivieren** und in der `.htaccess` die drei auskommentierten Zeilen für HTTPS einschalten.
5. `https://www.philathlos.de/verwaltung/` aufrufen, Einrichtungsschlüssel eingeben und das **erste Konto (Administration)** anlegen.
6. In `konfiguration.php` den `einrichtungs_schluessel` wieder auf `''` setzen.
7. Unter „Benutzer“ die weiteren Vorstandsmitglieder anlegen. Das vorläufige Passwort wird einmal angezeigt; bitte persönlich oder telefonisch weitergeben. Bei der ersten Anmeldung muss jede Person ein eigenes Passwort wählen.
8. Prüfen: `https://www.philathlos.de/daten/` und `https://www.philathlos.de/verwaltung/lib/kern.php` müssen „Forbidden“ (403) zeigen.

---

## 4. Sicherheit und Datenschutz (was eingebaut ist)

| Thema | Umsetzung |
|---|---|
| Zugang | Anmeldung mit Benutzername und Passwort (mind. 12 Zeichen), gespeichert nur als Argon2id-/bcrypt-Hash. Keine Zugangsdaten im Frontend, kein API-Schlüssel. |
| Rollen | **Administration** (alles, inkl. Benutzer, Sicherung, Protokoll) · **Redaktion** (veröffentlichen, archivieren, löschen, Dateien, Startseiten-Hinweis) · **Mitarbeit** (nur Entwürfe, die die Redaktion freigibt). |
| Angriffe | Höchstens 5 Fehlversuche je Benutzername und je Adresse in 15 Minuten; CSRF-Schutz für jedes Formular; Sitzungs-Cookie `HttpOnly`, `Secure`, `SameSite=Strict`; Abmeldung nach 60 Minuten ohne Aktivität; strenge Content-Security-Policy; Verwaltung nicht in Suchmaschinen. |
| Keine öffentlichen Schreibzugriffe | Die öffentliche Schnittstelle `api/inhalte.php` liest nur und liefert nur veröffentlichte Inhalte ohne Personendaten. |
| Datei-Uploads | Höchstens 10 MB; Typ wird am Inhalt erkannt (nicht am Namen); nur JPG/PNG/WebP und PDF; Fotos werden neu berechnet (entfernt Standortdaten und versteckten Code), Dateinamen zufällig; im Ordner `medien/` wird kein Skript ausgeführt. |
| Ausgabe | Texte werden auf der Website nur als Text eingesetzt, nie als HTML. Internetadressen werden automatisch zu Links. |
| Fehler | Besucher sehen nur eine verständliche Meldung mit Fehlercode; Details stehen in `fehler.log` im Datenordner. |
| Protokoll | Jede Änderung mit Person und Zeit, Löschung nach zwei Jahren. |
| DSGVO | Alle Daten auf dem Webspace des Vereins. Mit dem Hoster einen Vertrag zur Auftragsverarbeitung (AVV) abschließen; die Datenschutzerklärung enthält einen Abschnitt zur Verwaltung (Platzhalter für den Hoster ausfüllen). Fotos mit erkennbaren Kindern nur mit Einwilligung der Eltern. |

---

## 5. Kurzanleitung für den Vorstand

**Anmelden:** `www.philathlos.de/verwaltung/` (funktioniert auch auf dem Handy).

**Neue Mitteilung (schwarzes Brett):** „Neue Mitteilung schreiben“ → Überschrift und Text → optional Bild oder PDF → „Speichern und veröffentlichen“. Sie erscheint sofort unter *Aktuelles*.
- **Wichtig** ankreuzen: Die Mitteilung steht oben und erscheint auch auf der Startseite unter „Am Aushang“.
- **Sichtbar ab / bis:** Die Mitteilung erscheint erst ab dem Datum und wandert nach dem Enddatum automatisch ins Archiv.
- **Als Entwurf speichern:** noch nicht sichtbar, kann später fertig geschrieben werden.
- **Archivieren:** nicht mehr am Brett, aber im Archiv auffindbar. Löschen ist endgültig.

**Neuer Termin:** „Neuen Termin eintragen“ → Titel, Datum, optional Uhrzeit, Ort, Beschreibung, Bild oder PDF (z. B. Ausschreibung). Vergangene Termine wandern am Tag danach automatisch ins Archiv.

**Hinweis auf der Startseite:** Menüpunkt „Startseite“ → ein Satz wie „Die Plätze sind ab Samstag wieder bespielbar“, optional mit Link. Ausschalten mit dem Häkchen.

**Bilder und PDFs:** Dateien können direkt in einer Mitteilung oder unter „Bilder und PDFs“ hochgeladen und später in mehreren Beiträgen verwendet werden. Bitte bei Fotos immer kurz beschreiben, was zu sehen ist.

**Passwort vergessen:** Die Administration vergibt unter „Benutzer“ ein neues vorläufiges Passwort.

---

## 6. Sicherung und Wiederherstellung

- **Automatisch:** Beim ersten Aufruf der Verwaltung an jedem Tag wird die Datenbank nach `<Datenordner>/sicherungen/` kopiert; die letzten 14 Tage bleiben.
- **Manuell:** Unter „Sicherung“ lädt die Administration eine ZIP-Datei mit Datenbank und allen Bildern/PDFs herunter. Empfehlung: einmal im Monat, an einem anderen Ort aufbewahren.
- **Wiederherstellen:** `philathlos.sqlite` aus der Sicherung in den Datenordner kopieren (vorher die vorhandene Datei umbenennen), den Inhalt von `medien/` aus der ZIP-Datei in den Ordner `medien/` der Website kopieren.
- Zusätzlich bieten die meisten Hoster eigene tägliche Backups des Webspaces an.

**nginx statt Apache:** Die `.htaccess`-Sperren gelten dort nicht. Der Hoster muss dann `daten/`, `verwaltung/lib/`, `verwaltung/konfiguration.php`, `_quellen/` und `*.md` sperren und im Ordner `medien/` die PHP-Ausführung verbieten; den Datenordner in diesem Fall unbedingt außerhalb des Webroots anlegen.

---

## 7. Technik im Überblick

| Datei / Ordner | Zweck |
|---|---|
| `verwaltung/index.php` | Verwaltungsoberfläche (ein Einstiegspunkt, `?s=…`) |
| `verwaltung/lib/kern.php` | Konfiguration, Datenbank, Anmeldung, Rollen, CSRF, Protokoll, Sicherungen |
| `verwaltung/lib/medien.php` | Prüfung und Verarbeitung von Uploads |
| `verwaltung/lib/oberflaeche.php` | Seitenrahmen und Formularbausteine |
| `verwaltung/verwaltung.css`, `verwaltung.js` | Gestaltung (gleiche Schriften und Farben wie die Website), Sicherheitsabfragen |
| `api/inhalte.php` | Öffentliche, nur lesende JSON-Schnittstelle |
| `assets/js/site.js` | lädt die Inhalte und zeigt Hinweis, Brett, Termine und Archiv; ohne Verwaltung bleiben die festen Texte stehen |
| `medien/` | hochgeladene Dateien (öffentlich, ohne Skriptausführung) |
| `daten/` bzw. Datenordner | `philathlos.sqlite`, Sicherungen, Fehlerprotokoll (nie öffentlich) |
| `_quellen/dev-router.php` | nur für lokale Tests mit `php -S 127.0.0.1:8770 _quellen/dev-router.php` |
