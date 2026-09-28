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
    if ($app->weekdayShort(1, 2026) !== 'Di') {
        throw new RuntimeException('Der Wochentag für den 1. Dezember 2026 ist falsch.');
    }
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
        'address' => 'Teststraße 1',
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
        'address' => 'Zielstraße 3',
        'phone' => '0345',
        'email' => '',
        'publication_consent' => 1,
    ]);
    if ($app->dayAvailable('nebringen', 3, 1)) {
        throw new RuntimeException('Ein belegter Zielsatg wurde als frei gemeldet.');
    }
    $app->saveAdmin('nebringen', 1, 2, [
        'name' => 'Test',
        'address' => 'Teststraße 1',
        'phone' => '0123',
        'email' => '',
        'publication_consent' => 1,
    ]);
    if ($app->registration('nebringen', 1) !== null || $app->registration('nebringen', 2)['name'] !== 'Test') {
        throw new RuntimeException('Der Admin-Tageswechsel wurde nicht korrekt gespeichert.');
    }
    if ($app->registration('nebringen', 2)['address'] !== 'Teststraße 1') {
        throw new RuntimeException('Ortsangabe wurde nicht gespeichert.');
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
        VALUES ('nebringen', 4, 'Alt', 'Alte Straße', '9', '0123', NULL, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    unset($legacyDb);
    putenv('DB_PATH='.$legacyPath);
    $legacyApp = new App($legacyRoot);
    if ($legacyApp->registration('nebringen', 4)['address'] !== 'Alte Straße 9') {
        throw new RuntimeException('Die Ortsangabe wurde bei der Legacy-Migration nicht übernommen.');
    }
    echo "AppTest: OK\n";
} finally {
    putenv('DB_PATH');
    putenv('LOCATION');
    array_map('unlink', glob($root.'/var/*.sqlite') ?: []);
    rmdir($root.'/var');
    unlink($root.'/.env');
    rmdir($root);
    if (isset($legacyRoot)) {
        array_map('unlink', glob($legacyRoot.'/var/*.sqlite') ?: []);
        rmdir($legacyRoot.'/var');
        rmdir($legacyRoot);
    }
}
