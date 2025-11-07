<?php

declare(strict_types=1);

namespace Pi\User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Pi\User\Service\TokenService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class TokenServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return TokenService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): TokenService
    {
        // Get config
        $config = $container->get('config');
        $config = array_merge(
            $config['jwt'] ?? [],
            $config['security'] ?? [],
        );

        return new TokenService(
            $container->get(CacheService::class),
            $config
        );
    }
}