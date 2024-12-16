<?php

declare(strict_types=1);

namespace Pi\User\Factory\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Model\Permission\Page;
use Pi\User\Model\Permission\Resource;
use Pi\User\Model\Permission\Role;
use Pi\User\Repository\PermissionRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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
            new Resource('', '', '', '', ''),
            new Role('', '', '', '', ''),
            new Page('', '', '', '', '', '', '', '', 0, '')
        );
    }
}