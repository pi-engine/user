<?php

use User\Installer\Update;

// Composer autoloading
include __DIR__ . '/../../../vendor/autoload.php';

$result = [
    'init'   => false,
    'config' => false,
];

$install          = new Update();
$result['init']   = $install->init();
$result['config'] = $install->manageConfig();

var_dump($result);