<?php

return [
    'jwt' => [
        'secret'      => '',
        //'exp_access'  => 900, // 15 min
        'exp_access'  => 1209600, // 14 days, for development
        'exp_refresh' => 7776000, // 90 days
    ],
];