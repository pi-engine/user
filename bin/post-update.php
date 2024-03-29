<?php

use Laminas\Config\Config;
use Laminas\Db\Adapter\Adapter;
use User\Installer\Update;
use User\Service\PermissionService;
use User\Service\RoleService;

// Composer autoloading
include realpath(__DIR__ . '/../../../vendor/autoload.php');

$result = [
    'init'   => false,
    'config' => false,
];

$config     = new Config(include realpath(__DIR__ . '/../../../config/autoload/global.php'));
$adapter    = new Adapter($config->db->toArray());
$role       = new RoleService();
$permission = new PermissionService();
$install    = new Update($adapter, $role, $permission);
echo $install->database();
echo $install->config();
echo $install->permission();