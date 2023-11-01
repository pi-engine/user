<?php

namespace User\Factory\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Notification\Service\NotificationService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Repository\AccountRepositoryInterface;
use User\Service\AccountService;
use User\Service\CacheService;
use User\Service\HistoryService;
use User\Service\RoleService;
use User\Service\TokenService;
use User\Service\UtilityService;

class AccountServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return AccountService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AccountService
    {
        // Get config
        $config = $container->get('config');

        return new AccountService(
            $container->get(AccountRepositoryInterface::class),
            $container->get(RoleService::class),
            $container->get(TokenService::class),
            $container->get(CacheService::class),
            $container->get(UtilityService::class),
            $container->get(NotificationService::class),
            $container->get(HistoryService::class),
            $config['account']
        );
    }
}