<?php

namespace User\Factory\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Service\CacheService;
use User\Service\TokenService;

class TokenServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return TokenService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): TokenService
    {
        return new TokenService(
            $container->get(CacheService::class)
        );
    }
}