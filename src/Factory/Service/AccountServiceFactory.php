<?php

namespace Pi\User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Security\Account\AccountLocked;
use Pi\Core\Security\Account\AccountLoginAttempts;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\TranslatorService;
use Pi\Core\Service\UtilityService;
use Pi\Notification\Service\NotificationService;
use Pi\User\Repository\AccountRepositoryInterface;
use Pi\User\Service\AccountService;
use Pi\User\Service\AvatarService;
use Pi\User\Service\HistoryService;
use Pi\User\Service\PermissionService;
use Pi\User\Service\RoleService;
use Pi\User\Service\TokenService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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

        // Set account
        $accountService = new AccountService(
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

        // Set light company service
        $modules = $container->get('ModuleManager')->getLoadedModules();
        if (isset($modules['Pi\Company'])) {
            $companyService = $container->get('Pi\Company\Service\CompanyLightService');
            $accountService->setCompanyService($companyService);
        }

        return $accountService;
    }
}