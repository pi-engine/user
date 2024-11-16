<?php

namespace Pi\User\Factory\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Pi\User\Model\Profile\Profile;
use Pi\User\Repository\ProfileRepository;

class ProfileRepositoryFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return ProfileRepository
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ProfileRepository
    {
        return new ProfileRepository(
            $container->get(AdapterInterface::class),
            new ReflectionHydrator(),
            new Profile()
        );
    }
}