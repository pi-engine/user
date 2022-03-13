<?php

namespace User\Repository;

use Laminas\Db\ResultSet\HydratingResultSet;
use User\Model\Role;
use User\Model\RoleAccount;

interface RoleRepositoryInterface
{
    public function getRoleList($params = []): HydratingResultSet;

    public function getUserRoleList($params = []): HydratingResultSet;

    public function getRole(array $params = []): Role;

    public function getUserRole($userId, $section = ''): HydratingResultSet;

    public function addRole(array $params = []): Role;

    public function addUserRole(array $params = []): RoleAccount;

    public function updateRole(string $roleName, array $params = []): void;

    public function deleteUserRole(int $userId, string $roleName) : void;
}