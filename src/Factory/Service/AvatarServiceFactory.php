<?php

namespace User\Factory\Service;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Service\AvatarService;
use User\Service\HistoryService;

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