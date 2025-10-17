<?php

declare(strict_types=1);

namespace Pi\User\Repository;

use Laminas\Db\ResultSet\HydratingResultSet;
use Pi\User\Model\Permission\Page;
use Pi\User\Model\Permission\Resource;
use Pi\User\Model\Permission\Role;

interface PermissionRepositoryInterface
{
    public function getPermissionResourceList(array $params = []): HydratingResultSet;

    public function getPermissionResource(array $params = []): Resource;

    public function addPermissionResource(array $params = []): Resource;

    public function updatePermissionResource(string $resourceKey, array $params = []): void;

    public function deletePermissionResource(array $params = []): void;

    public function getPermissionResourceCount($params = []): int;

    public function getPermissionRoleList(array $params = []): HydratingResultSet;

    public function getPermissionRole(array $params = []): Role;

    public function addPermissionRole(array $params = []): Role;

    public function updatePermissionRole(string $roleKey, array $params = []): void;

    public function deletePermissionRole(array $params = []): void;

    public function getPermissionRoleCount($params = []): int;

    public function getPermissionPageList(array $params = []): HydratingResultSet;

    public function getPermissionPage(array $params = []): Page;

    public function addPermissionPage(array $params = []): Page;

    public function updatePermissionPage(string $pageKey, array $params = []): void;

    public function deletePermissionPage(array $params = []): void;

    public function getPermissionPageCount($params = []): int;
}