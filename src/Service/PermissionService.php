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

    public function checkPermissionBefore($pageName, $userRoles): bool
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

    public function checkPermissionAfter(array $current = [], array $validate = [], array $items = []): bool
    {
        foreach ($items as $item) {
            // Check item exist or not
            if (!isset($current[$item]) || empty($current[$item]) || !isset($validate[$item]) || empty($validate[$item])) {
                return false;
            }

            // Check
            if (is_array($validate[$item])) {
                if (!in_array($current[$item], $validate[$item])) {
                    return false;
                }
            } elseif (is_int($validate[$item])) {
                if ($current[$item] !== $validate[$item]) {
                    return false;
                }
            } else {
                if ($current[$item] != $validate[$item]) {
                    return false;
                }
            }
        }

        return true;
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
            'resource'    => $resource,
            'systemRoles' => $systemRoles,
        ];
    }

    public function getInstallerList($params)
    {
        $result = [
            'page_list'     => [],
            'resource_list' => [],
            'role_list'     => [],
        ];

        // Get list
        $rowSet = $this->permissionRepository->getPermissionPageList(['module' => $params['module']]);
        foreach ($rowSet as $row) {
            $result['page_list'][$row->getName()] = $this->canonizePage($row);
        }

        // Get list
        $rowSet = $this->permissionRepository->getPermissionResourceList(['module' => $params['module']]);
        foreach ($rowSet as $row) {
            $result['resource_list'][$row->getName()] = $this->canonizeResource($row);
        }

        $rowSet = $this->permissionRepository->getPermissionRoleList(['module' => $params['module']]);
        foreach ($rowSet as $row) {
            $result['role_list'][$row->getName()] = $this->canonizeRole($row);
        }

        return $result;
    }

    public function addPermissionResource($params)
    {
        $values = [
            'title'    => $params['title'] ?? $params['name'],
            'section' => $params['section'],
            'module'  => $params['module'],
            'name'    => $params['name'],
            'type'    => $params['type'] ?? 'system',
        ];

        $resource = $this->permissionRepository->addPermissionResource($values);

        return $this->canonizeResource($resource);
    }

    public function addPermissionPage($params)
    {
        $values = [
            'title'    => $params['title'] ?? $params['name'],
            'section'  => $params['section'],
            'module'   => $params['module'],
            'package'  => $params['package'],
            'handler'  => $params['handler'],
            'resource' => $params['resource'],
            'name'     => $params['name'],
        ];

        $page = $this->permissionRepository->addPermissionPage($values);

        return $this->canonizePage($page);
    }

    public function addPermissionRole($params)
    {
        $values = [
            'resource' => $params['resource'],
            'section'  => $params['section'],
            'module'   => $params['module'],
            'role'     => $params['role'],
            'name'     => $params['name'],
        ];

        $role = $this->permissionRepository->addPermissionRole($values);

        return $this->canonizeRole($role);
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