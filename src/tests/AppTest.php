<?php
declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Advent\App;

$root = sys_get_temp_dir().'/oela-app-test-'.bin2hex(random_bytes(4));
mkdir($root, 0777, true);
mkdir($root.'/var', 0777, true);
file_put_contents($root.'/.env', "DB_PATH=var/production.sqlite\nAPP_SECRET=test-secret-test-secret-test-secret\n");

$testPath = $root.'/var/testing.sqlite';
putenv('DB_PATH='.$testPath);

try {
    $app = new App($root);
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
    echo "AppTest: OK\n";
} finally {
    putenv('DB_PATH');
    array_map('unlink', glob($root.'/var/*.sqlite') ?: []);
    rmdir($root.'/var');
    unlink($root.'/.env');
    rmdir($root);
}
