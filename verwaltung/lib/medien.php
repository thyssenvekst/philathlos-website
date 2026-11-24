<?php
/*
 * Hochladen von Bildern und PDF-Dateien.
 * Schutz vor unberechtigten Dateien: Größe begrenzt, Dateityp am Inhalt erkannt
 * (nicht am Namen), Bilder werden mit GD neu gespeichert (entfernt eingebettete
 * Daten wie GPS-Koordinaten und unterbindet versteckten Code), Dateinamen werden
 * zufällig vergeben, im Medienordner wird kein Skript ausgeführt.
 */
declare(strict_types=1);

const BILD_GROSS = 2000;
const BILD_KLEIN = 800;

function medien_ordner(): string
{
    $o = konfig('medien_pfad');
    if (!is_dir($o) && !mkdir($o, 0755, true)) throw new RuntimeException('Medienordner kann nicht angelegt werden.');
    if (!is_file($o . '/.htaccess')) {
        @file_put_contents($o . '/.htaccess', medien_htaccess());
    }
    return $o;
}

function medien_htaccess(): string
{
    return "# Im Medienordner wird nichts ausgeführt, nur Bilder und PDFs werden ausgeliefert.\n"
        . "Options -Indexes -ExecCGI\n"
        . "<IfModule mod_php.c>\n  php_flag engine off\n</IfModule>\n"
        . "RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phar .pl .py .cgi .sh\n"
        . "<FilesMatch \"\\.(?i:php\\d?|phtml|phar|pl|py|cgi|sh|html?|svg|js)$\">\n  Require all denied\n</FilesMatch>\n"
        . "<IfModule mod_headers.c>\n  Header set X-Content-Type-Options \"nosniff\"\n</IfModule>\n";
}

/** Fehlertext für PHP-Upload-Fehler */
function upload_fehler(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Die Datei ist zu groß. Erlaubt sind höchstens ' . konfig('max_upload_mb') . ' MB.',
        UPLOAD_ERR_PARTIAL => 'Die Datei wurde nur teilweise übertragen. Bitte noch einmal versuchen.',
        UPLOAD_ERR_NO_FILE => 'Es wurde keine Datei ausgewählt.',
        default => 'Die Datei konnte nicht hochgeladen werden.',
    };
}

/**
 * Nimmt eine hochgeladene Datei aus $_FILES[$feld] an.
 * $art: 'bild' oder 'pdf'. Gibt die neue Medien-ID zurück oder wirft eine Ausnahme mit verständlichem Text.
 */
