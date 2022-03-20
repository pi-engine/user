<?php

namespace User\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\HydratorInterface;
use User\Model\Permission\Page;
use User\Model\Permission\Resource;
use User\Model\Permission\Role;

class PermissionRepository implements PermissionRepositoryInterface
{
    /**
     * Permission resource Table name
     *
     * @var string
     */
    private string $tableresource = 'permission_resource';

    /**
     * Permission rule Table name
     *
     * @var string
     */
    private string $tablePermissionRule = 'permission_rule';

    /**
     * Permission page Table name
     *
     * @var string
     */
    private string $tablepage = 'permission_page';

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $db;

    /**
     * @var HydratorInterface
     */
    private HydratorInterface $hydrator;

    /**
     * @var Resource
     */
    private Resource $resourcePrototype;

    /**
     * @var Role
     */
    private Role $rolePrototype;

    /**
     * @var Page
     */
    private Page $pagePrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        Resource $resourcePrototype,
        Role $rolePrototype,
        Page $pagePrototype
    ) {
        $this->db                = $db;
        $this->hydrator          = $hydrator;
        $this->resourcePrototype = $resourcePrototype;
        $this->rolePrototype     = $rolePrototype;
        $this->pagePrototype     = $pagePrototype;
    }
}