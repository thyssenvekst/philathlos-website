<?php
/*
 * Nur für die lokale Entwicklung mit dem PHP-Testserver (php -S), nicht hochladen.
 * Ahmt die Sperren der .htaccess-Dateien nach, die der Testserver nicht kennt.
 */
$pfad = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$gesperrt = '~^/(daten/|verwaltung/lib/|verwaltung/konfiguration|_quellen/|\.git|.*\.md$|medien/.*\.(php\d?|phtml|phar|html?|svg|js)$)~i';
if (preg_match($gesperrt, $pfad)) { http_response_code(403); echo 'Forbidden'; return true; }
if ($pfad === '/') { $_SERVER['SCRIPT_NAME'] = '/index.html'; readfile(__DIR__ . '/../index.html'); return true; }
if (preg_match('~/$~', $pfad) && is_file(__DIR__ . '/..' . $pfad . 'index.php')) { $_SERVER['SCRIPT_NAME'] = $pfad . 'index.php'; chdir(__DIR__ . '/..' . $pfad); require __DIR__ . '/..' . $pfad . 'index.php'; return true; }
return false;
