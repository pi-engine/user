<?php

declare(strict_types=1);

namespace Pi\User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\UtilityService;
use Pi\User\Repository\RoleRepositoryInterface;
use Pi\User\Service\HistoryService;
use Pi\User\Service\PermissionService;
use Pi\User\Service\RoleService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class RoleServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return RoleService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): RoleService
    {
        // Get config
        $config = $container->get('config');
        $config = $config['roles'] ?? [];

        return new RoleService(
            $container->get(RoleRepositoryInterface::class),
            $container->get(PermissionService::class),
            $container->get(CacheService::class),
            $container->get(HistoryService::class),
            $container->get(UtilityService::class),
            $config
        );
    }
}