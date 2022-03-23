<?php

namespace User\Service;

use Laminas\Permissions\Rbac\Rbac;
use Laminas\Permissions\Rbac\Role;
use User\Model\Permission\AssertRolesMatches;
use User\Repository\PermissionRepositoryInterface;

class PermissionService implements ServiceInterface
{
    /* @var PermissionRepositoryInterface */
    protected PermissionRepositoryInterface $permissionRepository;

    /* @var CacheService */
    protected CacheService $cacheService;

    /**
     * @param PermissionRepositoryInterface $permissionRepository
     * @param CacheService                  $cacheService
     */
    public function __construct(
        PermissionRepositoryInterface $permissionRepository,
        CacheService $cacheService
    ) {
        $this->permissionRepository = $permissionRepository;
        $this->cacheService         = $cacheService;
    }

    public function checkPermission($pageName, $userRoles): bool
    {
        $permission = $this->getPermission($pageName);

        $rbac      = new Rbac();
        $assertion = new AssertRolesMatches($userRoles, $permission['systemRoles'], $permission['resource']);
        foreach ($permission['systemRoles'] as $systemRole) {
            $result = $assertion->assert($rbac, new Role($systemRole['role']), $permission['resource']['name']);
            if ($result) {
                return true;
            }
        }

        return false;
    }

    public function getPermission($pageName): array
    {
        $page = $this->permissionRepository->getPermissionPage(['name' => $pageName]);
        $page = $this->canonizePage($page);

        $resource = $this->permissionRepository->getPermissionResource(['name' => $page['resource']]);
        $resource = $this->canonizeResource($resource);

        $roles       = $this->permissionRepository->getPermissionRoleList(['resource' => $page['resource']]);
        $systemRoles = [];
        foreach ($roles as $role) {
            $systemRoles[] = $this->canonizeRole($role);
        }

        return [
            'resource' => $resource,
            'systemRoles' => $systemRoles
        ];
    }

    public function canonizePage($page)
    {
        if (empty($page)) {
            return [];
        }

        if (is_object($page)) {
            $page = [
                'id'          => $page->getId(),
                'title'       => $page->getTitle(),
                'section'     => $page->getSection(),
                'module'      => $page->getModule(),
                'package'     => $page->getPackage(),
                'handler'     => $page->getHandler(),
                'resource'    => $page->getResource(),
                'name'        => $page->getName(),
                'cache_type'  => $page->getCacheType(),
                'cache_ttl'   => $page->getCacheTtl(),
                'cache_level' => $page->getCacheLevel(),
            ];
        }

        return $page;
    }

    public function canonizeResource($resource)
    {
        if (empty($resource)) {
            return [];
        }

        if (is_object($resource)) {
            $resource = [
                'id'      => $resource->getId(),
                'title'   => $resource->getTitle(),
                'section' => $resource->getSection(),
                'module'  => $resource->getModule(),
                'name'    => $resource->getName(),
                'type'    => $resource->getType(),
            ];
        }

        return $resource;
    }

    public function canonizeRole($role)
    {
        if (empty($role)) {
            return [];
        }

        if (is_object($role)) {
            $role = [
                'id'       => $role->getId(),
                'resource' => $role->getResource(),
                'section'  => $role->getSection(),
                'module'   => $role->getModule(),
                'role'     => $role->getRole(),
                'name'     => $role->getName(),
            ];
        }

        return $role;
    }
}