<?php

namespace User\Installer;

use User\Service\InstallerService;

class Remove implements InstallerInterface
{
    /* @var InstallerService */
    protected InstallerService $installerService;

    public function __construct(InstallerService $installerService)
    {
        $this->installerService = $installerService;
    }

    public function database($sqlFile): void
    {}

    public function config($configFile): void
    {}

    public function permission($permissionFile): void
    {}
}