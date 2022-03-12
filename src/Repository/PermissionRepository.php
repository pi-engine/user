<?php

namespace User\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\HydratorInterface;
use User\Model\PermissionResource;
use User\Model\PermissionRole;

class PermissionRepository implements PermissionRepositoryInterface
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
     * @var PermissionResource
     */
    private PermissionResource $permissionResourcePrototype;

    /**
     * @var PermissionRole
     */
    private PermissionRole $permissionRolePrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        PermissionResource $permissionResourcePrototype,
        PermissionRole $permissionRolePrototype
    ) {
        $this->db                          = $db;
        $this->hydrator                    = $hydrator;
        $this->permissionResourcePrototype = $permissionResourcePrototype;
        $this->permissionRolePrototype     = $permissionRolePrototype;
    }
}