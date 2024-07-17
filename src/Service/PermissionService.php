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

    public function checkPermissionBefore($pageKey, $userRoles): bool
    {
        $permission = $this->getPermission($pageKey);

        $rbac      = new Rbac();
        $assertion = new AssertRolesMatches($userRoles, $permission['systemRoles'], $permission['resource']);
        foreach ($permission['systemRoles'] as $systemRole) {
            $result = $assertion->assert($rbac, new Role($systemRole['role']), $permission['resource']['key']);
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

    public function getPermission($pageKey): array
    {
        $page = $this->permissionRepository->getPermissionPage(['key' => $pageKey]);
        $page = $this->canonizePage($page);

        $resource = $this->permissionRepository->getPermissionResource(['key' => $page['resource']]);
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

    public function getInstallerList($params): array
    {
        $result = [
            'page_list'     => [],
            'resource_list' => [],
            'role_list'     => [],
        ];

        // Get list
        $rowSet = $this->permissionRepository->getPermissionPageList(['module' => $params['module']]);
        foreach ($rowSet as $row) {
            $result['page_list'][$row->getKey()] = $this->canonizePage($row);
        }

        // Get list
        $rowSet = $this->permissionRepository->getPermissionResourceList(['module' => $params['module']]);
        foreach ($rowSet as $row) {
            $result['resource_list'][$row->getKey()] = $this->canonizeResource($row);
        }

        $rowSet = $this->permissionRepository->getPermissionRoleList(['module' => $params['module']]);
        foreach ($rowSet as $row) {
            $result['role_list'][$row->getKey()] = $this->canonizeRole($row);
        }

        return $result;
    }

    public function addPermissionResource($params)
    {
        $values = [
            'title'   => $params['title'] ?? $params['key'],
            'section' => $params['section'],
            'module'  => $params['module'],
            'key'    => $params['key'],
            'type'    => $params['type'] ?? 'system',
        ];

        $resource = $this->permissionRepository->addPermissionResource($values);

        return $this->canonizeResource($resource);
    }

    public function addPermissionPage($params)
    {
        $values = [
            'title'    => $params['title'] ?? $params['key'],
            'section'  => $params['section'],
            'module'   => $params['module'],
            'package'  => $params['package'],
            'handler'  => $params['handler'],
            'resource' => $params['resource'],
            'key'     => $params['key'],
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
            'key'     => $params['key'],
        ];

        $role = $this->permissionRepository->addPermissionRole($values);

        return $this->canonizeRole($role);
    }

    public function getPermissionRole($params): array
    {
        $roles    = $this->permissionRepository->getPermissionRoleList($params);
        $roleList = [];
        foreach ($roles as $role) {
            $roleList[$role->getResource()] = $role->getResource();
        }

        return array_values($roleList);
    }

    public function getResourceList($params): array
    {
        $limit = $params['limit'] ?? 100;
        $page  = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ((int)$page - 1) * (int)$limit;

        // Set params
        $listParams = [
            'page'   => (int)$page,
            'limit'  => (int)$limit,
            'order'  => $order,
            'offset' => $offset,
        ];

        if (isset($params['title']) && !empty($params['title'])) {
            $listParams['title'] = $params['title'];
        }
        if (isset($params['key']) && !empty($params['key'])) {
            $listParams['key'] = $params['key'];
        }
        if (isset($params['section']) && !empty($params['section'])) {
            $listParams['section'] = $params['section'];
        }
        if (isset($params['module']) && !empty($params['module'])) {
            $listParams['module'] = $params['module'];
        }
        if (isset($params['type']) && !empty($params['type'])) {
            $listParams['type'] = $params['type'];
        }

        // Get list
        $list   = [];
        $rowSet = $this->permissionRepository->getPermissionResourceList($listParams);
        foreach ($rowSet as $row) {
            $list[] = $this->canonizeResource($row);
        }

        // Get count
        $count = $this->permissionRepository->getPermissionResourceCount($listParams);

        return [
            'list'      => $list,
            'paginator' => [
                'count' => $count,
                'limit' => $limit,
                'page'  => $page,
            ],
        ];
    }

    public function getPageList($params): array
    {
        $limit = $params['limit'] ?? 100;
        $page  = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ((int)$page - 1) * (int)$limit;

        // Set params
        $listParams = [
            'page'   => (int)$page,
            'limit'  => (int)$limit,
            'order'  => $order,
            'offset' => $offset,
        ];

        if (isset($params['title']) && !empty($params['title'])) {
            $listParams['title'] = $params['title'];
        }
        if (isset($params['key']) && !empty($params['key'])) {
            $listParams['key'] = $params['key'];
        }
        if (isset($params['resource']) && !empty($params['resource'])) {
            $listParams['resource'] = $params['resource'];
        }
        if (isset($params['section']) && !empty($params['section'])) {
            $listParams['section'] = $params['section'];
        }
        if (isset($params['module']) && !empty($params['module'])) {
            $listParams['module'] = $params['module'];
        }
        if (isset($params['package']) && !empty($params['package'])) {
            $listParams['package'] = $params['package'];
        }
        if (isset($params['handler']) && !empty($params['handler'])) {
            $listParams['handler'] = $params['handler'];
        }

        // Get list
        $list   = [];
        $rowSet = $this->permissionRepository->getPermissionPageList($listParams);
        foreach ($rowSet as $row) {
            $list[] = $this->canonizePage($row);
        }

        // Get count
        $count = $this->permissionRepository->getPermissionPageCount($listParams);

        return [
            'list'      => $list,
            'paginator' => [
                'count' => $count,
                'limit' => $limit,
                'page'  => $page,
            ],
        ];
    }

    public function getRoleList($params): array
    {
        $limit = $params['limit'] ?? 100;
        $page  = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ((int)$page - 1) * (int)$limit;

        // Set params
        $listParams = [
            'page'   => (int)$page,
            'limit'  => (int)$limit,
            'order'  => $order,
            'offset' => $offset,
        ];

        if (isset($params['key']) && !empty($params['key'])) {
            $listParams['key'] = $params['key'];
        }
        if (isset($params['resource']) && !empty($params['resource'])) {
            $listParams['resource'] = $params['resource'];
        }
        if (isset($params['section']) && !empty($params['section'])) {
            $listParams['section'] = $params['section'];
        }
        if (isset($params['module']) && !empty($params['module'])) {
            $listParams['module'] = $params['module'];
        }
        if (isset($params['role']) && !empty($params['role'])) {
            $listParams['role'] = $params['role'];
        }

        // Get list
        $list   = [];
        $rowSet = $this->permissionRepository->getPermissionRoleList($listParams);
        foreach ($rowSet as $row) {
            $list[] = $this->canonizeRole($row);
        }

        // Get count
        $count = $this->permissionRepository->getPermissionRoleCount($listParams);

        return [
            'list'      => $list,
            'paginator' => [
                'count' => $count,
                'limit' => $limit,
                'page'  => $page,
            ],
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
                'key'        => $page->getKey(),
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
                'key'    => $resource->getKey(),
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
                'key'     => $role->getKey(),
            ];
        }

        return $role;
    }
}