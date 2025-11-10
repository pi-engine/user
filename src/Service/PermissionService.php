<?php

declare(strict_types=1);

namespace Pi\User\Service;

use Laminas\Permissions\Rbac\Rbac;
use Laminas\Permissions\Rbac\Role;
use Pi\Core\Service\CacheService;
use Pi\User\Model\Permission\AssertRolesMatches;
use Pi\User\Repository\PermissionRepositoryInterface;

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
        CacheService                  $cacheService
    ) {
        $this->permissionRepository = $permissionRepository;
        $this->cacheService         = $cacheService;
    }

    /**
     * @param $pageKey
     * @param $userRoles
     *
     * @return bool
     */
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

    /**
     * @param array $current
     * @param array $validate
     * @param array $items
     *
     * @return bool
     */
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

    /**
     * @param string $roleName
     * @param array  $permissions
     *
     * @return void
     */
    public function managePermissionByRole(string $roleName, array $permissions): void
    {
        // Delete old permissions
        $this->permissionRepository->deletePermissionRole(['role' => $roleName]);

        // Get all permission resources
        $resourceList = $this->permissionRepository->getPermissionResourceList(['key' => $permissions]);
        foreach ($resourceList as $resource) {
            $resource = $this->canonizeResource($resource);

            // Set role params
            $roleParams = [
                'resource' => $resource['key'],
                'section'  => $resource['section'],
                'module'   => $resource['module'],
                'role'     => $roleName,
                'key'      => sprintf('%s-%s', $roleName, $resource['key']),
            ];

            // Add a new role
            $this->permissionRepository->addPermissionRole($roleParams);
        }
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function managePermissionByResource(array $params): array
    {
        // Set resource params
        $resourceParams = [
            'title'   => $params['title'] ?? $params['key'],
            'section' => $params['section'] ?? 'api',
            'module'  => $params['module'] ?? 'custom',
            'key'     => $params['key'],
            'type'    => $params['type'] ?? 'custom',
        ];

        // Add resource
        $resource = $this->permissionRepository->addPermissionResource($resourceParams);
        $resource = $this->canonizeResource($resource);

        // Set page params
        $pageParams = [
            'title'    => $params['title'] ?? $params['key'],
            'section'  => $params['section'] ?? 'api',
            'module'   => $params['module'] ?? 'custom',
            'package'  => $params['package'] ?? null,
            'handler'  => $params['handler'] ?? null,
            'resource' => $resource['key'],
            'key'      => $params['key'],
        ];

        // Add page
        $this->permissionRepository->addPermissionPage($pageParams);

        // Add all roles to resource and page
        $roles = array_values(array_unique($params['roles']));
        foreach ($roles as $role) {
            // Set role params
            $roleParams = [
                'resource' => $resource['key'],
                'section'  => $params['section'] ?? 'api',
                'module'   => $params['module'] ?? 'custom',
                'role'     => $role,
                'key'      => sprintf('%s-%s', $role, $resource['key']),
            ];

            // Add role
            $this->permissionRepository->addPermissionRole($roleParams);
        }

        // Set result
        return [
            'message' => 'New resource and all related roles added successfully',
            'key'     => 'new-resource-and-all-related-roles-added-successfully',
        ];
    }

    /**
     * @param $params
     *
     * @return array
     */
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

    /**
     * @param $params
     *
     * @return void
     */
    public function cleanInstallerList($params): void
    {
        $this->permissionRepository->deletePermissionPage(['module' => $params['module']]);
        $this->permissionRepository->deletePermissionResource(['module' => $params['module']]);
        $this->permissionRepository->deletePermissionRole(['module' => $params['module']]);
    }

    /**
     * For admin aria
     *
     * @param $params
     *
     * @return array
     */
    public function getPermissionResource($params): array
    {
        $resource = $this->permissionRepository->getPermissionResource($params);
        return $this->canonizeResource($resource);
    }

    /**
     * For admin aria
     *
     * @param $params
     *
     * @return array
     */
    public function getPermissionResourceList($params): array
    {
        $limit  = $params['limit'] ?? 100;
        $page   = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['id DESC'];
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

    /**
     * For admin aria
     *
     * @param $params
     *
     * @return array
     */
    public function addPermissionResource($params): array
    {
        $values = [
            'title'   => $params['title'] ?? $params['key'],
            'section' => $params['section'],
            'module'  => $params['module'],
            'key'     => $params['key'],
            'type'    => $params['type'] ?? 'system',
        ];

        $resource = $this->permissionRepository->addPermissionResource($values);

        return $this->canonizeResource($resource);
    }

    /**
     * For account actions
     *
     * @param $params
     *
     * @return array
     */
    public function editPermissionResource($params): array
    {
        return [
            'message' => 'editPermissionResource',
            'params'  => $params,
        ];
    }

    /**
     * For account actions
     *
     * @param $params
     *
     * @return array
     */
    public function managePermissionResource($params): array
    {
        switch ($params['action']) {
            case 'addAccess':
                // Set delete params
                $deleteParams = [
                    'role'     => $params['role'],
                    'resource' => $params['resource'],
                ];

                // Delete role permission
                $this->permissionRepository->deletePermissionRole($deleteParams);

                // Set resource params
                $resourceParams = [
                    'key' => $params['resource'],
                ];

                // Get resource
                $resource = $this->getPermissionResource($resourceParams);

                // Set role params
                $roleParams = [
                    'role'     => $params['role'],
                    'resource' => $resource['key'],
                    'section'  => $resource['section'],
                    'module'   => $resource['module'],
                    'key'      => $resource['key'],
                ];

                // Add role
                $this->permissionRepository->addPermissionRole($roleParams);
                break;

            case 'removeAccess':
                // Set delete params
                $deleteParams = [
                    'role'     => $params['role'],
                    'resource' => $params['resource'],
                ];

                // Delete role permission
                $this->permissionRepository->deletePermissionRole($deleteParams);
                break;
        }

        return [
            'message' => 'Selected permission managed successfully !',
            'params'  => $params,
        ];
    }

    /**
     * For account actions
     *
     * @param $params
     *
     * @return array
     */
    public function deletePermissionResource($params): array
    {
        return [
            'message' => 'deletePermissionResource',
            'params'  => $params,
        ];
    }

    /**
     * For admin aria
     *
     * @param $params
     *
     * @return array
     */
    public function getPermissionPageList($params): array
    {
        $limit  = $params['limit'] ?? 100;
        $page   = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['id DESC'];
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

    /**
     * For admin aria
     *
     * @param $params
     *
     * @return array
     */
    public function addPermissionPage($params): array
    {
        $values = [
            'title'    => $params['title'] ?? $params['key'],
            'section'  => $params['section'],
            'module'   => $params['module'],
            'package'  => $params['package'],
            'handler'  => $params['handler'],
            'resource' => $params['resource'],
            'key'      => $params['key'],
        ];

        $page = $this->permissionRepository->addPermissionPage($values);

        return $this->canonizePage($page);
    }

    /**
     * For account actions
     *
     * @param $params
     *
     * @return array
     */
    public function editPermissionPage($params): array
    {
        return [
            'message' => 'editPermissionPage',
            'params'  => $params,
        ];
    }

    /**
     * For account actions
     *
     * @param $params
     *
     * @return array
     */
    public function deletePermissionPage($params): array
    {
        return [
            'message' => 'deletePermissionPage',
            'params'  => $params,
        ];
    }

    /**
     * For admin aria
     *
     * @param $params
     *
     * @return array
     */
    public function getPermissionRoleList($params): array
    {
        $limit  = $params['limit'] ?? 100;
        $page   = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['id DESC'];
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

    /**
     * For admin aria
     *
     * @param $params
     *
     * @return array
     */
    public function addPermissionRole($params): array
    {
        $values = [
            'resource' => $params['resource'],
            'section'  => $params['section'],
            'module'   => $params['module'],
            'role'     => $params['role'],
            'key'      => $params['key'],
        ];

        $role = $this->permissionRepository->addPermissionRole($values);

        return $this->canonizeRole($role);
    }

    /**
     * For account actions
     *
     * @param $params
     *
     * @return array
     */
    public function editPermissionRole($params): array
    {
        return [
            'message' => 'editPermissionRole',
            'params'  => $params,
        ];
    }

    /**
     * For account actions
     *
     * @param $params
     *
     * @return array
     */
    public function deletePermissionRole($params): array
    {
        return [
            'message' => 'deletePermissionRole',
            'params'  => $params,
        ];
    }

    /**
     * For account actions
     *
     * @param $params
     *
     * @return array
     */
    public function getPermissionRole($params): array
    {
        $roles    = $this->permissionRepository->getPermissionRoleList($params);
        $roleList = [];
        foreach ($roles as $role) {
            $roleList[$role->getResource()] = $role->getResource();
        }

        return array_values($roleList);
    }

    /**
     * Get resource and role data of permission by page, for check access
     *
     * @param $pageKey
     *
     * @return array
     */
    protected function getPermission($pageKey): array
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

    /**
     * @param $resource
     *
     * @return array
     */
    public function canonizeResource($resource): array
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
                'key'     => $resource->getKey(),
                'type'    => $resource->getType(),
            ];
        }

        return $resource;
    }

    /**
     * @param $page
     *
     * @return array
     */
    public function canonizePage($page): array
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
                'key'         => $page->getKey(),
                'cache_type'  => $page->getCacheType(),
                'cache_ttl'   => $page->getCacheTtl(),
                'cache_level' => $page->getCacheLevel(),
            ];
        }

        return $page;
    }

    /**
     * @param $role
     *
     * @return array
     */
    public function canonizeRole($role): array
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
                'key'      => $role->getKey(),
            ];
        }

        return $role;
    }
}