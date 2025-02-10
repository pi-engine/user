<?php

declare(strict_types=1);

namespace Pi\User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\UtilityService;
use Pi\User\Service\MultiFactorService;
use Psr\Container\ContainerInterface;

class MultiFactorServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return MultiFactorService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): MultiFactorService
    {
        // Get config
        $config = $container->get('config');
        $config = $config['account']['multi_factor'] ?? null;

        return new MultiFactorService(
            $container->get(CacheService::class),
            $container->get(UtilityService::class),
            $config
        );
    }
}