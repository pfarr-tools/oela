<?php
declare(strict_types=1);

$vendor = dirname(__DIR__).'/vendor/autoload.php';
if (!is_file($vendor)) {
    $vendor = dirname(__DIR__, 2).'/vendor/autoload.php';
}
require $vendor;

use Advent\App;

$root = sys_get_temp_dir().'/oela-app-test-'.bin2hex(random_bytes(4));
mkdir($root, 0777, true);
mkdir($root.'/var', 0777, true);
file_put_contents($root.'/.env', "DB_PATH=var/production.sqlite\nAPP_SECRET=test-secret-test-secret-test-secret\n");

$testPath = $root.'/var/testing.sqlite';
putenv('DB_PATH='.$testPath);
putenv('LOCATION=Nebringen,Öschelbronn,Fußgänger');

try {
    $app = new App($root);
    $locations = $app->locations();
    if ($locations !== ['nebringen'=>'Nebringen', 'oeschelbronn'=>'Öschelbronn', 'fussgaenger'=>'Fußgänger']) {
        throw new RuntimeException('LOCATION wurde nicht korrekt in Orts-Slugs umgewandelt.');
    }

    $configuredRoot = sys_get_temp_dir().'/oela-cities-test-'.bin2hex(random_bytes(4));
    mkdir($configuredRoot.'/var', 0777, true);
    mkdir($configuredRoot.'/config', 0777, true);
    file_put_contents($configuredRoot.'/.env', "DB_PATH=var/advent.sqlite\nAPP_SECRET=test-secret-test-secret-test-secret\nCITIES_FILE=config/cities.json\n");
    file_put_contents($configuredRoot.'/config/cities.json', json_encode([
        'cities' => [
            ['name' => 'Nebringen', 'email' => 'kontakt@nebringen.example', 'start_time' => '17:00'],
            ['name' => 'Öschelbronn', 'email' => '', 'start_time' => ''],
        ],
    ], JSON_THROW_ON_ERROR));
    putenv('DB_PATH='.$configuredRoot.'/var/advent.sqlite');
    $configuredApp = new App($configuredRoot);
    if ($configuredApp->locations() !== ['nebringen' => 'Nebringen', 'oeschelbronn' => 'Öschelbronn']) {
        throw new RuntimeException('Die JSON-Ortskonfiguration wurde nicht geladen.');
    }
    if ($configuredApp->locationEmail('nebringen') !== 'kontakt@nebringen.example' || $configuredApp->locationEmail('oeschelbronn') !== null) {
        throw new RuntimeException('Die Orts-Kontaktadressen wurden nicht korrekt geladen.');
    }
    if ($configuredApp->locationStartTime('nebringen') !== '17:00' || $configuredApp->locationStartTime('oeschelbronn') !== null) {
        throw new RuntimeException('Die Orts-Startzeiten wurden nicht korrekt geladen.');
    }
    if ($configuredApp->obfuscatedContactHref('nebringen') === 'mailto:kontakt@nebringen.example' || !str_contains($configuredApp->obfuscatedContactHref('nebringen') ?? '', '&#x')) {
        throw new RuntimeException('Die Kontaktadresse wird nicht obfuskiert ausgegeben.');
    }
    putenv('DB_PATH='.$testPath);
    if ($app->weekdayShort(1, 2026) !== 'Di') {
        throw new RuntimeException('Der Wochentag für den 1. Dezember 2026 ist falsch.');
    }
    if ($app->title() !== 'Lebendiger Adventskalender' || $app->logoImage() !== '/oela_icon.png') {
        throw new RuntimeException('Die Standardwerte für Titel oder Logo sind falsch.');
    }
    if ($app->publicationConsentText() === '') {
        throw new RuntimeException('Der Standardtext für die Veröffentlichung fehlt.');
    }
    putenv('APP_TITLE=Testkalender');
    putenv('LOGO_IMAGE=/custom-logo.png');
    putenv('PUBLICATION_CONSENT_TEXT=Eigener Veröffentlichungstext');
    if ($app->title() !== 'Testkalender' || $app->logoImage() !== '/custom-logo.png' || $app->publicationConsentText() !== 'Eigener Veröffentlichungstext') {
        throw new RuntimeException('Konfigurationswerte für Titel, Logo oder Einwilligung werden nicht übernommen.');
    }
    putenv('APP_TITLE');
    putenv('LOGO_IMAGE');
    putenv('PUBLICATION_CONSENT_TEXT');
    if ($app->isLocked('nebringen')) {
        throw new RuntimeException('Ein Ort ist standardmäßig gesperrt.');
    }
    $app->setLocked('nebringen', true);
    if (!$app->isLocked('nebringen')) {
        throw new RuntimeException('Der Ort konnte nicht gesperrt werden.');
    }
    $app->setLocked('nebringen', false);
    if ($app->isLocked('nebringen')) {
        throw new RuntimeException('Der Ort konnte nicht wieder freigegeben werden.');
    }
    $app->save('nebringen', 1, [
        'name' => 'Test',
        'street' => 'Teststraße',
        'house_number' => '1',
        'phone' => '0123',
        'email' => '',
        'publication_consent' => 0,
    ]);

    if (!is_file($testPath)) {
        throw new RuntimeException('Testdatenbank wurde nicht verwendet.');
    }
    if (is_file($root.'/var/production.sqlite')) {
        throw new RuntimeException('Produktionsdatenbank wurde im Test angelegt.');
    }

    $app->save('nebringen', 3, [
        'name' => 'Ziel',
        'street' => 'Zielstraße',
        'house_number' => '3',
        'phone' => '0345',
        'email' => '',
        'publication_consent' => 1,
    ]);
    if ($app->dayAvailable('nebringen', 3, 1)) {
        throw new RuntimeException('Ein belegter Zielsatg wurde als frei gemeldet.');
    }
    $app->saveAdmin('nebringen', 1, 2, [
        'name' => 'Test',
        'street' => 'Teststraße',
        'house_number' => '1',
        'phone' => '0123',
        'email' => '',
        'publication_consent' => 1,
    ]);
    if ($app->registration('nebringen', 1) !== null || $app->registration('nebringen', 2)['name'] !== 'Test') {
        throw new RuntimeException('Der Admin-Tageswechsel wurde nicht korrekt gespeichert.');
    }
    if ($app->registration('nebringen', 2)['street'] !== 'Teststraße' || $app->registration('nebringen', 2)['house_number'] !== '1' || $app->registration('nebringen', 2)['address'] !== 'Teststraße 1') {
        throw new RuntimeException('Straße, Hausnummer oder Ortsangabe wurden nicht korrekt gespeichert.');
    }

    $legacyRoot = sys_get_temp_dir().'/oela-legacy-test-'.bin2hex(random_bytes(4));
    mkdir($legacyRoot.'/var', 0777, true);
    $legacyPath = $legacyRoot.'/var/advent.sqlite';
    $legacyDb = new PDO('sqlite:'.$legacyPath);
    $legacyDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $legacyDb->exec("CREATE TABLE registrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        location TEXT NOT NULL,
        day INTEGER NOT NULL,
        name TEXT NOT NULL,
        street TEXT NOT NULL,
        house_number TEXT NOT NULL,
        phone TEXT NOT NULL,
        email TEXT NULL,
        publication_consent INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        UNIQUE(location, day)
    )");
    $legacyDb->exec("INSERT INTO registrations (location, day, name, street, house_number, phone, email, publication_consent, created_at, updated_at)
        VALUES ('nebringen', 4, 'Alt', 'Alte Straße 9', '', '0123', NULL, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    unset($legacyDb);
    putenv('DB_PATH='.$legacyPath);
    $legacyApp = new App($legacyRoot);
    $legacyRegistration = $legacyApp->registration('nebringen', 4);
    if ($legacyRegistration['street'] !== 'Alte Straße' || $legacyRegistration['house_number'] !== '9' || $legacyRegistration['address'] !== 'Alte Straße 9') {
        throw new RuntimeException('Die Ortsangabe wurde bei der Legacy-Migration nicht aufgeteilt.');
    }
    echo "AppTest: OK\n";
} finally {
    putenv('DB_PATH');
    putenv('LOCATION');
    putenv('APP_TITLE');
    putenv('LOGO_IMAGE');
    putenv('PUBLICATION_CONSENT_TEXT');
    array_map('unlink', glob($root.'/var/*.sqlite') ?: []);
    rmdir($root.'/var');
    unlink($root.'/.env');
    rmdir($root);
    if (isset($legacyRoot)) {
        array_map('unlink', glob($legacyRoot.'/var/*.sqlite') ?: []);
        rmdir($legacyRoot.'/var');
        rmdir($legacyRoot);
    }
    if (isset($configuredRoot)) {
        array_map('unlink', glob($configuredRoot.'/config/*') ?: []);
        array_map('unlink', glob($configuredRoot.'/var/*.sqlite') ?: []);
        rmdir($configuredRoot.'/config');
        rmdir($configuredRoot.'/var');
        unlink($configuredRoot.'/.env');
        rmdir($configuredRoot);
    }
}
