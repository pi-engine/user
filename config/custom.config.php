<?php

return [
    'jwt' => [
        'secret'      => 'xt2468xc9mh5hvnal80rng36bbk16co4',
        //'exp_access'  => 900, // 15 min
        'exp_access'  => 1209600, // 14 days, for development
        'exp_refresh' => 7776000, // 90 days
    ],
];