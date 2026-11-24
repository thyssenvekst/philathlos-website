<?php
/* Oberfläche des Verwaltungsbereichs: Seitenrahmen und Formularbausteine. */
declare(strict_types=1);

function seite_anfang(string $titel, string $aktiv = ''): void
{
    $b = benutzer();
    $css = 'verwaltung.css?v=' . substr(md5_file(dirname(__DIR__) . '/verwaltung.css') ?: '', 0, 8);
    $js = 'verwaltung.js?v=' . substr(md5_file(dirname(__DIR__) . '/verwaltung.js') ?: '', 0, 8);
    echo '<!doctype html><html lang="de"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="robots" content="noindex,nofollow">'
        . '<title>' . h($titel) . ' – Verwaltung T.C. Philathlos</title>'
        . '<link rel="icon" href="../favicon.svg" type="image/svg+xml">'
        . '<link rel="stylesheet" href="' . h($css) . '">'
        . '<script src="' . h($js) . '" defer></script></head><body>';
    echo '<a class="sprung" href="#inhalt">Zum Inhalt springen</a>';
    echo '<header class="v-kopf"><a class="v-marke" href="index.php"><img src="../assets/img/wappen.svg" width="36" height="36" alt=""><span>Verwaltung<small>T.C. Philathlos</small></span></a>';
    if ($b) {
        $punkte = [
            ['index.php', 'start', 'Übersicht', 'inhalt'],
            ['index.php?s=mitteilungen', 'mitteilungen', 'Schwarzes Brett', 'inhalt'],
            ['index.php?s=termine', 'termine', 'Termine', 'inhalt'],
            ['index.php?s=medien', 'medien', 'Bilder und PDFs', 'medien'],
            ['index.php?s=startseite', 'startseite', 'Startseite', 'startseite'],
            ['index.php?s=benutzer', 'benutzer', 'Benutzer', 'benutzer'],
            ['index.php?s=sicherung', 'sicherung', 'Sicherung', 'sicherung'],
        ];
        echo '<nav class="v-nav" aria-label="Verwaltung"><ul>';
        foreach ($punkte as [$url, $id, $name, $recht]) {
            if (!darf($recht)) continue;
            echo '<li><a href="' . h($url) . '"' . ($aktiv === $id ? ' aria-current="page"' : '') . '>' . h($name) . '</a></li>';
        }
        echo '</ul></nav>';
        echo '<div class="v-konto"><a href="index.php?s=konto"' . ($aktiv === 'konto' ? ' aria-current="page"' : '') . '>' . h($b['name']) . '</a>'
            . '<form method="post" action="index.php?s=abmelden">' . csrf_feld() . '<button type="submit" class="v-link">Abmelden</button></form>'
            . '<a class="v-link" href="../index.html" target="_blank" rel="noopener">Website ansehen</a></div>';
    }
    echo '</header><main id="inhalt" class="v-inhalt">';
    foreach (meldungen_holen() as [$art, $text]) {
        echo '<p class="v-meldung v-meldung--' . h($art) . '" role="' . ($art === 'fehler' ? 'alert' : 'status') . '">' . h($text) . '</p>';
    }
}

function seite_ende(): void
{
    echo '</main><footer class="v-fuss"><p>Verwaltung des T.C. Philathlos München e.V. Version ' . VERWALTUNG_VERSION . '. Änderungen erscheinen sofort auf der Website.</p></footer></body></html>';
}

function feld_text(string $name, string $label, string $wert = '', array $o = []): string
{
    $id = 'f-' . $name;
    $hilfe = isset($o['hilfe']) ? '<span class="v-hilfe" id="' . $id . '-hilfe">' . h($o['hilfe']) . '</span>' : '';
    $attr = ($o['pflicht'] ?? false ? ' required' : '') . (isset($o['max']) ? ' maxlength="' . (int) $o['max'] . '"' : '')
        . (isset($o['hilfe']) ? ' aria-describedby="' . $id . '-hilfe"' : '') . (isset($o['auto']) ? ' autocomplete="' . h($o['auto']) . '"' : '');
    $typ = $o['typ'] ?? 'text';
    $label_html = '<label for="' . $id . '">' . h($label) . ($o['pflicht'] ?? false ? '' : ' <small>(freiwillig)</small>') . '</label>';
    if ($typ === 'textarea') {
        return '<div class="v-feld">' . $label_html . $hilfe . '<textarea id="' . $id . '" name="' . h($name) . '" rows="' . (int) ($o['zeilen'] ?? 8) . '"' . $attr . '>' . h($wert) . '</textarea></div>';
    }
    return '<div class="v-feld">' . $label_html . $hilfe . '<input id="' . $id . '" type="' . h($typ) . '" name="' . h($name) . '" value="' . h($wert) . '"' . $attr . '></div>';
}

