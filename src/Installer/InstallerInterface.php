<?php

namespace User\Installer;

interface InstallerInterface
{
    public function database($version = ''): bool;

    public function config($file = ''): bool;
}