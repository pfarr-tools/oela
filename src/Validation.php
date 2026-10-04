<?php
namespace Advent;

function validateRegistration(array $in, bool $requireConsent = true): array
{
    $data = [
        'name'=>trim((string)($in['name']??'')), 'street'=>trim((string)($in['street']??'')),
        'house_number'=>trim((string)($in['house_number']??'')),
        'phone'=>trim((string)($in['phone']??'')),
        'email'=>trim((string)($in['email']??'')), 'publication_consent'=>isset($in['publication_consent']) ? 1 : 0,
    ];
    $errors=[];
    foreach (['name'=>'Name','street'=>'Straße','house_number'=>'Hausnummer','phone'=>'Telefon'] as $k=>$label) {
        if ($data[$k]==='') {
            $errors[$k]="$label ist erforderlich.";
        }
    }
    if (mb_strlen($data['name'])>150) {
        $errors['name']='Name ist zu lang.';
    }
    if ($data['email']!=='' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email']='Bitte eine gültige E-Mail-Adresse eingeben.';
    }
    if ($requireConsent && !$data['publication_consent']) {
        $errors['publication_consent']='Die Zustimmung zur Veröffentlichung ist erforderlich.';
    }
    return [$data,$errors];
}
