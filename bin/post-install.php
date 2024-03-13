<?php

use User\Installer\Install;
use User\Service\InstallerService;

// Composer autoload
include realpath(__DIR__ . '/../../../vendor/autoload.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$sqlFile        = realpath(__DIR__ . '/../data/schema.sql');
$permissionFile = realpath(__DIR__ . '/../config/module.permission.php');

$result = [
    'init'   => false,
    'config' => false,
];

$install = new Install(new InstallerService());
$install->permission($permissionFile);