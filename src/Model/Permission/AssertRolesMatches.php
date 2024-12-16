<?php

declare(strict_types=1);

namespace Pi\User\Model\Permission;

use Laminas\Permissions\Rbac\AssertionInterface;
use Laminas\Permissions\Rbac\Rbac;
use Laminas\Permissions\Rbac\RoleInterface;

class AssertRolesMatches implements AssertionInterface
{
    protected array $userRoles;
    protected array $systemRoles;
    protected array $resource;

    public function __construct($userRoles, $systemRoles, $resource)
    {
        $this->userRoles   = $userRoles;
        $this->systemRoles = $systemRoles;
        $this->resource    = $resource;
    }

    public function assert(Rbac $rbac, RoleInterface $role = null, string $permission = null): bool
    {
        if (empty($this->userRoles) || empty($this->systemRoles) || empty($this->resource)) {
            return false;
        }

        return in_array($role->getName(), $this->userRoles);
    }
}