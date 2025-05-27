<?php

declare(strict_types=1);

namespace Pi\User\Service;

use Pi\Core\Service\CacheService;
use Pi\Core\Service\UtilityService;
use Pi\User\Repository\RoleRepositoryInterface;
use function in_array;

class RoleService implements ServiceInterface
{
    /* @var RoleRepositoryInterface */
    protected RoleRepositoryInterface $roleRepository;

    /** @var PermissionService */
    protected PermissionService $permissionService;

    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var HistoryService */
    protected HistoryService $historyService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    protected array $defaultRoles
        = [
            [
                'name'    => 'member',
                'section' => 'api',
            ],
        ];

    //protected array $sectionList = ['api', 'admin'];

    /* @var array */
    protected array $config;

    /**
     * @param RoleRepositoryInterface $roleRepository
     * @param CacheService            $cacheService
     * @param HistoryService          $historyService
     */
    public function __construct(
        RoleRepositoryInterface $roleRepository,
        PermissionService       $permissionService,
        CacheService            $cacheService,
        HistoryService          $historyService,
        UtilityService          $utilityService,
                                $config
    ) {
        $this->roleRepository    = $roleRepository;
        $this->permissionService = $permissionService;
        $this->cacheService      = $cacheService;
        $this->historyService    = $historyService;
        $this->utilityService    = $utilityService;
        $this->config            = $config;
    }

    public function getRoleList($section = 'api'): array
    {
        switch ($section) {
            case 'api':
                return $this->getApiRoleList();
                break;

            case 'admin':
                return $this->getAdminRoleList();
                break;

            default:
            case 'all':
            case '':
                return $this->getAllRoleList();
                break;
        }
    }

    public function getApiRoleList(): array
    {
        $roles = $this->cacheService->getItem('roles_api');
        if (empty($roles)) {
            $listParams = [
                'section' => 'api',
                'status'  => 1,
            ];

            // Get list
            $list   = [];
            $rowSet = $this->roleRepository->getRoleResourceList($listParams);
            foreach ($rowSet as $row) {
                $list[] = $row->getName();
            }

            $roles = $this->cacheService->setItem('roles_api', $list);
        }

        return $roles;
    }

    public function getAdminRoleList(): array
    {
        $roles = $this->cacheService->getItem('roles_admin');
        if (empty($roles)) {
            $listParams = [
                'section' => 'admin',
                'status'  => 1,
            ];

            // Get list
            $list   = [];
            $rowSet = $this->roleRepository->getRoleResourceList($listParams);
            foreach ($rowSet as $row) {
                $list[] = $row->getName();
            }

            $roles = $this->cacheService->setItem('roles_admin', $list);
        }

        return $roles;
    }

    public function getAllRoleList(): array
    {
        $roles = $this->cacheService->getItem('roles_light');
        if (empty($roles)) {
            $listParams = [
                'status' => 1,
            ];

            // Get list
            $list   = [];
            $rowSet = $this->roleRepository->getRoleResourceList($listParams);
            foreach ($rowSet as $row) {
                $list[] = $row->getName();
            }

            $roles = $this->cacheService->setItem('roles_light', $list);
        }

        return $roles;
    }

    public function getRoleResourceListByAdmin(): array
    {
        $list   = [];
        $rowSet = $this->roleRepository->getRoleResourceList();
        foreach ($rowSet as $row) {
            $list[] = $this->canonizeRole($row);
        }
        return $list;
    }

    public function getRoleResourceList($section = ''): array
    {
        $roles = $this->cacheService->getItem('role_list');
        if (empty($roles)) {
            $listParams = [
                'section' => $section,
                'status'  => 1,
            ];

            // Get list
            $list   = [];
            $rowSet = $this->roleRepository->getRoleResourceList($listParams);
            foreach ($rowSet as $row) {
                $list[] = $this->canonizeRole($row);
            }

            $roles = $this->cacheService->setItem('role_list', $list);
        }

        return $roles;
    }

    public function getDefaultRolesLight(): array
    {
        // Check and clean all up default roles from list
        $defaultRoleList = [];
        foreach ($this->defaultRoles as $defaultRole) {
            $defaultRoleList[$defaultRole['name']] = $defaultRole['name'];
        }

        return array_values($defaultRoleList);
    }

