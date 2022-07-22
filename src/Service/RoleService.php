<?php

namespace User\Service;

use User\Repository\RoleRepositoryInterface;

class RoleService implements ServiceInterface
{
    /* @var RoleRepositoryInterface */
    protected RoleRepositoryInterface $roleRepository;

    /* @var CacheService */
    protected CacheService $cacheService;

    protected array $defaultRoles
        = [
            [
                'name'    => 'member',
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
     * @param CacheService            $cacheService
     */
    public function __construct(
        RoleRepositoryInterface $roleRepository,
        CacheService $cacheService
    ) {
        $this->roleRepository = $roleRepository;
        $this->cacheService   = $cacheService;
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

    public function addDefaultRoles(int $userId): void
    {
        foreach ($this->defaultRoles as $role) {
            $this->roleRepository->addRoleAccount($userId, $role['name'], $role['section']);
        }
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

    public function addRoleAccount(int $userId, string $roleName, string $section = 'api'): void
    {
        $roleList = $this->getRoleListLight();
        if (in_array($roleName, $roleList) && in_array($section, $this->sectionList)) {
            $this->roleRepository->addRoleAccount($userId, $roleName, $section);
        }
    }

    public function getRoleListLight(): array
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

    public function deleteRoleAccount(int $userId, string $roleName): void
    {
        $roleList = $this->getRoleListLight();
        if (in_array($roleName, $roleList)) {
            $this->roleRepository->deleteRoleAccount($userId, $roleName);
        }
    }

    public function canonizeUserRole($userRole)
    {
        if (empty($userRole)) {
            return [];
        }

        if (is_object($userRole)) {
            $userRole = [
                'id'      => $userRole->getId(),
                'user_id' => $userRole->getUserId(),
                'role'    => $userRole->getRoleResource(),
                'section' => $userRole->getSection(),
            ];
        }

        return $userRole;
    }
}