function feld_auswahl(string $name, string $label, array $optionen, ?string $wert, string $hilfe = ''): string
{
    $id = 'f-' . $name;
    $o = '';
    foreach ($optionen as $k => $v) $o .= '<option value="' . h((string) $k) . '"' . ((string) $k === (string) $wert ? ' selected' : '') . '>' . h($v) . '</option>';
    return '<div class="v-feld"><label for="' . $id . '">' . h($label) . '</label>'
        . ($hilfe ? '<span class="v-hilfe" id="' . $id . '-hilfe">' . h($hilfe) . '</span>' : '')
        . '<select id="' . $id . '" name="' . h($name) . '"' . ($hilfe ? ' aria-describedby="' . $id . '-hilfe"' : '') . '>' . $o . '</select></div>';
}

/** Auswahl eines vorhandenen Bildes oder PDFs plus Feld für eine neue Datei */
function feld_datei(string $art, ?int $gewaehlt): string
{
    $liste = db()->prepare('SELECT id, titel, klein, datei, erstellt FROM medien WHERE art = ? ORDER BY erstellt DESC LIMIT 200');
    $liste->execute([$art]);
    $optionen = ['' => $art === 'bild' ? 'Kein Bild' : 'Keine PDF-Datei'];
    $vorschau = '';
    foreach ($liste as $m) {
        $optionen[$m['id']] = $m['titel'] . ' (' . datum_de($m['erstellt']) . ')';
        if ($art === 'bild' && (int) $m['id'] === $gewaehlt) {
            $vorschau = '<img class="v-vorschau" src="' . h('../' . medien_url($m['klein'] ?: $m['datei'])) . '" alt="">';
        }
    }
    $name = $art === 'bild' ? 'bild_id' : 'anhang_id';
    $titel = $art === 'bild' ? 'Bild' : 'PDF-Datei';
    $html = '<fieldset class="v-datei"><legend>' . $titel . ' <small>(freiwillig)</small></legend>' . $vorschau
        . feld_auswahl($name, 'Vorhandene Datei wählen', $optionen, $gewaehlt ? (string) $gewaehlt : '');
    $html .= '<div class="v-feld"><label for="f-neu-' . $art . '">Oder neue Datei hochladen</label>'
        . '<span class="v-hilfe">' . ($art === 'bild' ? 'Foto als JPG, PNG oder WebP, höchstens ' : 'PDF, höchstens ') . (int) konfig('max_upload_mb') . ' MB.</span>'
        . '<input id="f-neu-' . $art . '" type="file" name="neu_' . $art . '" accept="' . ($art === 'bild' ? 'image/jpeg,image/png,image/webp' : 'application/pdf') . '"></div>';
    $html .= feld_text('neu_' . $art . '_titel', $art === 'bild' ? 'Was ist auf dem neuen Bild zu sehen?' : 'Titel der neuen PDF-Datei', '', ['max' => 200, 'hilfe' => $art === 'bild' ? 'Kurze Beschreibung für Menschen, die das Bild nicht sehen können, z. B. „Kinder beim Training auf Platz 2“.' : 'Zum Beispiel „Ausschreibung Clubmeisterschaft“.']);
    return $html . '</fieldset>';
}

function status_marke(string $status, ?string $bis = null): string
{
    if ($status === 'veroeffentlicht' && $bis && $bis < heute()) return '<span class="v-marke-status v-marke-status--archiv">Abgelaufen</span>';
    return match ($status) {
        'veroeffentlicht' => '<span class="v-marke-status v-marke-status--live">Veröffentlicht</span>',
        'entwurf' => '<span class="v-marke-status">Entwurf</span>',
        'archiviert' => '<span class="v-marke-status v-marke-status--archiv">Archiviert</span>',
        default => '',
    };
}
