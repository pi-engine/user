<?php

declare(strict_types=1);

namespace Pi\User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\UtilityService;
use Pi\Logger\Service\LoggerService;
use Pi\User\Service\HistoryService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class HistoryServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return HistoryService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): HistoryService
    {
        // Get config
        $config = $container->get('config');

        return new HistoryService(
            $container->get(LoggerService::class),
            $container->get(UtilityService::class),
            $config['utility']
        );
    }
}