<?php

use Laminas\Config\Config;
use Laminas\Db\Adapter\Adapter;
use User\Installer\Install;

// Composer autoloading
include realpath(__DIR__ . '/../../../vendor/autoload.php');

$result = [
    'init'   => false,
    'config' => false,
];

$config           = new Config(include realpath(__DIR__ . '/../../../config/autoload/global.php'));
$adapter          = new Adapter($config->db->toArray());
$install          = new Install($adapter);
echo $install->database();
echo $install->config();