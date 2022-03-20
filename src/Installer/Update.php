<?php

namespace User\Installer;

use Laminas\Db\Adapter\AdapterInterface;

class Update implements InstallerInterface
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
        return 'User module database update successfully !';
    }

    public function config($file = ''): string
    {
        return true;
    }
}