<?php

declare(strict_types=1);

namespace Pi\User\Factory\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Model\Account\Account;
use Pi\User\Model\Account\AccountProfile;
use Pi\User\Model\Account\Credential;
use Pi\User\Model\Account\MultiFactor;
use Pi\User\Model\Account\Profile;
use Pi\User\Model\Role\Account as AccountRole;
use Pi\User\Repository\AccountRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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
            new Account('', '', '', '', 0, 0, 0),
            new AccountProfile('', '', '', '', 0, 0, '', '', '', '', '', '', 0),
            new Profile(0, '', '', '', '', '', '', 0),
            new AccountRole(0, '', '', 0),
            new Credential('', 0),
            new MultiFactor(0, '', 0),
        );
    }
}