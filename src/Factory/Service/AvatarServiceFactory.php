<?php

declare(strict_types=1);

namespace Pi\User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Service\AvatarService;
use Pi\User\Service\HistoryService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class AvatarServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return AvatarService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AvatarService
    {
        // Get config
        $config = $container->get('config');
        $config = $config['avatar'] ?? [];

        return new AvatarService(
            $container->get(HistoryService::class),
            $config
        );
    }
}