<?php

namespace User\Installer;

interface InstallerInterface
{
    public function database($sqlFile): void;

    public function config($configFile): void;

    public function permission($permissionFile): void;
}