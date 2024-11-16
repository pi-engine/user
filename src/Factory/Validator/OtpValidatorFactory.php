<?php

namespace Pi\User\Factory\Validator;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Pi\User\Service\AccountService;
use Pi\User\Validator\OtpValidator;

class OtpValidatorFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return OtpValidator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): OtpValidator
    {
        return new OtpValidator(
            $container->get(AccountService::class),
            $container->get(CacheService::class)
        );
    }
}