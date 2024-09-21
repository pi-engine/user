<?php

namespace User\Factory\Security;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Security\AccountLocked;
use User\Security\AccountLoginAttempts;
use User\Service\CacheService;

class AccountLoginAttemptsFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return AccountLoginAttempts
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AccountLoginAttempts
    {
        // Get config
        $config = $container->get('config');
        $config = $config['security'] ?? [];

        return new AccountLoginAttempts(
            $container->get(CacheService::class),
            $container->get(AccountLocked::class),
            $config
        );
    }
}