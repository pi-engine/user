<?php

declare(strict_types=1);

namespace Pi\User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\UtilityService;
use Pi\Notification\Service\NotificationService;
use Pi\User\Repository\AccountRepositoryInterface;
use Pi\User\Service\AccountService;
use Pi\User\Service\MultiFactorService;
use Psr\Container\ContainerInterface;

class MultiFactorServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return MultiFactorService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): MultiFactorService
    {
        // Get config
        $config = $container->get('config');
        $config = array_merge(
            $config['global'] ?? [],
            $config['account'] ?? []
        );

        return new MultiFactorService(
            $container->get(AccountRepositoryInterface::class),
            $container->get(AccountService::class),
            $container->get(CacheService::class),
            $container->get(UtilityService::class),
            $container->get(NotificationService::class),
            $config
        );
    }
}