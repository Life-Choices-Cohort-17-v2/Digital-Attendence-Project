<?php
$users = [
    'thina@company.com' => 'thina@01',
    'marco@company.com' => 'marco@02',
    'dominic@company.com' => 'dom@03',
    'jose@company.com' => 'jose@04',
    'lutfeeya@company.com' => 'lutfeeya@05',
    'mcdonald@company.com' => 'mcdonald@06',
    'amohelang@company.com' => 'amohelang@07',
    'ntsapo@company.com' => 'ntsapo@08',
];

foreach ($users as $email => $plain) {
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    echo "UPDATE users SET password_hash = '$hash' WHERE email = '$email';\n";
}