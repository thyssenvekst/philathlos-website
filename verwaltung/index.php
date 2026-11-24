<?php
/*
 * T.C. Philathlos München – Verwaltungsbereich (schwarzes Brett, Termine,
 * Bilder und PDFs, Hinweis auf der Startseite, Benutzer, Sicherung).
 * Ein Einstiegspunkt: index.php?s=<bereich>
 */
declare(strict_types=1);

require __DIR__ . '/lib/kern.php';
require __DIR__ . '/lib/medien.php';
require __DIR__ . '/lib/oberflaeche.php';

fehlerbehandlung_einrichten();
sicherheits_kopfzeilen();
sitzung_starten();

$s = is_string($_GET['s'] ?? null) ? $_GET['s'] : 'start';
$post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
if ($post) csrf_pruefen();

$anzahl_benutzer = (int) db()->query('SELECT COUNT(*) FROM benutzer')->fetchColumn();
if ($anzahl_benutzer === 0) { bereich_einrichten($post); exit; }
if ($s === 'anmelden' || !benutzer()) { bereich_anmelden($post); exit; }
if ($s === 'abmelden' && $post) { protokoll('Abmeldung'); abmelden(); session_start(); $_SESSION['csrf'] = bin2hex(random_bytes(32)); meldung('Sie sind abgemeldet.'); umleiten('index.php?s=anmelden'); }

if (benutzer()['muss_aendern'] && $s !== 'konto') {
    meldung('Bitte legen Sie zuerst ein eigenes Passwort fest.', 'hinweis');
    umleiten('index.php?s=konto');
}

tagessicherung();

match ($s) {
    'mitteilungen' => bereich_mitteilungen($post),
    'termine' => bereich_termine($post),
    'medien' => bereich_medien($post),
    'startseite' => bereich_startseite($post),
    'benutzer' => bereich_benutzer($post),
    'konto' => bereich_konto($post),
    'sicherung' => bereich_sicherung($post),
    default => bereich_start(),
};

/* =====================================================================
   Einrichtung und Anmeldung
   ===================================================================== */
function bereich_einrichten(bool $post): void
{
    $schluessel = (string) konfig('einrichtungs_schluessel');
    $fehler = [];
    if ($post && $schluessel !== '') {
        $name = post('name'); $kennung = post('kennung'); $pw = (string) ($_POST['passwort'] ?? '');
        if (!hash_equals($schluessel, post('schluessel'))) $fehler[] = 'Der Einrichtungsschlüssel stimmt nicht.';
        if ($name === '' || $kennung === '') $fehler[] = 'Bitte Name und Benutzername angeben.';
        if (!preg_match('/^[\w.@-]{3,60}$/u', $kennung)) $fehler[] = 'Der Benutzername darf nur Buchstaben, Ziffern, Punkt, @, Binde- und Unterstrich enthalten (3 bis 60 Zeichen).';
        if ($e = passwort_pruefen_regeln($pw)) $fehler[] = $e;
        if (!$fehler) {
            db()->prepare('INSERT INTO benutzer(name, kennung, passwort, rolle, erstellt) VALUES (?,?,?,?,?)')
                ->execute([$name, $kennung, passwort_hash($pw), 'admin', jetzt()]);
            protokoll('Verwaltung eingerichtet', $kennung, ['id' => (int) db()->lastInsertId(), 'name' => $name]);
            meldung('Die Verwaltung ist eingerichtet. Bitte melden Sie sich an und entfernen Sie danach den Einrichtungsschlüssel aus der Konfiguration.');
            umleiten('index.php?s=anmelden');
        }
    }
    seite_anfang('Einrichtung');
    echo '<div class="v-schmal"><h1>Verwaltung einrichten</h1>';
    if ($schluessel === '') {
        echo '<p>Bevor das erste Konto angelegt werden kann, muss in der Datei <code>verwaltung/konfiguration.php</code> ein Einrichtungsschlüssel eingetragen werden. Die Anleitung steht in <code>VERWALTUNG.md</code>.</p></div>';
        seite_ende();
        return;
    }
    foreach ($fehler as $f) echo '<p class="v-meldung v-meldung--fehler" role="alert">' . h($f) . '</p>';
    echo '<p>Legen Sie das erste Konto mit allen Rechten an. Weitere Personen fügen Sie danach unter „Benutzer“ hinzu.</p>';
    echo '<form method="post" class="v-formular">' . csrf_feld()
        . feld_text('schluessel', 'Einrichtungsschlüssel', '', ['pflicht' => true, 'auto' => 'one-time-code', 'hilfe' => 'Steht in verwaltung/konfiguration.php. Am besten hineinkopieren.'])
        . feld_text('name', 'Ihr Name', post('name'), ['pflicht' => true, 'max' => 80, 'auto' => 'name'])
        . feld_text('kennung', 'Benutzername', post('kennung'), ['pflicht' => true, 'max' => 60, 'auto' => 'username'])
        . feld_text('passwort', 'Passwort', '', ['pflicht' => true, 'typ' => 'password', 'auto' => 'new-password', 'hilfe' => 'Mindestens 12 Zeichen. Ein Satz aus mehreren Wörtern ist gut zu merken und sicher.'])
        . '<div class="v-knoepfe"><button class="v-knopf" type="submit">Konto anlegen</button></div></form></div>';
    seite_ende();
}

function bereich_anmelden(bool $post): void
{
    if (benutzer()) umleiten('index.php');
    $fehler = null;
    if ($post) {
        $fehler = anmelden(post('kennung'), (string) ($_POST['passwort'] ?? ''));
        if ($fehler === null) umleiten('index.php');
    }
    seite_anfang('Anmelden');
    echo '<div class="v-schmal"><h1>Anmelden</h1><p>Für Vorstandsmitglieder und alle, die Inhalte der Website pflegen.</p>';
    if ($fehler) echo '<p class="v-meldung v-meldung--fehler" role="alert">' . h($fehler) . '</p>';
    echo '<form method="post" action="index.php?s=anmelden" class="v-formular">' . csrf_feld()
        . feld_text('kennung', 'Benutzername', post('kennung'), ['pflicht' => true, 'auto' => 'username'])
        . feld_text('passwort', 'Passwort', '', ['pflicht' => true, 'typ' => 'password', 'auto' => 'current-password'])
        . '<div class="v-knoepfe"><button class="v-knopf" type="submit">Anmelden</button></div></form>'
        . '<p class="v-hilfe">Passwort vergessen? Bitte bei der Administration des Vereins melden, sie kann ein neues Passwort vergeben.</p></div>';
    seite_ende();
}

/* =====================================================================
   Übersicht
   ===================================================================== */
