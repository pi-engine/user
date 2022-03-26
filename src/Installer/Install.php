<?php

namespace User\Installer;

use User\Service\InstallerService;

class Install implements InstallerInterface
{
    /* @var InstallerService */
    protected InstallerService $installerService;

    public function __construct(InstallerService $installerService)
    {
        $this->installerService = $installerService;
    }

    public function database($sqlFile): void
    {
        // Set and check
        if (!file_exists($sqlFile)) {
            echo 'Error to find or read sql file';
            exit();
        }

        /* $sql       = file_get_contents($sqlFile);
        $statement = $this->db->createStatement($sql);
        $statement->execute(); */
        echo 'User module database install successfully !';
    }

    public function config($configFile): void
    {
        // Set and check
        if (!file_exists($configFile)) {
            echo 'Error to find or read config file';
            exit();
        }

        echo '234';
    }

    public function permission($permissionFile): void
    {
        // Set and check
        if (!file_exists($permissionFile)) {
            echo 'Error to find or read permission file';
            exit();
        }

        $permissionConfig = include $permissionFile;

        $this->installerService->installPermission($permissionConfig);

        echo 'User module permission install successfully !';
    }
}