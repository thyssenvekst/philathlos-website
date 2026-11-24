<?php
/*
 * T.C. Philathlos München – Verwaltungsbereich, Kern.
 * Konfiguration, Datenbank (SQLite), Sitzung, Anmeldung, Rollen, CSRF-Schutz,
 * Protokoll, Sicherungen und Fehlerbehandlung. Keine externen Dienste.
 */
declare(strict_types=1);

const VERWALTUNG_VERSION = '1.0';

/* ---------- Konfiguration ---------- */
function konfig(?string $schluessel = null)
{
    static $k = null;
    if ($k === null) {
        $datei = dirname(__DIR__) . '/konfiguration.php';
        $standard = [
            'daten_pfad' => dirname(__DIR__, 2) . '/daten',
            'medien_pfad' => dirname(__DIR__, 2) . '/medien',
            'medien_url' => 'medien',
            'einrichtungs_schluessel' => '',
            'zeitzone' => 'Europe/Berlin',
            'https_erzwingen' => true,
            'max_upload_mb' => 10,
            'sicherungen_behalten' => 14,
        ];
        $k = is_file($datei) ? array_merge($standard, (array) require $datei) : $standard;
        date_default_timezone_set($k['zeitzone']);
    }
    return $schluessel === null ? $k : ($k[$schluessel] ?? null);
}

function heute(): string { return date('Y-m-d'); }
function jetzt(): string { return date('Y-m-d H:i:s'); }

/* ---------- Fehlerbehandlung: nichts Technisches nach außen, alles ins Fehlerprotokoll ---------- */
function fehler_protokollieren(string $text): string
{
    $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $pfad = konfig('daten_pfad');
    if (is_dir($pfad) && is_writable($pfad)) {
        @file_put_contents($pfad . '/fehler.log', '[' . jetzt() . "] [$code] $text\n", FILE_APPEND | LOCK_EX);
    } else {
        error_log("Philathlos Verwaltung [$code] $text");
    }
    return $code;
}

function fehlerbehandlung_einrichten(bool $json = false): void
{
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    set_error_handler(function (int $nr, string $text, string $datei, int $zeile) {
        if (!(error_reporting() & $nr)) return false;
        throw new ErrorException($text, 0, $nr, $datei, $zeile);
    });
    set_exception_handler(function (Throwable $e) use ($json) {
        $code = fehler_protokollieren(get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        if (!headers_sent()) http_response_code(500);
        if ($json) {
            if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['fehler' => 'Die Inhalte konnten gerade nicht geladen werden.', 'code' => $code]);
            return;
        }
        echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Fehler</title>'
            . '<body style="font:18px/1.5 system-ui;max-width:36rem;margin:3rem auto;padding:0 1rem">'
            . '<h1 style="font-size:1.5rem">Das hat leider nicht geklappt</h1>'
            . '<p>Es ist ein technischer Fehler aufgetreten. Ihre letzte Änderung wurde eventuell nicht gespeichert.</p>'
            . '<p>Bitte versuchen Sie es noch einmal. Wenn der Fehler bleibt, nennen Sie der technischen Betreuung diesen Code: <strong>' . h($code) . '</strong></p>'
            . '<p><a href="index.php">Zurück zur Übersicht</a></p></body></html>';
    });
}