function bereich_start(): void
{
    $db = db();
    $entwuerfe = $db->query("SELECT 'm' AS t, id, titel, geaendert FROM mitteilungen WHERE status = 'entwurf'
                             UNION ALL SELECT 't', id, titel, geaendert FROM termine WHERE status = 'entwurf' ORDER BY geaendert DESC LIMIT 10")->fetchAll();
    $s = $db->prepare("SELECT id, titel, datum, beginn FROM termine WHERE status = 'veroeffentlicht' AND datum >= ? ORDER BY datum, beginn LIMIT 5");
    $s->execute([heute()]);
    $termine = $s->fetchAll();
    $s = $db->prepare("SELECT COUNT(*) FROM mitteilungen WHERE status = 'veroeffentlicht' AND (sichtbar_ab IS NULL OR sichtbar_ab <= :h) AND (sichtbar_bis IS NULL OR sichtbar_bis >= :h)");
    $s->execute(['h' => heute()]);
    $sichtbar = (int) $s->fetchColumn();
    $hinweis = einstellung('hinweis_aktiv') === '1' ? einstellung('hinweis_text', '') : '';

    seite_anfang('Übersicht', 'start');
    echo '<h1>Guten Tag, ' . h(benutzer()['name']) . '</h1>';
    echo '<div class="v-schnell">'
        . '<a class="v-knopf" href="index.php?s=mitteilungen&amp;neu=1">Neue Mitteilung schreiben</a>'
        . '<a class="v-knopf v-knopf--zweit" href="index.php?s=termine&amp;neu=1">Neuen Termin eintragen</a></div>';
    echo '<div class="v-raster">';
    echo '<section class="v-kasten"><h2>Auf der Website sichtbar</h2><p><strong>' . $sichtbar . '</strong> ' . ($sichtbar === 1 ? 'Mitteilung' : 'Mitteilungen') . ' am schwarzen Brett.</p>'
        . '<p>Hinweis auf der Startseite: ' . ($hinweis !== '' ? '„' . h($hinweis) . '“' : 'keiner') . '</p>'
        . (darf('startseite') ? '<p><a href="index.php?s=startseite">Hinweis ändern</a></p>' : '') . '</section>';
    echo '<section class="v-kasten"><h2>Nächste Termine</h2>';
    if ($termine) {
        echo '<ul class="v-liste">';
        foreach ($termine as $t) echo '<li><a href="index.php?s=termine&amp;id=' . (int) $t['id'] . '">' . h(datum_de($t['datum'])) . ($t['beginn'] ? ', ' . h($t['beginn']) . ' Uhr' : '') . ': ' . h($t['titel']) . '</a></li>';
        echo '</ul>';
    } else echo '<p>Keine Termine eingetragen.</p>';
    echo '</section>';
    echo '<section class="v-kasten"><h2>Entwürfe</h2>';
    if ($entwuerfe) {
        echo '<ul class="v-liste">';
        foreach ($entwuerfe as $e) echo '<li><a href="index.php?s=' . ($e['t'] === 'm' ? 'mitteilungen' : 'termine') . '&amp;id=' . (int) $e['id'] . '">' . h($e['titel']) . '</a> <small>' . ($e['t'] === 'm' ? 'Mitteilung' : 'Termin') . '</small></li>';
        echo '</ul>';
    } else echo '<p>Keine offenen Entwürfe.</p>';
    echo '</section></div>';
    seite_ende();
}

/* =====================================================================
   Mitteilungen (schwarzes Brett)
   ===================================================================== */
function bereich_mitteilungen(bool $post): void
{
    recht_noetig('inhalt');
    $db = db();
    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
    $eintrag = null;
    if ($id) {
        $q = $db->prepare('SELECT * FROM mitteilungen WHERE id = ?');
        $q->execute([$id]);
        $eintrag = $q->fetch() ?: null;
        if (!$eintrag) { meldung('Diese Mitteilung gibt es nicht mehr.', 'fehler'); umleiten('index.php?s=mitteilungen'); }
    }
    $fehler = [];

    if ($post && post('aktion') === 'loeschen' && $eintrag) {
        recht_noetig('loeschen');
        $db->prepare('DELETE FROM mitteilungen WHERE id = ?')->execute([$id]);
        protokoll('Mitteilung gelöscht', $eintrag['titel']);
        meldung('Die Mitteilung „' . $eintrag['titel'] . '“ wurde gelöscht.');
        umleiten('index.php?s=mitteilungen');
    }

    if ($post && post('aktion') === 'speichern') {
        $status = post('status', 'entwurf');
        if (!in_array($status, ['entwurf', 'veroeffentlicht', 'archiviert'], true)) $status = 'entwurf';
        if ($status !== 'entwurf' && !darf('veroeffentlichen')) $status = 'entwurf';
        if ($eintrag && $eintrag['status'] !== 'entwurf' && !darf('veroeffentlichen')) {
            recht_noetig('veroeffentlichen');
        }
        $d = [
            'titel' => mb_substr(post('titel'), 0, 150),
            'text' => mb_substr(post('text'), 0, 8000),
            'datum' => post('datum', heute()),
            'wichtig' => isset($_POST['wichtig']) ? 1 : 0,
            'sichtbar_ab' => post('sichtbar_ab') ?: null,
            'sichtbar_bis' => post('sichtbar_bis') ?: null,
            'bild_id' => (int) post('bild_id') ?: null,
            'anhang_id' => (int) post('anhang_id') ?: null,
        ];
        if ($d['titel'] === '') $fehler[] = 'Bitte eine Überschrift eingeben.';
        if (!gueltiges_datum($d['datum'])) $fehler[] = 'Bitte ein gültiges Datum angeben.';
        foreach (['sichtbar_ab' => 'Sichtbar ab', 'sichtbar_bis' => 'Sichtbar bis'] as $k => $n) {
            if ($d[$k] !== null && !gueltiges_datum($d[$k])) $fehler[] = "„{$n}“ ist kein gültiges Datum.";
        }
        if ($d['sichtbar_ab'] && $d['sichtbar_bis'] && $d['sichtbar_bis'] < $d['sichtbar_ab']) $fehler[] = '„Sichtbar bis“ liegt vor „Sichtbar ab“.';
        if (!$fehler) $fehler = dateien_aus_formular($d);
        if (!$fehler) {
            if ($eintrag) {
                $db->prepare('UPDATE mitteilungen SET titel=?, text=?, datum=?, status=?, wichtig=?, sichtbar_ab=?, sichtbar_bis=?, bild_id=?, anhang_id=?, geaendert=?, geaendert_von=? WHERE id=?')
                    ->execute([$d['titel'], $d['text'], $d['datum'], $status, $d['wichtig'], $d['sichtbar_ab'], $d['sichtbar_bis'], $d['bild_id'], $d['anhang_id'], jetzt(), benutzer()['id'], $id]);
            } else {
                $db->prepare('INSERT INTO mitteilungen(titel, text, datum, status, wichtig, sichtbar_ab, sichtbar_bis, bild_id, anhang_id, erstellt, geaendert, geaendert_von) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$d['titel'], $d['text'], $d['datum'], $status, $d['wichtig'], $d['sichtbar_ab'], $d['sichtbar_bis'], $d['bild_id'], $d['anhang_id'], jetzt(), jetzt(), benutzer()['id']]);
                $id = (int) $db->lastInsertId();
            }
            protokoll('Mitteilung ' . ['entwurf' => 'als Entwurf gespeichert', 'veroeffentlicht' => 'veröffentlicht', 'archiviert' => 'archiviert'][$status], $d['titel']);
            meldung(match ($status) {
                'veroeffentlicht' => $d['sichtbar_bis'] && $d['sichtbar_bis'] < heute()
                    ? 'Gespeichert. Das Datum „Sichtbar bis“ ist schon vorbei, deshalb steht die Mitteilung im Archiv.'
                    : 'Gespeichert. Die Mitteilung ist jetzt auf der Website sichtbar' . ($d['sichtbar_ab'] && $d['sichtbar_ab'] > heute() ? ' ab ' . datum_de($d['sichtbar_ab']) : '') . '.',
                'archiviert' => 'Gespeichert und archiviert. Die Mitteilung steht jetzt im Archiv.',
                default => 'Als Entwurf gespeichert. Auf der Website ist sie noch nicht zu sehen.',
            });
            umleiten('index.php?s=mitteilungen&id=' . $id);
        }
        $eintrag = array_merge($eintrag ?? [], $d, ['status' => $eintrag['status'] ?? 'entwurf']);
    }

    if ($eintrag || isset($_GET['neu']) || $fehler) {
        $e = $eintrag ?? ['titel' => '', 'text' => '', 'datum' => heute(), 'status' => 'entwurf', 'wichtig' => 0, 'sichtbar_ab' => '', 'sichtbar_bis' => '', 'bild_id' => null, 'anhang_id' => null];
        $gesperrt = !empty($e['id']) && $e['status'] !== 'entwurf' && !darf('veroeffentlichen');
        seite_anfang(!empty($e['id']) ? 'Mitteilung bearbeiten' : 'Neue Mitteilung', 'mitteilungen');
        echo '<p class="v-zurueck"><a href="index.php?s=mitteilungen">Alle Mitteilungen</a></p>';
        echo '<h1>' . (!empty($e['id']) ? 'Mitteilung bearbeiten' : 'Neue Mitteilung') . ' ' . (!empty($e['id']) ? status_marke($e['status'], $e['sichtbar_bis'] ?: null) : '') . '</h1>';
        foreach ($fehler as $f) echo '<p class="v-meldung v-meldung--fehler" role="alert">' . h($f) . '</p>';
        if ($gesperrt) { echo '<p class="v-meldung v-meldung--hinweis">Diese Mitteilung ist veröffentlicht. Ändern kann sie nur die Redaktion.</p>'; }
        echo '<form method="post" enctype="multipart/form-data" class="v-formular" action="index.php?s=mitteilungen">' . csrf_feld()
            . '<input type="hidden" name="aktion" value="speichern"><input type="hidden" name="id" value="' . (int) ($e['id'] ?? 0) . '">'
            . '<input type="hidden" name="MAX_FILE_SIZE" value="' . ((int) konfig('max_upload_mb') * 1048576) . '">'
            . feld_text('titel', 'Überschrift', $e['titel'], ['pflicht' => true, 'max' => 150])
            . feld_text('text', 'Text', $e['text'], ['typ' => 'textarea', 'zeilen' => 10, 'max' => 8000, 'hilfe' => 'Eine Leerzeile beginnt einen neuen Absatz. Internetadressen werden automatisch zu Links.'])
            . '<div class="v-zeile">'
            . feld_text('datum', 'Datum der Mitteilung', $e['datum'], ['pflicht' => true, 'typ' => 'date'])
            . feld_text('sichtbar_ab', 'Sichtbar ab', (string) $e['sichtbar_ab'], ['typ' => 'date', 'hilfe' => 'Leer lassen für sofort.'])
            . feld_text('sichtbar_bis', 'Sichtbar bis', (string) $e['sichtbar_bis'], ['typ' => 'date', 'hilfe' => 'Danach wandert die Mitteilung automatisch ins Archiv.'])
            . '</div>'
            . '<div class="v-feld v-feld--haken"><input id="f-wichtig" type="checkbox" name="wichtig" value="1"' . ($e['wichtig'] ? ' checked' : '') . '><label for="f-wichtig">Wichtig: hervorheben und auf der Startseite zeigen</label></div>'
            . feld_datei('bild', $e['bild_id'] ? (int) $e['bild_id'] : null)
            . feld_datei('pdf', $e['anhang_id'] ? (int) $e['anhang_id'] : null);
        if (!$gesperrt) {
            echo '<div class="v-knoepfe">';
            if (darf('veroeffentlichen')) {
                echo '<button class="v-knopf" type="submit" name="status" value="veroeffentlicht">Speichern und veröffentlichen</button>';
                echo '<button class="v-knopf v-knopf--zweit" type="submit" name="status" value="entwurf">Als Entwurf speichern</button>';
                if (!empty($e['id'])) echo '<button class="v-knopf v-knopf--leise" type="submit" name="status" value="archiviert">Archivieren</button>';
            } else {
                echo '<button class="v-knopf" type="submit" name="status" value="entwurf">Als Entwurf speichern</button>';
                echo '<p class="v-hilfe">Die Redaktion prüft den Entwurf und veröffentlicht ihn.</p>';
            }
            echo '</div>';
        }
        echo '</form>';
        if (!empty($e['id']) && darf('loeschen')) {
            echo '<form method="post" action="index.php?s=mitteilungen" class="v-loeschen" data-bestaetigen="Die Mitteilung „' . h($e['titel']) . '“ endgültig löschen? Das lässt sich nicht rückgängig machen. Archivieren ist die sanftere Wahl.">'
                . csrf_feld() . '<input type="hidden" name="aktion" value="loeschen"><input type="hidden" name="id" value="' . (int) $e['id'] . '">'
                . '<button type="submit" class="v-knopf v-knopf--gefahr">Mitteilung löschen</button></form>';
        }
        seite_ende();
        return;
    }

    $tab = in_array($_GET['tab'] ?? '', ['entwurf', 'archiv'], true) ? $_GET['tab'] : 'aktuell';
    $where = match ($tab) {
        'entwurf' => "status = 'entwurf'",
        'archiv' => "status = 'archiviert' OR (status = 'veroeffentlicht' AND sichtbar_bis < :h)",
        default => "status = 'veroeffentlicht' AND (sichtbar_bis IS NULL OR sichtbar_bis >= :h)",
    };
    $q = $db->prepare("SELECT * FROM mitteilungen WHERE $where ORDER BY wichtig DESC, datum DESC LIMIT 200");
    $q->execute($tab === 'entwurf' ? [] : ['h' => heute()]);
    $liste = $q->fetchAll();
    seite_anfang('Schwarzes Brett', 'mitteilungen');
    echo '<div class="v-kopfzeile"><h1>Schwarzes Brett</h1><a class="v-knopf" href="index.php?s=mitteilungen&amp;neu=1">Neue Mitteilung</a></div>';
    echo reiter('mitteilungen', $tab, ['aktuell' => 'Veröffentlicht', 'entwurf' => 'Entwürfe', 'archiv' => 'Archiv']);
    if (!$liste) echo '<p class="v-leer">Hier ist noch nichts. ' . ($tab === 'aktuell' ? 'Mit „Neue Mitteilung“ legen Sie die erste an.' : '') . '</p>';
    echo '<ul class="v-eintraege">';
    foreach ($liste as $m) {
        $zukunft = $m['sichtbar_ab'] && $m['sichtbar_ab'] > heute() && $m['status'] === 'veroeffentlicht';
        echo '<li><a href="index.php?s=mitteilungen&amp;id=' . (int) $m['id'] . '"><strong>' . h($m['titel']) . '</strong>'
            . '<span>' . h(datum_de($m['datum'])) . ($m['wichtig'] ? ' · Wichtig' : '') . ($zukunft ? ' · sichtbar ab ' . h(datum_de($m['sichtbar_ab'])) : '') . ($m['sichtbar_bis'] ? ' · bis ' . h(datum_de($m['sichtbar_bis'])) : '') . '</span></a>'
            . status_marke($m['status'], $m['sichtbar_bis']) . '</li>';
    }
    echo '</ul>';
    seite_ende();
}

/** Neue Dateien aus dem Formular übernehmen; schreibt IDs in $d, gibt Fehler zurück */
function dateien_aus_formular(array &$d): array
{
    $fehler = [];
    foreach (['bild' => 'bild_id', 'pdf' => 'anhang_id'] as $art => $feld) {
        $f = $_FILES['neu_' . $art] ?? null;
        if ($f && is_array($f) && !is_array($f['error']) && $f['error'] !== UPLOAD_ERR_NO_FILE) {
            if (!darf('medien')) { $fehler[] = 'Sie dürfen keine Dateien hochladen.'; continue; }
            try {
                $d[$feld] = datei_annehmen('neu_' . $art, $art, post('neu_' . $art . '_titel'));
            } catch (DomainException $e) {
                $fehler[] = $e->getMessage() . ' Bitte die Datei danach noch einmal auswählen.';
            }
        } elseif ($d[$feld] !== null) {
            $m = medium((int) $d[$feld]);
            if (!$m || $m['art'] !== $art) $d[$feld] = null;
        }
    }
    return $fehler;
}

function reiter(string $s, string $aktiv, array $reiter): string
{
    $html = '<nav class="v-reiter" aria-label="Ansicht wählen"><ul>';
    foreach ($reiter as $k => $n) {
        $html .= '<li><a href="index.php?s=' . h($s) . '&amp;tab=' . h($k) . '"' . ($k === $aktiv ? ' aria-current="page"' : '') . '>' . h($n) . '</a></li>';
    }
    return $html . '</ul></nav>';
}

/* =====================================================================
   Termine
   ===================================================================== */
function bereich_termine(bool $post): void
{
    recht_noetig('inhalt');
    $db = db();
    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
    $eintrag = null;
    if ($id) {
        $q = $db->prepare('SELECT * FROM termine WHERE id = ?');
        $q->execute([$id]);
        $eintrag = $q->fetch() ?: null;
        if (!$eintrag) { meldung('Diesen Termin gibt es nicht mehr.', 'fehler'); umleiten('index.php?s=termine'); }
    }
    $fehler = [];

    if ($post && post('aktion') === 'loeschen' && $eintrag) {
        recht_noetig('loeschen');
        $db->prepare('DELETE FROM termine WHERE id = ?')->execute([$id]);
        protokoll('Termin gelöscht', $eintrag['titel']);
        meldung('Der Termin „' . $eintrag['titel'] . '“ wurde gelöscht.');
        umleiten('index.php?s=termine');
    }

    if ($post && post('aktion') === 'speichern') {
        $status = post('status') === 'veroeffentlicht' && darf('veroeffentlichen') ? 'veroeffentlicht' : 'entwurf';
        if ($eintrag && $eintrag['status'] !== 'entwurf' && !darf('veroeffentlichen')) recht_noetig('veroeffentlichen');
        $d = [
            'titel' => mb_substr(post('titel'), 0, 150),
            'beschreibung' => mb_substr(post('beschreibung'), 0, 8000),
            'datum' => post('datum'),
            'beginn' => post('beginn') ?: null,
            'ende' => post('ende') ?: null,
            'ort' => mb_substr(post('ort'), 0, 120) ?: null,
            'bild_id' => (int) post('bild_id') ?: null,
            'anhang_id' => (int) post('anhang_id') ?: null,
        ];
        if ($d['titel'] === '') $fehler[] = 'Bitte einen Titel eingeben.';
        if (!gueltiges_datum($d['datum'])) $fehler[] = 'Bitte ein gültiges Datum angeben.';
        if ($d['beginn'] !== null && !gueltige_uhrzeit($d['beginn'])) $fehler[] = 'Die Uhrzeit „Beginn“ bitte als Stunde und Minute angeben, z. B. 14:00.';
        if ($d['ende'] !== null && !gueltige_uhrzeit($d['ende'])) $fehler[] = 'Die Uhrzeit „Ende“ bitte als Stunde und Minute angeben, z. B. 18:00.';
        if ($d['beginn'] && $d['ende'] && $d['ende'] <= $d['beginn']) $fehler[] = 'Das Ende liegt vor dem Beginn.';
        if (!$fehler) $fehler = dateien_aus_formular($d);
        if (!$fehler) {
            if ($eintrag) {
                $db->prepare('UPDATE termine SET titel=?, beschreibung=?, datum=?, beginn=?, ende=?, ort=?, status=?, bild_id=?, anhang_id=?, geaendert=?, geaendert_von=? WHERE id=?')
                    ->execute([$d['titel'], $d['beschreibung'], $d['datum'], $d['beginn'], $d['ende'], $d['ort'], $status, $d['bild_id'], $d['anhang_id'], jetzt(), benutzer()['id'], $id]);
            } else {
                $db->prepare('INSERT INTO termine(titel, beschreibung, datum, beginn, ende, ort, status, bild_id, anhang_id, erstellt, geaendert, geaendert_von) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$d['titel'], $d['beschreibung'], $d['datum'], $d['beginn'], $d['ende'], $d['ort'], $status, $d['bild_id'], $d['anhang_id'], jetzt(), jetzt(), benutzer()['id']]);
                $id = (int) $db->lastInsertId();
            }
            protokoll('Termin ' . ($status === 'veroeffentlicht' ? 'veröffentlicht' : 'als Entwurf gespeichert'), $d['titel'] . ', ' . $d['datum']);
            meldung($status === 'veroeffentlicht'
                ? ($d['datum'] < heute() ? 'Gespeichert. Der Termin liegt in der Vergangenheit und steht deshalb im Archiv.' : 'Gespeichert. Der Termin ist jetzt auf der Website sichtbar.')
                : 'Als Entwurf gespeichert. Auf der Website ist er noch nicht zu sehen.');
            umleiten('index.php?s=termine&id=' . $id);
        }
        $eintrag = array_merge($eintrag ?? [], $d, ['status' => $eintrag['status'] ?? 'entwurf']);
    }

    if ($eintrag || isset($_GET['neu']) || $fehler) {
        $e = $eintrag ?? ['titel' => '', 'beschreibung' => '', 'datum' => '', 'beginn' => '', 'ende' => '', 'ort' => '', 'status' => 'entwurf', 'bild_id' => null, 'anhang_id' => null];
        $gesperrt = !empty($e['id']) && $e['status'] !== 'entwurf' && !darf('veroeffentlichen');
        seite_anfang(!empty($e['id']) ? 'Termin bearbeiten' : 'Neuer Termin', 'termine');
        echo '<p class="v-zurueck"><a href="index.php?s=termine">Alle Termine</a></p>';
        echo '<h1>' . (!empty($e['id']) ? 'Termin bearbeiten' : 'Neuer Termin') . ' ' . (!empty($e['id']) ? ($e['datum'] < heute() && $e['status'] === 'veroeffentlicht' ? '<span class="v-marke-status v-marke-status--archiv">Vergangen</span>' : status_marke($e['status'])) : '') . '</h1>';
        foreach ($fehler as $f) echo '<p class="v-meldung v-meldung--fehler" role="alert">' . h($f) . '</p>';
        if ($gesperrt) echo '<p class="v-meldung v-meldung--hinweis">Dieser Termin ist veröffentlicht. Ändern kann ihn nur die Redaktion.</p>';
        echo '<form method="post" enctype="multipart/form-data" class="v-formular" action="index.php?s=termine">' . csrf_feld()
            . '<input type="hidden" name="aktion" value="speichern"><input type="hidden" name="id" value="' . (int) ($e['id'] ?? 0) . '">'
            . '<input type="hidden" name="MAX_FILE_SIZE" value="' . ((int) konfig('max_upload_mb') * 1048576) . '">'
            . feld_text('titel', 'Titel', $e['titel'], ['pflicht' => true, 'max' => 150, 'hilfe' => 'Zum Beispiel „Clubmeisterschaft, Finaltag“.'])
            . '<div class="v-zeile">'
            . feld_text('datum', 'Datum', $e['datum'], ['pflicht' => true, 'typ' => 'date'])
            . feld_text('beginn', 'Beginn', (string) $e['beginn'], ['typ' => 'time'])
            . feld_text('ende', 'Ende', (string) $e['ende'], ['typ' => 'time'])
            . '</div>'
            . feld_text('ort', 'Ort', (string) $e['ort'], ['max' => 120, 'hilfe' => 'Leer lassen, wenn der Termin auf der Anlage am Föhringer Ring stattfindet.'])
            . feld_text('beschreibung', 'Beschreibung', $e['beschreibung'], ['typ' => 'textarea', 'zeilen' => 8, 'max' => 8000, 'hilfe' => 'Eine Leerzeile beginnt einen neuen Absatz.'])
            . feld_datei('bild', $e['bild_id'] ? (int) $e['bild_id'] : null)
            . feld_datei('pdf', $e['anhang_id'] ? (int) $e['anhang_id'] : null);
        if (!$gesperrt) {
            echo '<div class="v-knoepfe">';
            if (darf('veroeffentlichen')) {
                echo '<button class="v-knopf" type="submit" name="status" value="veroeffentlicht">Speichern und veröffentlichen</button>'
                    . '<button class="v-knopf v-knopf--zweit" type="submit" name="status" value="entwurf">Als Entwurf speichern</button>';
            } else {
                echo '<button class="v-knopf" type="submit" name="status" value="entwurf">Als Entwurf speichern</button><p class="v-hilfe">Die Redaktion prüft den Entwurf und veröffentlicht ihn.</p>';
            }
            echo '</div>';
        }
        echo '</form>';
        if (!empty($e['id']) && darf('loeschen')) {
            echo '<form method="post" action="index.php?s=termine" class="v-loeschen" data-bestaetigen="Den Termin „' . h($e['titel']) . '“ endgültig löschen? Das lässt sich nicht rückgängig machen.">'
                . csrf_feld() . '<input type="hidden" name="aktion" value="loeschen"><input type="hidden" name="id" value="' . (int) $e['id'] . '">'
                . '<button type="submit" class="v-knopf v-knopf--gefahr">Termin löschen</button></form>';
        }
        seite_ende();
        return;
    }

    $tab = in_array($_GET['tab'] ?? '', ['entwurf', 'vergangen'], true) ? $_GET['tab'] : 'kommend';
    [$where, $ord] = match ($tab) {
        'entwurf' => ["status = 'entwurf'", 'datum DESC'],
        'vergangen' => ["status = 'veroeffentlicht' AND datum < :h", 'datum DESC'],
        default => ["status = 'veroeffentlicht' AND datum >= :h", 'datum, beginn'],
    };
    $q = $db->prepare("SELECT * FROM termine WHERE $where ORDER BY $ord LIMIT 300");
    $q->execute($tab === 'entwurf' ? [] : ['h' => heute()]);
    $liste = $q->fetchAll();
    seite_anfang('Termine', 'termine');
    echo '<div class="v-kopfzeile"><h1>Termine</h1><a class="v-knopf" href="index.php?s=termine&amp;neu=1">Neuer Termin</a></div>';
    echo reiter('termine', $tab, ['kommend' => 'Kommende', 'entwurf' => 'Entwürfe', 'vergangen' => 'Vergangen (Archiv)']);
    if ($tab === 'vergangen') echo '<p class="v-hilfe">Vergangene Termine wandern am Tag danach automatisch hierher und ins Archiv der Website.</p>';
    if (!$liste) echo '<p class="v-leer">Hier ist noch nichts.</p>';
    echo '<ul class="v-eintraege">';
    foreach ($liste as $t) {
        echo '<li><a href="index.php?s=termine&amp;id=' . (int) $t['id'] . '"><strong>' . h($t['titel']) . '</strong><span>' . h(datum_de($t['datum']))
            . ($t['beginn'] ? ', ' . h($t['beginn']) . ($t['ende'] ? '–' . h($t['ende']) : '') . ' Uhr' : '') . ($t['ort'] ? ' · ' . h($t['ort']) : '') . '</span></a>'
            . ($tab === 'vergangen' ? '<span class="v-marke-status v-marke-status--archiv">Vergangen</span>' : status_marke($t['status'])) . '</li>';
    }
    echo '</ul>';
    seite_ende();
}

/* =====================================================================
   Bilder und PDFs
   ===================================================================== */
function bereich_medien(bool $post): void
{
    recht_noetig('medien');
    $fehler = [];
    if ($post && post('aktion') === 'hochladen') {
        $art = post('art') === 'pdf' ? 'pdf' : 'bild';
        try {
            datei_annehmen('datei', $art, post('titel'));
            meldung($art === 'pdf' ? 'Die PDF-Datei ist hochgeladen.' : 'Das Bild ist hochgeladen.');
            umleiten('index.php?s=medien');
        } catch (DomainException $e) {
            $fehler[] = $e->getMessage();
        }
    }
    if ($post && post('aktion') === 'titel') {
        $m = medium((int) post('id'));
        if ($m && post('titel') !== '') {
            db()->prepare('UPDATE medien SET titel = ? WHERE id = ?')->execute([mb_substr(post('titel'), 0, 200), $m['id']]);
            protokoll('Dateititel geändert', post('titel'));
            meldung('Gespeichert.');
        }
        umleiten('index.php?s=medien');
    }
    if ($post && post('aktion') === 'loeschen') {
        recht_noetig('loeschen');
        $m = medium((int) post('id'));
        if ($m) {
            $verwendet = medium_verwendung((int) $m['id']);
            if ($verwendet) {
                meldung('„' . $m['titel'] . '“ wird noch verwendet (' . implode(', ', array_map(fn($v) => $v['art'] . ' „' . $v['titel'] . '“', $verwendet)) . '). Bitte dort zuerst entfernen.', 'fehler');
            } else {
                medium_loeschen((int) $m['id']);
                meldung('Die Datei wurde gelöscht.');
            }
        }
        umleiten('index.php?s=medien');
    }

    $liste = db()->query('SELECT * FROM medien ORDER BY erstellt DESC LIMIT 300')->fetchAll();
    seite_anfang('Bilder und PDFs', 'medien');
    echo '<h1>Bilder und PDFs</h1>';
    foreach ($fehler as $f) echo '<p class="v-meldung v-meldung--fehler" role="alert">' . h($f) . '</p>';
    echo '<details class="v-aufklapp"' . ($fehler || !$liste ? ' open' : '') . '><summary>Neue Datei hochladen</summary>'
        . '<form method="post" enctype="multipart/form-data" class="v-formular" action="index.php?s=medien">' . csrf_feld()
        . '<input type="hidden" name="aktion" value="hochladen"><input type="hidden" name="MAX_FILE_SIZE" value="' . ((int) konfig('max_upload_mb') * 1048576) . '">'
        . feld_auswahl('art', 'Art der Datei', ['bild' => 'Foto (JPG, PNG oder WebP)', 'pdf' => 'PDF-Dokument'], post('art', 'bild'))
        . '<div class="v-feld"><label for="f-datei">Datei</label><span class="v-hilfe">Höchstens ' . (int) konfig('max_upload_mb') . ' MB. Fotos werden automatisch verkleinert, Standortdaten aus dem Handy werden entfernt.</span>'
        . '<input id="f-datei" type="file" name="datei" required accept="image/jpeg,image/png,image/webp,application/pdf"></div>'
        . feld_text('titel', 'Beschreibung oder Titel', post('titel'), ['pflicht' => true, 'max' => 200, 'hilfe' => 'Bei Fotos: was zu sehen ist. Bei PDFs: der Titel des Dokuments.'])
        . '<p class="v-hilfe">Bitte nur Fotos hochladen, für die der Verein die Rechte hat. Bei erkennbaren Kindern braucht es die Einwilligung der Eltern.</p>'
        . '<div class="v-knoepfe"><button class="v-knopf" type="submit">Hochladen</button></div></form></details>';
    if (!$liste) { echo '<p class="v-leer">Noch keine Dateien hochgeladen.</p>'; seite_ende(); return; }
    echo '<ul class="v-medien">';
    foreach ($liste as $m) {
        $verwendet = medium_verwendung((int) $m['id']);
        echo '<li>';
        echo $m['art'] === 'bild'
            ? '<img src="' . h('../' . medien_url($m['klein'] ?: $m['datei'])) . '" alt="" loading="lazy" width="320" height="200">'
            : '<a class="v-pdf" href="' . h('../' . medien_url($m['datei'])) . '" target="_blank" rel="noopener">PDF öffnen</a>';
        echo '<form method="post" action="index.php?s=medien" class="v-titelform">' . csrf_feld() . '<input type="hidden" name="aktion" value="titel"><input type="hidden" name="id" value="' . (int) $m['id'] . '">'
            . '<label class="sr" for="t-' . (int) $m['id'] . '">Titel</label><input id="t-' . (int) $m['id'] . '" name="titel" value="' . h($m['titel']) . '" maxlength="200"><button class="v-link" type="submit">Speichern</button></form>';
        echo '<p class="v-hilfe">' . h(datum_de($m['erstellt'])) . ($m['art'] === 'bild' ? ', ' . (int) $m['breite'] . ' × ' . (int) $m['hoehe'] . ' px' : ', ' . round(((int) $m['groesse']) / 1048576, 1) . ' MB')
            . ($verwendet ? '<br>Verwendet in: ' . h(implode(', ', array_map(fn($v) => $v['titel'], $verwendet))) : '<br>Nicht verwendet') . '</p>';
        if (darf('loeschen') && !$verwendet) {
            echo '<form method="post" action="index.php?s=medien" data-bestaetigen="Diese Datei endgültig löschen?">' . csrf_feld()
                . '<input type="hidden" name="aktion" value="loeschen"><input type="hidden" name="id" value="' . (int) $m['id'] . '"><button class="v-link v-link--gefahr" type="submit">Löschen</button></form>';
        }
        echo '</li>';
    }
    echo '</ul>';
    seite_ende();
}

/* =====================================================================
   Startseite: Hinweis
   ===================================================================== */
function bereich_startseite(bool $post): void
{
    recht_noetig('startseite');
    $fehler = [];
    if ($post) {
        $aktiv = isset($_POST['aktiv']) ? '1' : '0';
        $text = mb_substr(post('text'), 0, 220);
        $link = post('link');
        $linktext = mb_substr(post('linktext'), 0, 60);
        if ($aktiv === '1' && $text === '') $fehler[] = 'Bitte einen Hinweistext eingeben oder den Hinweis ausschalten.';
        if ($link !== '' && !preg_match('~^(https://|http://|mailto:|tel:|/|[a-z0-9-]+\.html)~i', $link)) $fehler[] = 'Der Link muss mit https:// beginnen oder eine Seite der Website sein, z. B. aktuelles.html.';
        if (!$fehler) {
            foreach (['hinweis_aktiv' => $aktiv, 'hinweis_text' => $text, 'hinweis_link' => $link, 'hinweis_linktext' => $linktext] as $k => $v) einstellung_setzen($k, $v);
            protokoll('Startseiten-Hinweis ' . ($aktiv === '1' ? 'gesetzt' : 'ausgeschaltet'), $text);
            meldung($aktiv === '1' ? 'Gespeichert. Der Hinweis steht jetzt oben auf der Startseite.' : 'Gespeichert. Auf der Startseite steht kein Hinweis.');
            umleiten('index.php?s=startseite');
        }
    }
    $w = fn($k, $s = '') => $post ? post(str_replace('hinweis_', '', $k)) : (string) einstellung($k, $s);
    seite_anfang('Startseite', 'startseite');
    echo '<h1>Hinweis auf der Startseite</h1><p>Ein kurzer Satz, der ganz oben auf der Startseite erscheint, zum Beispiel „Die Plätze sind ab 1. April wieder bespielbar“. Mitteilungen, die Sie als „wichtig“ markieren, erscheinen zusätzlich im Bereich „Am Aushang“.</p>';
    foreach ($fehler as $f) echo '<p class="v-meldung v-meldung--fehler" role="alert">' . h($f) . '</p>';
    $aktiv = $post ? isset($_POST['aktiv']) : einstellung('hinweis_aktiv') === '1';
    echo '<form method="post" class="v-formular" action="index.php?s=startseite">' . csrf_feld()
        . '<div class="v-feld v-feld--haken"><input id="f-aktiv" type="checkbox" name="aktiv" value="1"' . ($aktiv ? ' checked' : '') . '><label for="f-aktiv">Hinweis auf der Startseite anzeigen</label></div>'
        . feld_text('text', 'Hinweistext', $w('hinweis_text'), ['max' => 220, 'hilfe' => 'Höchstens 220 Zeichen, ein oder zwei Sätze.'])
        . '<div class="v-zeile">'
        . feld_text('link', 'Link', $w('hinweis_link'), ['max' => 300, 'hilfe' => 'Zum Beispiel aktuelles.html oder https://…'])
        . feld_text('linktext', 'Linktext', $w('hinweis_linktext'), ['max' => 60, 'hilfe' => 'Zum Beispiel „Mehr erfahren“.'])
        . '</div><div class="v-knoepfe"><button class="v-knopf" type="submit">Speichern</button></div></form>';
    seite_ende();
}

/* =====================================================================
   Benutzer (nur Administration)
   ===================================================================== */
function bereich_benutzer(bool $post): void
{
    recht_noetig('benutzer');
    $db = db();
    $fehler = [];
    $neues_passwort = null;
    if ($post) {
        $aktion = post('aktion');
        $id = (int) post('id');
        $ich = (int) benutzer()['id'];
        if ($aktion === 'anlegen') {
            $name = mb_substr(post('name'), 0, 80); $kennung = post('kennung'); $rolle = post('rolle');
            if ($name === '' || !preg_match('/^[\w.@-]{3,60}$/u', $kennung)) $fehler[] = 'Bitte Name und einen Benutzernamen (3 bis 60 Zeichen, ohne Leerzeichen) angeben.';
            if (!isset(ROLLEN[$rolle])) $fehler[] = 'Bitte eine Rolle wählen.';
            $q = $db->prepare('SELECT COUNT(*) FROM benutzer WHERE kennung = ?'); $q->execute([$kennung]);
            if ($q->fetchColumn()) $fehler[] = 'Diesen Benutzernamen gibt es schon.';
            if (!$fehler) {
                $neues_passwort = zufallspasswort();
                $db->prepare('INSERT INTO benutzer(name, kennung, passwort, rolle, muss_aendern, erstellt) VALUES (?,?,?,?,1,?)')
                    ->execute([$name, $kennung, passwort_hash($neues_passwort), $rolle, jetzt()]);
                protokoll('Benutzer angelegt', "$name ($kennung), " . ROLLEN[$rolle]);
                $neues_passwort = [$name, $kennung, $neues_passwort];
            }
        } elseif ($aktion === 'aendern' && $id) {
            $rolle = post('rolle'); $aktiv = isset($_POST['aktiv']) ? 1 : 0;
            if ($id === $ich && ($rolle !== 'admin' || !$aktiv)) $fehler[] = 'Sie können sich nicht selbst die Administration entziehen oder sich sperren.';
            elseif (isset(ROLLEN[$rolle])) {
                $db->prepare('UPDATE benutzer SET rolle = ?, aktiv = ? WHERE id = ?')->execute([$rolle, $aktiv, $id]);
                protokoll('Benutzer geändert', "#$id: " . ROLLEN[$rolle] . ($aktiv ? '' : ', gesperrt'));
                meldung('Gespeichert.');
                umleiten('index.php?s=benutzer');
            }
        } elseif ($aktion === 'passwort' && $id && $id !== $ich) {
            $q = $db->prepare('SELECT name, kennung FROM benutzer WHERE id = ?'); $q->execute([$id]);
            if ($b = $q->fetch()) {
                $pw = zufallspasswort();
                $db->prepare('UPDATE benutzer SET passwort = ?, muss_aendern = 1 WHERE id = ?')->execute([passwort_hash($pw), $id]);
                protokoll('Passwort zurückgesetzt', $b['kennung']);
                $neues_passwort = [$b['name'], $b['kennung'], $pw];
            }
        } elseif ($aktion === 'loeschen' && $id && $id !== $ich) {
            $db->prepare('DELETE FROM benutzer WHERE id = ?')->execute([$id]);
            protokoll('Benutzer gelöscht', "#$id");
            meldung('Der Zugang wurde gelöscht.');
            umleiten('index.php?s=benutzer');
        }
    }
    $liste = $db->query('SELECT * FROM benutzer ORDER BY aktiv DESC, name')->fetchAll();
    seite_anfang('Benutzer', 'benutzer');
    echo '<h1>Benutzer</h1>';
    echo '<p>Nur Personen mit einem Zugang können Inhalte ändern. <strong>Administration</strong> darf alles, <strong>Redaktion</strong> darf Inhalte veröffentlichen und löschen, <strong>Mitarbeit</strong> darf nur Entwürfe schreiben, die die Redaktion freigibt.</p>';
    foreach ($fehler as $f) echo '<p class="v-meldung v-meldung--fehler" role="alert">' . h($f) . '</p>';
    if ($neues_passwort) {
        [$n, $k, $p] = $neues_passwort;
        echo '<div class="v-meldung v-meldung--ok" role="status"><p><strong>Vorläufiges Passwort für ' . h($n) . '</strong> (Benutzername <code>' . h($k) . '</code>):</p>'
            . '<p class="v-passwort"><code>' . h($p) . '</code></p><p>Es wird nur jetzt angezeigt. Geben Sie es persönlich oder telefonisch weiter, nicht per E-Mail zusammen mit dem Benutzernamen. Bei der ersten Anmeldung muss ein eigenes Passwort gewählt werden.</p></div>';
    }
    echo '<ul class="v-eintraege v-eintraege--benutzer">';
    foreach ($liste as $b) {
        $ich = (int) $b['id'] === (int) benutzer()['id'];
        echo '<li><div><strong>' . h($b['name']) . '</strong><span>' . h($b['kennung']) . ' · ' . h(ROLLEN[$b['rolle']]) . ($b['aktiv'] ? '' : ' · gesperrt')
            . ' · zuletzt angemeldet: ' . ($b['letzte_anmeldung'] ? h(datum_de($b['letzte_anmeldung'])) : 'noch nie') . '</span></div>';
        if (!$ich) {
            echo '<details class="v-aufklapp"><summary>Ändern</summary>'
                . '<form method="post" class="v-formular" action="index.php?s=benutzer">' . csrf_feld() . '<input type="hidden" name="aktion" value="aendern"><input type="hidden" name="id" value="' . (int) $b['id'] . '">'
                . feld_auswahl('rolle', 'Rolle', ROLLEN, $b['rolle'])
                . '<div class="v-feld v-feld--haken"><input id="f-aktiv-' . (int) $b['id'] . '" type="checkbox" name="aktiv" value="1"' . ($b['aktiv'] ? ' checked' : '') . '><label for="f-aktiv-' . (int) $b['id'] . '">Zugang aktiv</label></div>'
                . '<div class="v-knoepfe"><button class="v-knopf v-knopf--zweit" type="submit">Speichern</button></div></form>'
                . '<form method="post" action="index.php?s=benutzer" data-bestaetigen="Neues vorläufiges Passwort für ' . h($b['name']) . ' erzeugen?">' . csrf_feld() . '<input type="hidden" name="aktion" value="passwort"><input type="hidden" name="id" value="' . (int) $b['id'] . '"><button class="v-link" type="submit">Neues Passwort vergeben</button></form>'
                . '<form method="post" action="index.php?s=benutzer" data-bestaetigen="Den Zugang von ' . h($b['name']) . ' endgültig löschen?">' . csrf_feld() . '<input type="hidden" name="aktion" value="loeschen"><input type="hidden" name="id" value="' . (int) $b['id'] . '"><button class="v-link v-link--gefahr" type="submit">Zugang löschen</button></form>'
                . '</details>';
        } else {
            echo '<span class="v-hilfe">Das sind Sie.</span>';
        }
        echo '</li>';
    }
    echo '</ul>';
    echo '<h2>Neuen Zugang anlegen</h2><form method="post" class="v-formular" action="index.php?s=benutzer">' . csrf_feld() . '<input type="hidden" name="aktion" value="anlegen">'
        . '<div class="v-zeile">' . feld_text('name', 'Name', '', ['pflicht' => true, 'max' => 80]) . feld_text('kennung', 'Benutzername', '', ['pflicht' => true, 'max' => 60, 'hilfe' => 'Zum Beispiel vorname.nachname']) . '</div>'
        . feld_auswahl('rolle', 'Rolle', ROLLEN, 'redaktion')
        . '<div class="v-knoepfe"><button class="v-knopf" type="submit">Zugang anlegen</button></div>'
        . '<p class="v-hilfe">Es wird ein vorläufiges Passwort erzeugt und einmal angezeigt.</p></form>';
    seite_ende();
}

/* =====================================================================
   Eigenes Konto
   ===================================================================== */
function bereich_konto(bool $post): void
{
    $fehler = [];
    if ($post) {
        $alt = (string) ($_POST['alt'] ?? ''); $neu = (string) ($_POST['neu'] ?? ''); $neu2 = (string) ($_POST['neu2'] ?? '');
        if (!password_verify($alt, benutzer()['passwort'])) $fehler[] = 'Das bisherige Passwort stimmt nicht.';
        if ($e = passwort_pruefen_regeln($neu)) $fehler[] = $e;
        if ($neu !== $neu2) $fehler[] = 'Die beiden neuen Passwörter sind nicht gleich.';
        if ($neu === $alt) $fehler[] = 'Bitte ein anderes Passwort als bisher wählen.';
        if (!$fehler) {
            db()->prepare('UPDATE benutzer SET passwort = ?, muss_aendern = 0 WHERE id = ?')->execute([passwort_hash($neu), benutzer()['id']]);
            session_regenerate_id(true);
            protokoll('Eigenes Passwort geändert');
            meldung('Ihr neues Passwort ist gespeichert.');
            umleiten('index.php');
        }
    }
    seite_anfang('Mein Zugang', 'konto');
    echo '<div class="v-schmal"><h1>Mein Zugang</h1><p>' . h(benutzer()['name']) . ', Benutzername <code>' . h(benutzer()['kennung']) . '</code>, Rolle: ' . h(ROLLEN[benutzer()['rolle']]) . '.</p>';
    foreach ($fehler as $f) echo '<p class="v-meldung v-meldung--fehler" role="alert">' . h($f) . '</p>';
    echo '<h2>Passwort ändern</h2><form method="post" class="v-formular" action="index.php?s=konto">' . csrf_feld()
        . feld_text('alt', 'Bisheriges Passwort', '', ['pflicht' => true, 'typ' => 'password', 'auto' => 'current-password'])
        . feld_text('neu', 'Neues Passwort', '', ['pflicht' => true, 'typ' => 'password', 'auto' => 'new-password', 'hilfe' => 'Mindestens 12 Zeichen. Ein Satz aus mehreren Wörtern ist gut zu merken und sicher.'])
        . feld_text('neu2', 'Neues Passwort wiederholen', '', ['pflicht' => true, 'typ' => 'password', 'auto' => 'new-password'])
        . '<div class="v-knoepfe"><button class="v-knopf" type="submit">Passwort speichern</button></div></form></div>';
    seite_ende();
}

/* =====================================================================
   Sicherung und Protokoll (nur Administration)
   ===================================================================== */
function bereich_sicherung(bool $post): void
{
    recht_noetig('sicherung');
    if ($post && post('aktion') === 'herunterladen') {
        if (!class_exists('ZipArchive')) { meldung('Auf diesem Server fehlt die ZIP-Erweiterung. Die täglichen Sicherungen liegen trotzdem im Datenordner.', 'fehler'); umleiten('index.php?s=sicherung'); }
        $tmp = sicherungs_ordner() . '/download-' . bin2hex(random_bytes(6));
        db()->exec('VACUUM INTO ' . db()->quote($tmp . '.sqlite'));
        $zip = new ZipArchive();
        $zip->open($tmp . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($tmp . '.sqlite', 'philathlos.sqlite');
        foreach (glob(medien_ordner() . '/*.{jpg,png,pdf}', GLOB_BRACE) ?: [] as $d) $zip->addFile($d, 'medien/' . basename($d));
        $zip->addFromString('LIESMICH.txt', "Sicherung der Verwaltung T.C. Philathlos vom " . jetzt() . ".\nphilathlos.sqlite gehört in den Datenordner, der Ordner medien/ in das Medienverzeichnis der Website.\nAnleitung zur Wiederherstellung: VERWALTUNG.md\n");
        $zip->close();
        @unlink($tmp . '.sqlite');
        protokoll('Sicherung heruntergeladen');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="philathlos-sicherung-' . date('Y-m-d') . '.zip"');
        header('Content-Length: ' . filesize($tmp . '.zip'));
        readfile($tmp . '.zip');
        @unlink($tmp . '.zip');
        exit;
    }
    $sicherungen = glob(sicherungs_ordner() . '/philathlos-*.sqlite') ?: [];
    rsort($sicherungen);
    $protokoll = db()->query('SELECT * FROM protokoll ORDER BY id DESC LIMIT 150')->fetchAll();
    seite_anfang('Sicherung', 'sicherung');
    echo '<h1>Sicherung und Protokoll</h1>';
    echo '<p>Die Datenbank wird jeden Tag automatisch gesichert (beim ersten Aufruf der Verwaltung). Die letzten ' . (int) konfig('sicherungen_behalten') . ' Tage bleiben erhalten. Zusätzlich sollten Sie einmal im Monat eine vollständige Sicherung mit allen Bildern herunterladen und an einem anderen Ort aufbewahren.</p>';
    echo '<form method="post" action="index.php?s=sicherung">' . csrf_feld() . '<input type="hidden" name="aktion" value="herunterladen"><button class="v-knopf" type="submit">Vollständige Sicherung herunterladen</button></form>';
    echo '<h2>Automatische Sicherungen</h2>';
    echo $sicherungen ? '<ul class="v-liste">' . implode('', array_map(fn($f) => '<li>' . h(datum_de(substr(basename($f), 11, 10))) . ' <small>(' . round(filesize($f) / 1024) . ' KB)</small></li>', $sicherungen)) . '</ul>' : '<p>Noch keine.</p>';
    echo '<h2>Protokoll</h2><p class="v-hilfe">Wer hat wann was geändert. Einträge werden nach zwei Jahren gelöscht.</p><table class="v-tabelle"><thead><tr><th scope="col">Zeit</th><th scope="col">Person</th><th scope="col">Aktion</th></tr></thead><tbody>';
    foreach ($protokoll as $p) echo '<tr><td>' . h(date('d.m.Y H:i', strtotime($p['zeit']))) . '</td><td>' . h($p['benutzer_name']) . '</td><td>' . h($p['aktion']) . ($p['details'] ? ': ' . h($p['details']) : '') . '</td></tr>';
    echo '</tbody></table>';
    seite_ende();
}
