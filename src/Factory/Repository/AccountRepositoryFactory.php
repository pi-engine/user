<?php

namespace User\Factory\Repository;

use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Model\Account\Account;
use User\Model\Account\Credential;
use User\Model\Account\Profile;
use User\Repository\AccountRepository;

class AccountRepositoryFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return AccountRepository
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AccountRepository
    {
        return new AccountRepository(
            $container->get(AdapterInterface::class),
            new ReflectionHydrator(),
            new Account('', '', '', '', 0, 0),
            new Profile(0, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
            new Credential('')
        );
    }
}