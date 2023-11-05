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
                'name' => 'member',
                'section' => 'api',
            ],
            /* [
                'name'    => 'admin',
                'section' => 'admin',
            ], */
        ];

    protected array $sectionList = ['api', 'admin'];

    /**
     * @param RoleRepositoryInterface $roleRepository
     * @param CacheService $cacheService
     * @param HistoryService $historyService
     */
    public function __construct(
        RoleRepositoryInterface $roleRepository,
        CacheService            $cacheService,
        HistoryService          $historyService
    )
    {
        $this->roleRepository = $roleRepository;
        $this->cacheService = $cacheService;
        $this->historyService = $historyService;
    }

    public function getApiRoleList(): array
    {
        $roles = $this->cacheService->getItem('roles-api');
        if (empty($roles)) {
            $listParams = [
                'section' => 'api',
                'status' => 1,
            ];

            // Get list
            $list = [];
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
                'status' => 1,
            ];

            // Get list
            $list = [];
            $rowSet = $this->roleRepository->getRoleResourceList($listParams);
            foreach ($rowSet as $row) {
                $list[] = $row->getName();
            }

            $roles = $this->cacheService->setItem('roles-admin', $list);
        }

        return $roles;
    }

    public function getRoleResourceList($section = ''): array
    {
        $roles = $this->cacheService->getItem('roleList');
        if (empty($roles)) {
            $listParams = [
                'section' => $section,
                'status' => 1,
            ];

            // Get list
            $list = [];
            $rowSet = $this->roleRepository->getRoleResourceList($listParams);
            foreach ($rowSet as $row) {
                $list[] = $this->canonizeRole($row);
            }

            $roles = $this->cacheService->setItem('roleList', $list);
        }

        return $roles;
    }

    public function canonizeRole($role)
    {
        if (empty($role)) {
            return [];
        }

        if (is_object($role)) {
            $role = [
                'id' => $role->getId(),
                'name' => $role->getName(),
                'title' => $role->getTitle(),
                'section' => $role->getSection(),
                'status' => $role->getStatus(),
            ];
        }

        return $role;
    }

    public function addDefaultRoles(int $userId, $operator = []): void
    {
        foreach ($this->defaultRoles as $role) {
            $this->roleRepository->addRoleAccount($userId, $role['name'], $role['section']);
        }

        // Save log
        $this->historyService->logger('addDefaultRoles', ['params' => $this->defaultRoles, 'account' => ['id' => $userId], 'operator' => $operator]);
    }

    public function addRoleAccount(int $userId, string $roleName, string $section = 'api', $operator = []): void
    {
        $systemRoleList = $this->getRoleListLight();
        $userRoleList = $this->getRoleAccount($userId);

        // Check role and add
        if (
            in_array($roleName, $systemRoleList)
            && !in_array($section, $userRoleList)
            && in_array($section, $this->sectionList)
        ) {
            $this->roleRepository->addRoleAccount($userId, $roleName, $section);
        }

        // Update cache
        $this->cacheService->updateUserRoles($userId, [$roleName], $section);

        // Save log
        $this->historyService->logger('addRoleAccount', ['params' => ['role' => $roleName, 'section' => $section], 'account' => ['id' => $userId], 'operator' => $operator]);
    }

    public function getRoleListLight(): array
    {
        $roles = $this->cacheService->getItem('roles-light');
        if (empty($roles)) {
            $listParams = [
                'status' => 1,
            ];

            // Get list
            $list = [];
            $rowSet = $this->roleRepository->getRoleResourceList($listParams);
            foreach ($rowSet as $row) {
                $list[] = $row->getName();
            }

            $roles = $this->cacheService->setItem('roles-light', $list);
        }

        return $roles;
    }

    public function getRoleAccount($userId): array
    {
        $list = [];
        $rowSet = $this->roleRepository->getRoleAccount($userId);
        foreach ($rowSet as $row) {
            $list[] = $row->getRoleResource();
        }

        return $list;
    }

    public function getRoleAccountList($userIdList, $section = 'full'): array
    {
        $list = [];
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

    public function deleteRoleAccount(int $userId, string $roleName): void
    {
        $roleList = $this->getRoleListLight();
        if (in_array($roleName, $roleList)) {
            $this->roleRepository->deleteRoleAccount($userId, $roleName);
        }

        // Save log
        $this->historyService->logger('deleteRoleAccount', ['params' => ['role' => $roleName], 'account' => ['id' => $userId]]);
    }

    public function deleteAllRoleAccount(int $userId, $operator = []): void
    {
        $this->roleRepository->deleteAllRoleAccount($userId);
        // Save log
        $this->historyService->logger('deleteAllRoleAccount', ['params' => ['role' => null], 'account' => ['id' => $userId], 'operator' => $operator]);
    }

    public function canonizeUserRole($userRole)
    {
        if (empty($userRole)) {
            return [];
        }

        if (is_object($userRole)) {
            $userRole = [
                'id' => $userRole->getId(),
                'user_id' => $userRole->getUserId(),
                'role' => $userRole->getRoleResource(),
                'section' => $userRole->getSection(),
            ];
        }

        return $userRole;
    }

    public function addRoleResource(object|array|null $params, mixed $operator)
    {
        $result = $this->roleRepository->addRoleResource($params);
        $result = $this->canonizeRole($result);
        $this->cacheService->deleteItem([
            'roles-admin',
            'roles-api',
            'roles-light',
            'roleList'
        ]);
        return $result;
    }
}