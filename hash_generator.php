<?php
$users = [
    ['jose', 'bodussy', 'Jose Pierre'],
    ['lutfeeya', 'green_frog', 'Lutfeeya Cupido'],
    ['marco', 'halaal', 'Marco Fisher'],
    ['dominic', 'slap_chips', 'Dominic Peck'],
    ['will', 'i_will', 'Will Mxabanisi'],
    ['thina', 'shoes_4_life', 'Thina Maliwa'],
    ['amohelang', 'i_do_not_know_what_to_put_here', 'Amohelang Mokhele'],
];

foreach ($users as $u) {
    $hash = password_hash($u[1], PASSWORD_BCRYPT);
    echo "('{$u[0]}', '$hash', '{$u[2]}'),\n";
}