function datei_annehmen(string $feld, string $art, string $titel): int
{
    $f = $_FILES[$feld] ?? null;
    if (!$f || !is_array($f) || is_array($f['error'])) throw new DomainException('Es wurde keine Datei ausgewählt.');
    if ($f['error'] !== UPLOAD_ERR_OK) throw new DomainException(upload_fehler((int) $f['error']));
    if (!is_uploaded_file($f['tmp_name'])) throw new DomainException('Die Datei konnte nicht hochgeladen werden.');
    $max = (int) konfig('max_upload_mb') * 1024 * 1024;
    if ($f['size'] <= 0 || $f['size'] > $max) throw new DomainException('Die Datei ist zu groß. Erlaubt sind höchstens ' . konfig('max_upload_mb') . ' MB.');
    $titel = trim($titel);
    if ($titel === '') throw new DomainException($art === 'bild'
        ? 'Bitte beschreiben Sie kurz, was auf dem Bild zu sehen ist. Das hilft Menschen, die das Bild nicht sehen können.'
        : 'Bitte geben Sie der PDF-Datei einen Titel.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
    $ordner = medien_ordner();
    $name = date('Y') . '-' . bin2hex(random_bytes(8));
    $original = mb_substr(preg_replace('/[^\p{L}\p{N} ._-]/u', '', (string) $f['name']), 0, 120);

    if ($art === 'pdf') {
        $kopf = (string) file_get_contents($f['tmp_name'], false, null, 0, 5);
        if ($mime !== 'application/pdf' || $kopf !== '%PDF-') throw new DomainException('Das ist keine gültige PDF-Datei.');
        $datei = $name . '.pdf';
        if (!move_uploaded_file($f['tmp_name'], $ordner . '/' . $datei)) throw new RuntimeException('PDF konnte nicht gespeichert werden.');
        @chmod($ordner . '/' . $datei, 0644);
        db()->prepare('INSERT INTO medien(art, datei, titel, originalname, groesse, erstellt, erstellt_von) VALUES (?,?,?,?,?,?,?)')
            ->execute(['pdf', $datei, mb_substr($titel, 0, 200), $original, (int) $f['size'], jetzt(), benutzer()['id'] ?? null]);
        $id = (int) db()->lastInsertId();
        protokoll('PDF hochgeladen', $titel);
        return $id;
    }

    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new DomainException('Bitte ein Foto im Format JPG, PNG oder WebP hochladen.');
    }
    if (!function_exists('imagecreatefromstring')) throw new RuntimeException('GD fehlt auf dem Server.');
    $gd = gd_info();
    $kann = ['image/jpeg' => !empty($gd['JPEG Support']), 'image/png' => !empty($gd['PNG Support']), 'image/webp' => !empty($gd['WebP Support'])];
    if (!$kann[$mime]) {
        fehler_protokollieren("GD kann $mime nicht lesen (Serverkonfiguration).");
        $moeglich = implode(', ', array_map(fn($m) => strtoupper(substr($m, 6)), array_keys(array_filter($kann))));
        throw new DomainException('Dieses Bildformat kann der Server nicht verarbeiten. Bitte als ' . ($moeglich ?: 'anderes Format') . ' hochladen.');
    }
    $info = @getimagesize($f['tmp_name']);
    if (!$info || $info[0] < 200 || $info[1] < 200) throw new DomainException('Das Bild ist zu klein oder beschädigt (mindestens 200 × 200 Pixel).');
    if ($info[0] * $info[1] > 40_000_000) throw new DomainException('Das Bild hat zu viele Pixel. Bitte eine kleinere Version hochladen.');
    $quelle = @imagecreatefromstring((string) file_get_contents($f['tmp_name']));
    if (!$quelle) throw new DomainException('Das Bild konnte nicht gelesen werden.');
    $quelle = bild_ausrichten($quelle, $f['tmp_name'], $mime);
    [$gross, $b, $hh] = bild_verkleinern($quelle, BILD_GROSS);
    [$klein] = bild_verkleinern($quelle, BILD_KLEIN);
    // JPEG ist für Fotos am kleinsten; nur wenn der Server kein JPEG schreiben kann, PNG verwenden
    $endung = !empty($gd['JPEG Support']) ? 'jpg' : 'png';
    $schreiben = fn($bild, $pfad, $q) => $endung === 'jpg' ? imagejpeg($bild, $pfad, $q) : imagepng($bild, $pfad, 7);
    $datei = $name . '.' . $endung;
    $dateiKlein = $name . '-klein.' . $endung;
    if (!$schreiben($gross, $ordner . '/' . $datei, 82) || !$schreiben($klein, $ordner . '/' . $dateiKlein, 80)) {
        throw new RuntimeException('Bild konnte nicht gespeichert werden.');
    }
    @chmod($ordner . '/' . $datei, 0644);
    @chmod($ordner . '/' . $dateiKlein, 0644);
    db()->prepare('INSERT INTO medien(art, datei, klein, titel, originalname, breite, hoehe, groesse, erstellt, erstellt_von) VALUES (?,?,?,?,?,?,?,?,?,?)')
        ->execute(['bild', $datei, $dateiKlein, mb_substr($titel, 0, 200), $original, $b, $hh, (int) filesize($ordner . '/' . $datei), jetzt(), benutzer()['id'] ?? null]);
    $id = (int) db()->lastInsertId();
    protokoll('Bild hochgeladen', $titel);
    return $id;
}

/** Handyfotos stehen oft per EXIF quer; vor dem Entfernen der Metadaten richtig drehen */
function bild_ausrichten(GdImage $bild, string $pfad, string $mime): GdImage
{
    if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) return $bild;
    $exif = @exif_read_data($pfad);
    $o = (int) ($exif['Orientation'] ?? 1);
    $winkel = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
    if ($winkel) {
        $gedreht = imagerotate($bild, $winkel, 0);
        if ($gedreht) return $gedreht;
    }
    return $bild;
}

function bild_verkleinern(GdImage $q, int $max): array
{
    $w = imagesx($q);
    $hh = imagesy($q);
    $f = min(1, $max / max($w, $hh));
    $nw = max(1, (int) round($w * $f));
    $nh = max(1, (int) round($hh * $f));
    $z = imagecreatetruecolor($nw, $nh);
    imagefill($z, 0, 0, imagecolorallocate($z, 255, 255, 255)); // Transparenz auf Weiß
    imagecopyresampled($z, $q, 0, 0, 0, 0, $nw, $nh, $w, $hh);
    imageinterlace($z, true);
    return [$z, $nw, $nh];
}

function medium(int $id): ?array
{
    $s = db()->prepare('SELECT * FROM medien WHERE id = ?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

/** Wo wird eine Datei verwendet? */
function medium_verwendung(int $id): array
{
    $s = db()->prepare("SELECT 'Mitteilung' AS art, titel FROM mitteilungen WHERE bild_id = :id OR anhang_id = :id
                        UNION ALL SELECT 'Termin', titel FROM termine WHERE bild_id = :id OR anhang_id = :id");
    $s->execute(['id' => $id]);
    return $s->fetchAll();
}

function medium_loeschen(int $id): void
{
    $m = medium($id);
    if (!$m) return;
    db()->prepare('DELETE FROM medien WHERE id = ?')->execute([$id]);
    foreach ([$m['datei'], $m['klein']] as $d) {
        if ($d && preg_match('/^[\w-]+\.(jpg|png|pdf)$/', $d)) @unlink(medien_ordner() . '/' . $d);
    }
    protokoll('Datei gelöscht', $m['titel']);
}
