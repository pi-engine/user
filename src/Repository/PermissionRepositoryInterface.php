<?php

namespace User\Repository;

use Laminas\Db\ResultSet\HydratingResultSet;
use User\Model\Permission\Page;
use User\Model\Permission\Resource;
use User\Model\Permission\Role;

interface PermissionRepositoryInterface
{
    public function getPermissionResourceList(array $params = []): HydratingResultSet;

    public function getPermissionResource(array $params = []): Resource;

    public function addPermissionResource(array $params = []): Resource;

    public function updatePermissionResource(string $resourceName, array $params = []): void;

    public function deletePermissionResource(string $roleName): void;

    public function getPermissionRoleList(array $params = []): HydratingResultSet;

    public function getPermissionRole(array $params = []): Role;

    public function addPermissionRole(array $params = []): Role;

    public function updatePermissionRole(string $roleName, array $params = []): void;

    public function deletePermissionRole(array $params = []): void;

    public function getPermissionPageList(array $params = []): HydratingResultSet;

    public function getPermissionPage(array $params = []): Page;

    public function addPermissionPage(array $params = []): Page;

    public function updatePermissionPage(string $pageName, array $params = []): void;

    public function deletePermissionPage(array $params = []): void;
}