/* ---------- Datenbank ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $pfad = konfig('daten_pfad');
    if (!is_dir($pfad) && !mkdir($pfad, 0750, true)) {
        throw new RuntimeException('Datenordner kann nicht angelegt werden: ' . $pfad);
    }
    daten_ordner_schuetzen($pfad);
    $pdo = new PDO('sqlite:' . $pfad . '/philathlos.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL; PRAGMA busy_timeout = 5000;');
    schema($pdo);
    return $pdo;
}

function daten_ordner_schuetzen(string $pfad): void
{
    // Falls der Datenordner im Webroot liegt: Apache-Zugriff sperren.
    if (!is_file($pfad . '/.htaccess')) {
        @file_put_contents($pfad . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    }
    if (!is_file($pfad . '/index.html')) @file_put_contents($pfad . '/index.html', '');
}

function schema(PDO $pdo): void
{
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS benutzer (
      id INTEGER PRIMARY KEY,
      name TEXT NOT NULL,
      kennung TEXT NOT NULL UNIQUE COLLATE NOCASE,
      passwort TEXT NOT NULL,
      rolle TEXT NOT NULL CHECK (rolle IN ('admin','redaktion','mitarbeit')),
      aktiv INTEGER NOT NULL DEFAULT 1,
      muss_aendern INTEGER NOT NULL DEFAULT 0,
      letzte_anmeldung TEXT,
      erstellt TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS medien (
      id INTEGER PRIMARY KEY,
      art TEXT NOT NULL CHECK (art IN ('bild','pdf')),
      datei TEXT NOT NULL UNIQUE,
      klein TEXT,
      titel TEXT NOT NULL,
      originalname TEXT,
      breite INTEGER, hoehe INTEGER, groesse INTEGER,
      erstellt TEXT NOT NULL,
      erstellt_von INTEGER
    );
    CREATE TABLE IF NOT EXISTS mitteilungen (
      id INTEGER PRIMARY KEY,
      titel TEXT NOT NULL,
      text TEXT NOT NULL DEFAULT '',
      datum TEXT NOT NULL,
      status TEXT NOT NULL CHECK (status IN ('entwurf','veroeffentlicht','archiviert')),
      wichtig INTEGER NOT NULL DEFAULT 0,
      sichtbar_ab TEXT,
      sichtbar_bis TEXT,
      bild_id INTEGER REFERENCES medien(id) ON DELETE SET NULL,
      anhang_id INTEGER REFERENCES medien(id) ON DELETE SET NULL,
      erstellt TEXT NOT NULL,
      geaendert TEXT NOT NULL,
      geaendert_von INTEGER
    );
    CREATE TABLE IF NOT EXISTS termine (
      id INTEGER PRIMARY KEY,
      titel TEXT NOT NULL,
      beschreibung TEXT NOT NULL DEFAULT '',
      datum TEXT NOT NULL,
      beginn TEXT,
      ende TEXT,
      ort TEXT,
      status TEXT NOT NULL CHECK (status IN ('entwurf','veroeffentlicht')),
      bild_id INTEGER REFERENCES medien(id) ON DELETE SET NULL,
      anhang_id INTEGER REFERENCES medien(id) ON DELETE SET NULL,
      erstellt TEXT NOT NULL,
      geaendert TEXT NOT NULL,
      geaendert_von INTEGER
    );
    CREATE TABLE IF NOT EXISTS einstellungen (schluessel TEXT PRIMARY KEY, wert TEXT);
    CREATE TABLE IF NOT EXISTS protokoll (
      id INTEGER PRIMARY KEY, zeit TEXT NOT NULL, benutzer_id INTEGER, benutzer_name TEXT,
      aktion TEXT NOT NULL, details TEXT
    );
    CREATE TABLE IF NOT EXISTS anmeldeversuche (id INTEGER PRIMARY KEY, schluessel TEXT NOT NULL, zeit INTEGER NOT NULL);
    CREATE INDEX IF NOT EXISTS i_mitteilungen_status ON mitteilungen(status, datum);
    CREATE INDEX IF NOT EXISTS i_termine_datum ON termine(status, datum);
    CREATE INDEX IF NOT EXISTS i_versuche ON anmeldeversuche(schluessel, zeit);
    ");
}

function einstellung(string $schluessel, ?string $standard = null): ?string
{
    $s = db()->prepare('SELECT wert FROM einstellungen WHERE schluessel = ?');
    $s->execute([$schluessel]);
    $w = $s->fetchColumn();
    return $w === false ? $standard : $w;
}

function einstellung_setzen(string $schluessel, string $wert): void
{
    db()->prepare('INSERT INTO einstellungen(schluessel, wert) VALUES(?, ?) ON CONFLICT(schluessel) DO UPDATE SET wert = excluded.wert')
        ->execute([$schluessel, $wert]);
}

/* ---------- Hilfen ---------- */
function h(?string $t): string { return htmlspecialchars((string) $t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function post(string $feld, string $standard = ''): string
{
    $w = $_POST[$feld] ?? $standard;
    return is_string($w) ? trim(str_replace("\r\n", "\n", $w)) : $standard;
}

function gueltiges_datum(string $d): bool
{
    $x = DateTime::createFromFormat('!Y-m-d', $d);
    return $x !== false && $x->format('Y-m-d') === $d;
}

function gueltige_uhrzeit(string $u): bool { return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $u); }

function datum_de(?string $d): string
{
    if (!$d) return '';
    $x = DateTime::createFromFormat('!Y-m-d', substr($d, 0, 10));
    return $x ? $x->format('d.m.Y') : $d;
}

function umleiten(string $ziel): never
{
    header('Location: ' . $ziel, true, 303);
    exit;
}

function meldung(string $text, string $art = 'ok'): void { $_SESSION['meldungen'][] = [$art, $text]; }

function meldungen_holen(): array
{
    $m = $_SESSION['meldungen'] ?? [];
    unset($_SESSION['meldungen']);
    return $m;
}

/* ---------- Sitzung und Sicherheit ---------- */
function ist_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function sicherheits_kopfzeilen(): void
{
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store, max-age=0');
    header('X-Robots-Tag: noindex, nofollow');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

function sitzung_starten(): void
{
    if (konfig('https_erzwingen') && !ist_https()) {
        $host = preg_replace('/[^A-Za-z0-9.\-:]/', '', $_SERVER['HTTP_HOST'] ?? '');
        umleiten('https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/verwaltung/'));
    }
    session_name('philathlos_verwaltung');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => dirname($_SERVER['SCRIPT_NAME'] ?? '/verwaltung/index.php') . '/',
        'secure' => ist_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
    // Nach 60 Minuten ohne Aktivität abmelden
    if (isset($_SESSION['zuletzt']) && time() - $_SESSION['zuletzt'] > 3600) {
        $_SESSION = [];
        session_regenerate_id(true);
        meldung('Sie wurden nach einer Stunde ohne Aktivität abgemeldet.', 'hinweis');
    }
    $_SESSION['zuletzt'] = time();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function csrf_feld(): string { return '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">'; }

function csrf_pruefen(): void
{
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(400);
        meldung('Das Formular war veraltet. Bitte noch einmal versuchen.', 'fehler');
        umleiten('index.php');
    }
}

/* ---------- Benutzer und Rollen ---------- */
const ROLLEN = [
    'admin' => 'Administration',
    'redaktion' => 'Redaktion',
    'mitarbeit' => 'Mitarbeit (nur Entwürfe)',
];

const RECHTE = [
    'admin' => ['inhalt', 'veroeffentlichen', 'loeschen', 'medien', 'startseite', 'benutzer', 'sicherung', 'protokoll'],
    'redaktion' => ['inhalt', 'veroeffentlichen', 'loeschen', 'medien', 'startseite'],
    'mitarbeit' => ['inhalt', 'medien'],
];

function benutzer(): ?array
{
    static $b = false;
    if ($b !== false) return $b;
    $b = null;
    if (!empty($_SESSION['benutzer_id'])) {
        $s = db()->prepare('SELECT * FROM benutzer WHERE id = ? AND aktiv = 1');
        $s->execute([$_SESSION['benutzer_id']]);
        $b = $s->fetch() ?: null;
        if (!$b) unset($_SESSION['benutzer_id']);
    }
    return $b;
}

function darf(string $recht): bool
{
    $b = benutzer();
    return $b && in_array($recht, RECHTE[$b['rolle']] ?? [], true);
}

function recht_noetig(string $recht): void
{
    if (!darf($recht)) {
        http_response_code(403);
        meldung('Dafür fehlt Ihnen die Berechtigung.', 'fehler');
        umleiten('index.php');
    }
}

function passwort_pruefen_regeln(string $pw): ?string
{
    if (mb_strlen($pw) < 12) return 'Das Passwort muss mindestens 12 Zeichen lang sein.';
    if (mb_strlen($pw) > 200) return 'Das Passwort ist zu lang.';
    return null;
}

function passwort_hash(string $pw): string
{
    return defined('PASSWORD_ARGON2ID') ? password_hash($pw, PASSWORD_ARGON2ID) : password_hash($pw, PASSWORD_DEFAULT);
}

function zufallspasswort(): string
{
    $zeichen = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $pw = '';
    for ($i = 0; $i < 16; $i++) $pw .= $zeichen[random_int(0, strlen($zeichen) - 1)];
    return implode('-', str_split($pw, 4));
}

/* Anmeldung mit Bremse: höchstens 5 Fehlversuche je Kennung und je Adresse in 15 Minuten */
function versuchs_schluessel(string $art, string $wert): string
{
    return $art . ':' . hash('sha256', $wert . '|' . (konfig('einrichtungs_schluessel') ?: __DIR__));
}

function zu_viele_versuche(string $kennung): bool
{
    $db = db();
    $db->prepare('DELETE FROM anmeldeversuche WHERE zeit < ?')->execute([time() - 86400]);
    $s = $db->prepare('SELECT COUNT(*) FROM anmeldeversuche WHERE schluessel IN (?, ?) AND zeit > ?');
    $s->execute([versuchs_schluessel('k', mb_strtolower($kennung)), versuchs_schluessel('ip', $_SERVER['REMOTE_ADDR'] ?? ''), time() - 900]);
    return (int) $s->fetchColumn() >= 5;
}

function fehlversuch_merken(string $kennung): void
{
    $s = db()->prepare('INSERT INTO anmeldeversuche(schluessel, zeit) VALUES (?, ?)');
    $s->execute([versuchs_schluessel('k', mb_strtolower($kennung)), time()]);
    $s->execute([versuchs_schluessel('ip', $_SERVER['REMOTE_ADDR'] ?? ''), time()]);
}

function anmelden(string $kennung, string $pw): ?string
{
    if ($kennung === '' || $pw === '') return 'Bitte Benutzername und Passwort eingeben.';
    if (zu_viele_versuche($kennung)) return 'Zu viele Fehlversuche. Bitte warten Sie 15 Minuten und versuchen Sie es dann erneut.';
    $s = db()->prepare('SELECT * FROM benutzer WHERE kennung = ? AND aktiv = 1');
    $s->execute([$kennung]);
    $b = $s->fetch();
    // Auch bei unbekannter Kennung einen Hash prüfen, damit die Antwortzeit nichts verrät
    $hash = $b['passwort'] ?? passwort_hash('kein-gueltiges-konto');
    if (!password_verify($pw, $hash) || !$b) {
        fehlversuch_merken($kennung);
        return 'Benutzername oder Passwort stimmen nicht.';
    }
    if (password_needs_rehash($b['passwort'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE benutzer SET passwort = ? WHERE id = ?')->execute([passwort_hash($pw), $b['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['benutzer_id'] = (int) $b['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    db()->prepare('UPDATE benutzer SET letzte_anmeldung = ? WHERE id = ?')->execute([jetzt(), $b['id']]);
    protokoll('Anmeldung', '', $b);
    return null;
}

function abmelden(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Strict']);
    }
    session_destroy();
}

/* ---------- Protokoll ---------- */
function protokoll(string $aktion, string $details = '', ?array $b = null): void
{
    $b = $b ?? benutzer();
    db()->prepare('INSERT INTO protokoll(zeit, benutzer_id, benutzer_name, aktion, details) VALUES (?, ?, ?, ?, ?)')
        ->execute([jetzt(), $b['id'] ?? null, $b['name'] ?? '', $aktion, mb_substr($details, 0, 500)]);
    // Protokoll nicht endlos wachsen lassen: Einträge älter als 2 Jahre entfernen
    db()->prepare('DELETE FROM protokoll WHERE zeit < ?')->execute([date('Y-m-d', strtotime('-2 years'))]);
}

/* ---------- Sicherungen ---------- */
function sicherungs_ordner(): string
{
    $o = konfig('daten_pfad') . '/sicherungen';
    if (!is_dir($o)) mkdir($o, 0750, true);
    return $o;
}

/* Einmal am Tag beim ersten Aufruf der Verwaltung eine Kopie der Datenbank ablegen */
function tagessicherung(): void
{
    $ziel = sicherungs_ordner() . '/philathlos-' . heute() . '.sqlite';
    if (is_file($ziel)) return;
    db()->exec('VACUUM INTO ' . db()->quote($ziel));
    $alle = glob(sicherungs_ordner() . '/philathlos-*.sqlite') ?: [];
    sort($alle);
    $behalten = max(3, (int) konfig('sicherungen_behalten'));
    foreach (array_slice($alle, 0, max(0, count($alle) - $behalten)) as $alt) @unlink($alt);
}

/* ---------- Öffentliche Inhalte (für api/inhalte.php und die Vorschau) ---------- */
function medien_url(?string $datei): ?string
{
    return $datei ? rtrim((string) konfig('medien_url'), '/') . '/' . rawurlencode($datei) : null;
}

function absaetze(string $text): array
{
    $teile = preg_split("/\n\s*\n/", trim($text)) ?: [];
    return array_values(array_filter(array_map('trim', $teile), fn($t) => $t !== ''));
}