    public function getRoleResource(array $params): array
    {
        $role = $this->roleRepository->getRoleResource($params);
        return $this->canonizeRole($role);
    }

    public function addRoleResource(object|array|null $params, mixed $operator)
    {
        // Set role params
        $addParams = [
            'name'    => $this->utilityService->slug($params['name']),
            'title'   => $params['title'],
            'section' => $params['section'] ?? 'api',
            'status'  => $params['status'] ?? 1,
        ];

        // Add a role
        $role = $this->roleRepository->addRoleResource($addParams);
        $role = $this->canonizeRole($role);

        // Clean cache
        $this->resetRoleListInCache();

        // Sync permissions
        if (isset($params['permissions']) && !empty($params['permissions'])) {
            $this->permissionService->managePermissionByRole($role['name'], $params['permissions']);
        }

        return $role;
    }

    public function addDefaultRoles($account, $operator = []): void
    {
        $roleList = $this->config['default_roles'] ?? $this->defaultRoles;
        foreach ($roleList as $role) {
            $this->roleRepository->addRoleAccount((int)$account['id'], $role['name'], $role['section']);
        }

        // Save log
        $this->historyService->logger('addDefaultRoles', ['request' => $roleList, 'account' => $account, 'operator' => $operator]);
    }

    public function addRoleAccount(array $account, string $roleName, string $section = 'api', $operator = []): void
    {
        $roleList     = $this->getRoleList($section);
        $userRoleList = $this->getRoleAccount((int)$account['id']);

        // Check role and add
        if (in_array($roleName, $roleList) && !in_array($roleName, $userRoleList)) {
            $this->roleRepository->addRoleAccount((int)$account['id'], $roleName, $section);
        }

        // Update cache
        $this->cacheService->updateUserRoles((int)$account['id'], [$roleName], $section);

        // Save log
        $this->historyService->logger(
            'addRoleAccount',
            ['request' => ['role' => $roleName, 'section' => $section], 'account' => $account, 'operator' => $operator]
        );
    }

    public function getRoleAccount($userId): array
    {
        $list   = [];
        $rowSet = $this->roleRepository->getRoleAccount($userId);
        foreach ($rowSet as $row) {
            $list[] = $row->getRoleResource();
        }

        return $list;
    }

    public function getRoleAccountList($userIdList, $section = 'full'): array
    {
        $list   = [];
        $rowSet = $this->roleRepository->getRoleAccount($userIdList);
        foreach ($rowSet as $row) {
            $role = $this->canonizeUserRole($row);

            switch ($section) {
                case 'admin':
                    if ($role['section'] == 'admin') {
                        $list[$role['user_id']][] = $role;
                    }
                    break;

                case 'api':
                    if ($role['section'] == 'api') {
                        $list[$role['user_id']][] = $role;
                    }
                    break;

                case 'full':
                    $list[$role['user_id']][$role['section']][] = $role;
                    break;
            }
        }

        return $list;
    }

    public function deleteRoleAccount($account, string $roleName, string $section = 'api', $operator = []): void
    {
        $roleList = $this->getRoleList($section);
        if (in_array($roleName, $roleList)) {
            $this->roleRepository->deleteRoleAccount((int)$account['id'], $roleName);
        }

        // Save log
        $this->historyService->logger('deleteRoleAccount', ['request' => ['role' => $roleName], 'account' => $account, 'operator' => $operator]);
    }

    public function deleteAllRoleAccount($account, string $section = 'api', $operator = []): void
    {
        $this->roleRepository->deleteAllRoleAccount((int)$account['id'], $section);

        // Save log
        $this->historyService->logger('deleteAllRoleAccount', ['request' => ['role' => null], 'account' => $account, 'operator' => $operator]);
    }

    public function updateAccountRoles($roles, $account, string $section = 'api', $operator = []): void
    {
        // Set role list
        $roleList    = $this->getRoleList($section);
        $defaultList = $this->getDefaultRolesLight();
        $userRoles   = array_unique(array_merge($roles, $defaultList));

        // Delete all roles and add defaults
        $this->deleteAllRoleAccount($account, $section, $operator);

        // Check and add new roles
        foreach ($userRoles as $roleName) {
            if (in_array($roleName, $roleList)) {
                $this->roleRepository->addRoleAccount((int)$account['id'], $roleName, $section);
            }
        }

        // Save log
        $this->historyService->logger('updateRoleAccount', ['request' => ['role' => $roles], 'account' => $account, 'operator' => $operator]);
    }

