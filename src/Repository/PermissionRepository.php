<?php

namespace User\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\HydratorInterface;
use User\Model\PermissionPage;
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

    /**
     * @var PermissionPage
     */
    private PermissionPage $permissionPagePrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        PermissionResource $permissionResourcePrototype,
        PermissionRole $permissionRolePrototype,
        PermissionPage $permissionPagePrototype
    ) {
        $this->db                          = $db;
        $this->hydrator                    = $hydrator;
        $this->permissionResourcePrototype = $permissionResourcePrototype;
        $this->permissionRolePrototype     = $permissionRolePrototype;
        $this->permissionPagePrototype     = $permissionPagePrototype;
    }
}