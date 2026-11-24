<?php
/*
 * Konfiguration der Verwaltung. Diese Datei als „konfiguration.php“ kopieren
 * und anpassen. Die Kopie gehört nicht in Git und wird nie öffentlich ausgeliefert.
 */
return [
    // Datenbank, Sicherungen und Fehlerprotokoll. Am besten AUSSERHALB des
    // Webroots, z. B. '/home/kundenname/philathlos-daten'. Im Webroot schützt
    // eine automatisch angelegte .htaccess den Ordner (nur bei Apache).
    'daten_pfad' => __DIR__ . '/../daten',

    // Hochgeladene Bilder und PDFs (öffentlich, nur geprüfte Dateien)
    'medien_pfad' => __DIR__ . '/../medien',
    'medien_url' => 'medien',

    // Nur für die erste Einrichtung: einen langen Zufallswert eintragen,
    // ersten Zugang anlegen, danach wieder auf '' setzen.
    'einrichtungs_schluessel' => '',

    'zeitzone' => 'Europe/Berlin',

    // Verwaltung nur über HTTPS (dringend empfohlen; lokal zum Testen false)
    'https_erzwingen' => true,

    'max_upload_mb' => 10,
    'sicherungen_behalten' => 14,
];
