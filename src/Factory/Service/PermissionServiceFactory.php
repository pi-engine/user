<?php

namespace User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Repository\PermissionRepositoryInterface;
use User\Service\PermissionService;

class PermissionServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return PermissionService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): PermissionService
    {
        return new PermissionService(
            $container->get(PermissionRepositoryInterface::class),
            $container->get(CacheService::class)
        );
    }
}