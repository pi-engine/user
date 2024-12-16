<?php

declare(strict_types=1);

namespace Pi\User\Factory\Validator;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Service\AccountService;
use Pi\User\Validator\IdentityValidator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class IdentityValidatorFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return IdentityValidator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): IdentityValidator
    {
        return new IdentityValidator(
            $container->get(AccountService::class)
        );
    }
}