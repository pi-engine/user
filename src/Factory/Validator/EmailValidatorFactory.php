<?php

namespace Pi\User\Factory\Validator;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Service\AccountService;
use Pi\User\Validator\EmailValidator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class EmailValidatorFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return EmailValidator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): EmailValidator
    {
        return new EmailValidator(
            $container->get(AccountService::class)
        );
    }
}