<?php

declare(strict_types=1);

namespace Pi\User\Factory\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Model\Role\Account;
use Pi\User\Model\Role\Resource;
use Pi\User\Repository\RoleRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class RoleRepositoryFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return RoleRepository
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): RoleRepository
    {
        return new RoleRepository(
            $container->get(AdapterInterface::class),
            new ReflectionHydrator(),
            new Resource('', '', ''),
            new Account(0, '', '')
        );
    }
}