    public function updateAccountRolesByAdmin($roles, $account, $operator = []): void
    {
        // Get role list
        $apiRoleList   = $this->getApiRoleList();
        $adminRoleList = $this->getAdminRoleList();

        // Set user role list
        $defaultList = $this->getDefaultRolesLight();
        $userRoles   = array_unique(array_merge($roles, $defaultList));

        // Delete all roles and add defaults
        $this->deleteAllRoleAccount($account, 'all', $operator);

        // Check and add new api roles
        foreach ($userRoles as $roleName) {
            if (in_array($roleName, $apiRoleList)) {
                $this->roleRepository->addRoleAccount((int)$account['id'], $roleName, 'api');
            }
        }

        // Check and add new admin roles
        foreach ($userRoles as $roleName) {
            if (in_array($roleName, $adminRoleList)) {
                $this->roleRepository->addRoleAccount((int)$account['id'], $roleName, 'admin');
            }
        }

        // Save log
        $this->historyService->logger('updateRoleAccount', ['request' => ['role' => $roles], 'account' => $account, 'operator' => $operator]);
    }

    public function resetRoleListInCache(): void
    {
        $this->cacheService->deleteItems([
            'roles_admin',
            'roles_api',
            'roles_light',
            'role_list',
        ]);
    }

    public function deleteRoleResource(object|array|null $params, mixed $operator): void
    {
        if (isset($params['name']) && !empty($params['name'])) {
            $this->roleRepository->updateRoleResource($params['name'], ['status' => 0]);
        }
        $this->resetRoleListInCache();
    }

    public function updateRoleResource($role, $params, $operator): void
    {
        // Set update params
        $updateParams = [];
        if (isset($params['title']) && !empty($params['title'])) {
            $updateParams['title'] = $params['title'];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $updateParams['status'] = $params['status'];
        }

        // Do update if needed
        if (!empty($updateParams)) {
            $this->roleRepository->updateRoleResource($role['name'], $updateParams);
        }

        // Clean cache
        $this->resetRoleListInCache();

        // Sync permissions
        if (isset($params['permissions'])
            && !empty($params['permissions'])
        ) {
            $this->permissionService->managePermissionByRole($role['name'], $params['permissions']);
        }
    }

    public function isDuplicated($field, $value, $id = 0): bool
    {
        return (bool)$this->roleRepository->duplicatedRole(
            [
                'field' => $field,
                'value' => $value,
                'id'    => $id,
            ]
        );
    }

    public function canonizeRole($role)
    {
        if (empty($role)) {
            return [];
        }

        if (is_object($role)) {
            $role = [
                'id'      => $role->getId(),
                'name'    => $role->getName(),
                'title'   => $role->getTitle(),
                'section' => $role->getSection(),
                'status'  => $role->getStatus(),
            ];
        }

        return $role;
    }

    public function canonizeUserRole($userRole)
    {
        if (empty($userRole)) {
            return [];
        }

        // Make list
        $roleResource = [];
        $roles        = $this->getRoleResourceList();
        foreach ($roles as $role) {
            $roleResource[$role['name']] = $role;
        }

        if (is_object($userRole)) {
            $userRole = [
                'id'      => $userRole->getId(),
                'user_id' => $userRole->getUserId(),
                'role'    => $userRole->getRoleResource(),
                'section' => $userRole->getSection(),
                'title'   => $roleResource[$userRole->getRoleResource()]['title'],
            ];
        }

        return $userRole;
    }

    public function canonizeAccountRole($roleList): array
    {
        $list = [
            'admin' => null,
            'api'   => null,
        ];

        // Get role resource
        $resources = $this->getRoleResourceList();
        foreach ($resources as $resource) {
            if (in_array($resource['name'], $roleList)) {
                $list[$resource['section']][] = [
                    'role'    => $resource['name'],
                    'title'   => $resource['title'],
                    'section' => $resource['section'],
                ];
            }
        }

        return $list;
    }
}