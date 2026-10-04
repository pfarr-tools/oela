<?php
declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$app = new class {
    public function weekdayShort(int $day): string
    {
        return 'Di';
    }

    public function csrf(): string
    {
        return 'csrf';
    }

    public function publicationConsentText(): string
    {
        return 'Consent';
    }
};
$admin = false;
$sig = '';
$location = 'nebringen';
$locationName = 'Nebringen';
$day = 1;
$data = [
    'name' => 'Test',
    'street' => 'Teststraße',
    'house_number' => '1',
    'phone' => '',
    'email' => '',
    'publication_consent' => 0,
];
$errors = ['phone' => 'Telefon ist erforderlich.'];
$availableDays = range(1, 23);

ob_start();
require dirname(__DIR__) . '/templates/form.php';
$html = ob_get_clean();

if (!str_contains($html, 'Telefon ist erforderlich.')) {
    throw new RuntimeException('Der Telefon-Validierungsfehler wird nicht im Formular angezeigt.');
}

if (!str_contains($html, 'id="phone"') || !str_contains($html, 'is-invalid')) {
    throw new RuntimeException('Das Telefonfeld wird nicht als ungültig markiert.');
}

if (!str_contains($html, 'class="row g-2"') || !str_contains($html, 'col-4 col-sm-3')) {
    throw new RuntimeException('Straße und Hausnummer werden nicht nebeneinander dargestellt.');
}

echo "FormTest: OK\n";
