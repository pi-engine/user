<?php

namespace User\Factory\Validator;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Service\AccountService;
use User\Service\UtilityService;
use User\Validator\PasswordValidator;

class PasswordValidatorFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return PasswordValidator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): PasswordValidator
    {
        return new PasswordValidator(
            $container->get(AccountService::class),
            $container->get(UtilityService::class)
        );
    }
}