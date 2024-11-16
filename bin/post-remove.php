<?php

use Laminas\Config\Config;
use Laminas\Db\Adapter\Adapter;
use Pi\Core\Installer\Remove;
use Pi\User\Service\PermissionService;
use Pi\User\Service\RoleService;

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
$install    = new Remove($adapter, $role, $permission);
echo $install->database();
echo $install->config();
echo $install->permission();