<?php
declare(strict_types=1);

require dirname(__DIR__).'/App.php';
require dirname(__DIR__).'/Markdown.php';

use Advent\App;
use function Advent\renderMarkdown;

$root = sys_get_temp_dir().'/oela-legal-test-'.bin2hex(random_bytes(4));
mkdir($root.'/var', 0777, true);
mkdir($root.'/content', 0777, true);
file_put_contents($root.'/.env', "DB_PATH=var/advent.sqlite\nAPP_SECRET=legal-test-secret-legal-test-secret\nIMPRESSUM_MD=content/impressum.md\n");
file_put_contents($root.'/content/impressum.md', "# Impressum\n\n**OELA** [Kontakt](mailto:test@example.org)\n\n- Punkt");

try {
    $app = new App($root);
    if ($app->legalMarkdown('IMPRESSUM_MD') !== "# Impressum\n\n**OELA** [Kontakt](mailto:test@example.org)\n\n- Punkt") {
        throw new RuntimeException('Konfiguriertes Markdown wurde nicht gelesen.');
    }
    if ($app->legalMarkdown('DATENSCHUTZ_MD') !== null) {
        throw new RuntimeException('Nicht konfigurierte Datenschutzseite ist nicht ausgeblendet.');
    }

    $html = renderMarkdown($app->legalMarkdown('IMPRESSUM_MD'));
    if (!str_contains($html, '<h1>Impressum</h1>') || !str_contains($html, '<strong>OELA</strong>')) {
        throw new RuntimeException('Markdown wurde nicht in HTML umgewandelt.');
    }
    if (str_contains($html, '<script') || !str_contains($html, 'href="mailto:test@example.org"')) {
        throw new RuntimeException('Markdown-Ausgabe ist nicht sicher oder enthält keinen Link.');
    }
    echo "LegalTest: OK\n";
} finally {
    array_map('unlink', glob($root.'/content/*') ?: []);
    array_map('unlink', glob($root.'/var/*') ?: []);
    rmdir($root.'/content');
    rmdir($root.'/var');
    unlink($root.'/.env');
    rmdir($root);
}
