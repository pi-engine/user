<?php

namespace User\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\HydratorInterface;
use User\Model\Role;

class RoleRepository implements RoleRepositoryInterface
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $db;

    /**
     * @var HydratorInterface
     */
    private HydratorInterface $hydrator;

    /**
     * @var Role
     */
    private Role $rolePrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        Role $rolePrototype
    ) {
        $this->db                   = $db;
        $this->hydrator             = $hydrator;
        $this->rolePrototype = $rolePrototype;
    }
}