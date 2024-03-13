<?php

namespace User\Repository;

use Laminas\Db\ResultSet\HydratingResultSet;
use User\Model\Role\Resource;

interface RoleRepositoryInterface
{
    public function getRoleResourceList($params = []): HydratingResultSet;

    public function getRoleResource(array $params = []): Resource;

    public function addRoleResource(array $params = []): Resource;

    public function updateRoleResource(string $roleName, array $params = []): void;

    public function deleteRoleResource(string $roleName): void;

    public function getRoleAccount($userId, $section = ''): HydratingResultSet;

    public function addRoleAccount(int $userId, string $roleName, string $section = 'api'): void;

    public function deleteRoleAccount(int $userId, string $roleName): void;

    public function deleteAllRoleAccount(int $userId, string $section = 'api'): void;
}