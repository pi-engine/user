<?php

namespace User\Service;

use User\Repository\ProfileRepositoryInterface;

class ProfileService implements ServiceInterface
{
    /* @var ProfileRepositoryInterface */
    protected ProfileRepositoryInterface $profileRepository;

    /* @var TokenService */
    protected TokenService $tokenService;

    /* @var CacheService */
    protected CacheService $cacheService;

    /**
     * @param ProfileRepositoryInterface $profileRepository
     * @param CacheService               $cacheService
     */
    public function __construct(
        ProfileRepositoryInterface $profileRepository,
        CacheService $cacheService
    ) {
        $this->profileRepository = $profileRepository;
        $this->cacheService      = $cacheService;
    }

}