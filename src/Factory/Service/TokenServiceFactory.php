<?php

namespace User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Service\CacheService;
use User\Service\TokenService;

class TokenServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return TokenService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): TokenService
    {
        // Get config
        $config = $container->get('config');

        return new TokenService(
            $container->get(CacheService::class),
            $config['jwt']
        );
    }
}