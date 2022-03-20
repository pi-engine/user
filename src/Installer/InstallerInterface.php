<?php

namespace User\Installer;

interface InstallerInterface
{
    public function database($version = ''): string;

    public function config($file = ''): string;
}