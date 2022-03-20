<?php

namespace User\Factory\Repository;

use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Model\Permission\Page;
use User\Model\Permission\Resource;
use User\Model\Permission\Role;
use User\Repository\PermissionRepository;

class PermissionRepositoryFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return PermissionRepository
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): PermissionRepository
    {
        return new PermissionRepository(
            $container->get(AdapterInterface::class),
            new ReflectionHydrator(),
            new Resource('', '','','', ''),
            new Role('', '','',''),
            new Page('', '','','','','','','','')
        );
    }
}