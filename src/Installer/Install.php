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

    public function database($version = ''): bool
    {
        // Set and check
        $sqlFile = realpath(__DIR__ . '/../../data/schema.sql');
        if (!file_exists($sqlFile)) {
            return false;
        }

        // read query
        $sql = file_get_contents($sqlFile);
        $statement = $this->db->createStatement($sql);
        $statement->execute();
        return true;
    }

    public function config($file = ''): bool
    {
        return true;
    }
}