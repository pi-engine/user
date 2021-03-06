<?php

namespace User\Factory\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Repository\AccountRepositoryInterface;
use User\Service\AccountService;
use User\Service\CacheService;
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
        return new AccountService(
            $container->get(AccountRepositoryInterface::class),
            $container->get(RoleService::class),
            $container->get(TokenService::class),
            $container->get(CacheService::class)
        );
    }
}