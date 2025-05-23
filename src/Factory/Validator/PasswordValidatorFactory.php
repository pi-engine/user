<?php

declare(strict_types=1);

namespace Pi\User\Factory\Validator;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\ConfigService;
use Pi\Core\Service\UtilityService;
use Pi\User\Service\AccountService;
use Pi\User\Validator\PasswordValidator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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
            $container->get(UtilityService::class),
            $container->get(ConfigService::class)
        );
    }
}