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

    public function database($version = ''): bool
    {
        return true;
    }

    public function config($file = ''): bool
    {
        return true;
    }
}