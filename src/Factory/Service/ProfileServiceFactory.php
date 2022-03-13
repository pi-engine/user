<?php

namespace User\Factory\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Repository\ProfileRepositoryInterface;
use User\Service\CacheService;
use User\Service\ProfileService;

class ProfileServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return ProfileService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ProfileService
    {
        return new ProfileService(
            $container->get(ProfileRepositoryInterface::class),
            $container->get(CacheService::class)
        );
    }
}