<?php

namespace User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Repository\RoleRepositoryInterface;
use User\Service\CacheService;
use User\Service\HistoryService;
use User\Service\RoleService;

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
            $container->get(CacheService::class),
            $container->get(HistoryService::class),
            $config
        );
    }
}