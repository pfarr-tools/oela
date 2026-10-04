<?php
declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$app = new class {
    public function title(): string { return 'Lebendiger Adventskalender'; }
    public function locations(): array { return ['nebringen' => 'Nebringen']; }
    public function locationStartTime(string $slug): ?string { return $slug === 'nebringen' ? '17:00' : null; }
    public function logoImage(): string { return '/logo.png'; }
    public function isLocked(string $location): bool { return false; }
    public function weekdayShort(int $day): string { return 'Di'; }
    public function obfuscatedContactHref(string $slug): ?string { return $slug === 'nebringen' ? '&#x6d;&#x61;&#x69;&#x6c;&#x74;&#x6f;&#x3a;&#x6b;&#x6f;&#x6e;&#x74;&#x61;&#x6b;&#x74;&#x40;&#x65;&#x78;&#x61;&#x6d;&#x70;&#x6c;&#x65;&#x2e;&#x6f;&#x72;&#x67;' : null; }
    public function legalMarkdown(string $key): ?string { return null; }
};

ob_start();
require dirname(__DIR__) . '/templates/home.php';
$home = ob_get_clean();
if (!str_contains($home, 'Nebringen') || str_contains($home, '(jeweils um 17:00 Uhr)')) {
    throw new RuntimeException('Die Startzeit wird fälschlich auf der Ortsauswahl angezeigt.');
}

$location = 'nebringen';
$locationName = 'Nebringen';
$regs = [];
ob_start();
require dirname(__DIR__) . '/templates/calendar.php';
$calendar = ob_get_clean();
if (!str_contains($calendar, 'Nebringen') || !str_contains($calendar, '(17:00 Uhr)')) {
    throw new RuntimeException('Die Startzeit wird nicht neben dem Ortsnamen angezeigt.');
}

$content = '<p>Kalender</p>';
$title = 'Lebendiger Adventskalender – Nebringen';
ob_start();
require dirname(__DIR__) . '/templates/layout.php';
$layout = ob_get_clean();
if (!str_contains($layout, 'Kontakt') || !str_contains($layout, '&#x6d;') || str_contains($layout, 'mailto:kontakt@example.org')) {
    throw new RuntimeException('Der obfuskierte Kontakt-Link fehlt oder enthält die Klartext-Adresse.');
}

echo "CityTemplateTest: OK\n";
