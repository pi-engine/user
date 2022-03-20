<?php

namespace User\Installer;

use Laminas\Db\Adapter\AdapterInterface;

class Remove implements InstallerInterface
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $db;

    public function __construct(
        AdapterInterface $db
    ) {
        $this->db = $db;
    }

    public function database($version = ''): string
    {
        return true;
    }

    public function config($file = ''): string
    {
        return true;
    }
}