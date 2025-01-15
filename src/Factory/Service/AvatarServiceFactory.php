<?php

declare(strict_types=1);

namespace Pi\User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Service\S3Service;
use Pi\Media\Storage\LocalStorage;
use Pi\User\Service\AccountService;
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
        $config = array_merge(
            $config['global'] ?? [],
            $config['avatar'] ?? [],
            [
                'storage' => $config['media']['storage'] ?? 'local',
                's3'      => $config['media']['s3'] ?? [],
            ]
        );

        return new AvatarService(
            $container->get(AccountService::class),
            $container->get(HistoryService::class),
            $container->get(LocalStorage::class),
            $container->get(S3Service::class),
            $config
        );
    }
}