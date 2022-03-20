<?php

namespace User\Repository;

use Laminas\Db\ResultSet\HydratingResultSet;
use User\Model\Role\Role;

interface RoleRepositoryInterface
{
    public function getRoleList($params = []): HydratingResultSet;

    public function getRole(array $params = []): Role;

    public function addRole(array $params = []): Role;

    public function updateRole(string $roleName, array $params = []): void;

    public function deleteRole(string $roleName): void;

    public function getUserRole($userId, $section = ''): HydratingResultSet;

    public function addUserRole(int $userId, string $roleName, string $section = 'api'): void;

    public function deleteUserRole(int $userId, string $roleName) : void;
}