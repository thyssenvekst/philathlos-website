<?php
/*
 * Öffentliche, nur lesende Schnittstelle für die Website:
 * Hinweis auf der Startseite, schwarzes Brett, Termine und Archiv.
 * Gibt nur veröffentlichte Inhalte und keine personenbezogenen Daten aus.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/verwaltung/lib/kern.php';
fehlerbehandlung_einrichten(true);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit;
}
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, max-age=0');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");

$db = db();
$heute = heute();

function medium_daten(PDO $db, ?int $id): ?array
{
    if (!$id) return null;
    $s = $db->prepare('SELECT art, datei, klein, titel, breite, hoehe FROM medien WHERE id = ?');
    $s->execute([$id]);
    $m = $s->fetch();
    if (!$m) return null;
    return $m['art'] === 'bild'
        ? ['url' => medien_url($m['datei']), 'klein' => medien_url($m['klein'] ?: $m['datei']), 'alt' => $m['titel'], 'breite' => (int) $m['breite'], 'hoehe' => (int) $m['hoehe']]
        : ['url' => medien_url($m['datei']), 'titel' => $m['titel']];
}

function mitteilung_json(PDO $db, array $m): array
{
    return [
        'id' => 'm' . $m['id'],
        'titel' => $m['titel'],
        'datum' => $m['datum'],
        'wichtig' => (bool) $m['wichtig'],
        'absaetze' => absaetze($m['text']),
        'bild' => medium_daten($db, $m['bild_id'] ? (int) $m['bild_id'] : null),
        'anhang' => medium_daten($db, $m['anhang_id'] ? (int) $m['anhang_id'] : null),
    ];
}

function termin_json(PDO $db, array $t): array
{
    return [
        'id' => 't' . $t['id'],
        'titel' => $t['titel'],
        'datum' => $t['datum'],
        'beginn' => $t['beginn'],
        'ende' => $t['ende'],
        'ort' => $t['ort'],
        'absaetze' => absaetze($t['beschreibung']),
        'bild' => medium_daten($db, $t['bild_id'] ? (int) $t['bild_id'] : null),
        'anhang' => medium_daten($db, $t['anhang_id'] ? (int) $t['anhang_id'] : null),
    ];
}

$s = $db->prepare("SELECT * FROM mitteilungen WHERE status = 'veroeffentlicht'
    AND (sichtbar_ab IS NULL OR sichtbar_ab <= :h) AND (sichtbar_bis IS NULL OR sichtbar_bis >= :h)
    ORDER BY wichtig DESC, datum DESC, id DESC LIMIT 30");
$s->execute(['h' => $heute]);
$mitteilungen = array_map(fn($m) => mitteilung_json($db, $m), $s->fetchAll());

$s = $db->prepare("SELECT * FROM mitteilungen WHERE status = 'archiviert' OR (status = 'veroeffentlicht' AND sichtbar_bis < :h)
    ORDER BY datum DESC, id DESC LIMIT 30");
$s->execute(['h' => $heute]);
$archivMitteilungen = array_map(fn($m) => mitteilung_json($db, $m), $s->fetchAll());

$s = $db->prepare("SELECT * FROM termine WHERE status = 'veroeffentlicht' AND datum >= :h ORDER BY datum, beginn, id LIMIT 60");
$s->execute(['h' => $heute]);
$termine = array_map(fn($t) => termin_json($db, $t), $s->fetchAll());

$s = $db->prepare("SELECT * FROM termine WHERE status = 'veroeffentlicht' AND datum < :h ORDER BY datum DESC, beginn DESC LIMIT 30");
$s->execute(['h' => $heute]);
$archivTermine = array_map(fn($t) => termin_json($db, $t), $s->fetchAll());

$hinweis = null;
if (einstellung('hinweis_aktiv') === '1' && trim((string) einstellung('hinweis_text')) !== '') {
    $hinweis = [
        'text' => einstellung('hinweis_text'),
        'link' => einstellung('hinweis_link') ?: null,
        'linktext' => einstellung('hinweis_linktext') ?: null,
    ];
}

echo json_encode([
    'stand' => date(DATE_ATOM),
    'hinweis' => $hinweis,
    'mitteilungen' => $mitteilungen,
    'termine' => $termine,
    'archiv' => ['mitteilungen' => $archivMitteilungen, 'termine' => $archivTermine],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
