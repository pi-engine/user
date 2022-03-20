<?php

use User\Installer\Remove;

// Composer autoloading
include __DIR__ . '/../../../vendor/autoload.php';

$result = [
    'init'   => false,
    'config' => false,
];

$install          = new Remove();
$result['init']   = $install->init();
$result['config'] = $install->manageConfig();

var_dump($result);