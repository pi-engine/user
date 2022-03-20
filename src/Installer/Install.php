<?php

namespace User\Installer;

use Laminas\Db\Adapter\AdapterInterface;

class Install implements InstallerInterface
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
        // Set and check
        $sqlFile = realpath(__DIR__ . '/../../data/schema.sql');
        if (!file_exists($sqlFile)) {
            return 'Error to find or read schema.sql';

        }

        $sql       = file_get_contents($sqlFile);
        $statement = $this->db->createStatement($sql);
        $statement->execute();
        return 'User module database install successfully !';
    }

    public function config($file = ''): string
    {
        return true;
    }
}