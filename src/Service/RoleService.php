<?php

namespace User\Service;

use User\Repository\RoleRepositoryInterface;

use function in_array;

class RoleService implements ServiceInterface
{
    /* @var RoleRepositoryInterface */
    protected RoleRepositoryInterface $roleRepository;

    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var HistoryService */
    protected HistoryService $historyService;

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
        CacheService $cacheService,
        HistoryService $historyService,
        $config
    ) {
        $this->roleRepository = $roleRepository;
        $this->cacheService   = $cacheService;
        $this->historyService = $historyService;
        $this->config         = $config;
    }

    public function getApiRoleList(): array
    {
        $roles = $this->cacheService->getItem('roles-api');
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

            $roles = $this->cacheService->setItem('roles-api', $list);
        }

        return $roles;
    }

    public function getAdminRoleList(): array
    {
        $roles = $this->cacheService->getItem('roles-admin');
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

            $roles = $this->cacheService->setItem('roles-admin', $list);
        }

        return $roles;
    }

    public function getAllRoleList(): array
    {
        $roles = $this->cacheService->getItem('roles-light');
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

            $roles = $this->cacheService->setItem('roles-light', $list);
        }

        return $roles;
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
        $roles = $this->cacheService->getItem('roleList');
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

            $roles = $this->cacheService->setItem('roleList', $list);
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

    public function addDefaultRoles($account, $operator = []): void
    {
        $roleList = $this->config['default_roles'] ?? $this->defaultRoles;
        foreach ($roleList as $role) {
            $this->roleRepository->addRoleAccount((int)$account['id'], $role['name'], $role['section']);
        }

        // Save log
        $this->historyService->logger('addDefaultRoles', ['params' => $roleList, 'account' => $account, 'operator' => $operator]);
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
            ['params' => ['role' => $roleName, 'section' => $section], 'account' => $account, 'operator' => $operator]
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
        $this->historyService->logger('deleteRoleAccount', ['params' => ['role' => $roleName], 'account' => $account, 'operator' => $operator]);
    }

    public function deleteAllRoleAccount($account, string $section = 'api', $operator = []): void
    {
        $this->roleRepository->deleteAllRoleAccount((int)$account['id'], $section);

        // Save log
        $this->historyService->logger('deleteAllRoleAccount', ['params' => ['role' => null], 'account' => $account, 'operator' => $operator]);
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
        $this->historyService->logger('updateRoleAccount', ['params' => ['role' => $roles], 'account' => $account, 'operator' => $operator]);
    }

    public function addRoleResource(object|array|null $params, mixed $operator)
    {
        $result = $this->roleRepository->addRoleResource($params);
        $result = $this->canonizeRole($result);
        $this->resetRoleListInCache();
        return $result;
    }

    public function resetRoleListInCache(): void
    {
        $this->cacheService->deleteItems([
            'roles-admin',
            'roles-api',
            'roles-light',
            'roleList',
        ]);
    }

    public function deleteRoleResource(object|array|null $params, mixed $operator): void
    {
        $name = (isset($params['name']) && !empty($params['name'])) ? $params['name'] : '';
        $this->roleRepository->updateRoleResource($name, ['status' => 0]);
        $this->resetRoleListInCache();
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