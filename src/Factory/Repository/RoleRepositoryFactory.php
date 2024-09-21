<?php

namespace User\Factory\Repository;

use Psr\Container\ContainerInterface;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Model\Role\Account;
use User\Model\Role\Resource;
use User\Repository\RoleRepository;

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