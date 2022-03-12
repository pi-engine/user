<?php

namespace User\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\HydratorInterface;
use User\Model\Role;
use User\Model\RoleAccount;

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

    /**
     * @var RoleAccount
     */
    private RoleAccount $roleAccountPrototype;


    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        Role $rolePrototype,
        RoleAccount $roleAccountPrototype
    ) {
        $this->db                   = $db;
        $this->hydrator             = $hydrator;
        $this->rolePrototype        = $rolePrototype;
        $this->roleAccountPrototype = $roleAccountPrototype;
    }
}