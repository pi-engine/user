<?php

namespace User\Service;

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

}