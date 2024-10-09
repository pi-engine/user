<?php

namespace User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Logger\Service\LoggerService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Service\HistoryService;

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
            $config['utility']
        );
    }
}