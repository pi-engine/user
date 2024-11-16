<?php

namespace Pi\User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Psr\Container\ContainerInterface;
use Pi\User\Service\TokenService;

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