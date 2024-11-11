<?php

namespace User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Notification\Service\NotificationService;
use Pi\Core\Security\Account\AccountLocked;
use Pi\Core\Security\Account\AccountLoginAttempts;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\TranslatorService;
use Pi\Core\Service\UtilityService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Repository\AccountRepositoryInterface;
use User\Service\AccountService;
use User\Service\AvatarService;
use User\Service\HistoryService;
use User\Service\PermissionService;
use User\Service\RoleService;
use User\Service\TokenService;

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
        $config  = $container->get('config');
        $global  = $config['global'] ?? [];
        $account = $config['account'] ?? [];
        $config  = array_merge($global, $account);

        return new AccountService(
            $container->get(AccountRepositoryInterface::class),
            $container->get(RoleService::class),
            $container->get(PermissionService::class),
            $container->get(TokenService::class),
            $container->get(CacheService::class),
            $container->get(UtilityService::class),
            $container->get(AvatarService::class),
            $container->get(NotificationService::class),
            $container->get(HistoryService::class),
            $container->get(TranslatorService::class),
            $container->get(AccountLoginAttempts::class),
            $container->get(AccountLocked::class),
            $config
        );
    }
}