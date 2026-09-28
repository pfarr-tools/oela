<?php
declare(strict_types=1);

require dirname(__DIR__).'/Validation.php';

[$data, $errors] = \Advent\validateRegistration([
    'name' => 'Test',
    'address' => 'Teststraße 1',
    'phone' => '0123',
]);

if ($data['publication_consent'] !== 0 || !isset($errors['publication_consent'])) {
    throw new RuntimeException('Fehlende Veröffentlichungseinwilligung wurde nicht abgelehnt.');
}

[, $errors] = \Advent\validateRegistration([
    'name' => 'Test',
    'address' => 'Teststraße 1',
    'phone' => '0123',
    'publication_consent' => '1',
]);

if ($errors !== []) {
    throw new RuntimeException('Gültige Veröffentlichungseinwilligung wurde abgelehnt.');
}

echo "ValidationTest: OK\n";
