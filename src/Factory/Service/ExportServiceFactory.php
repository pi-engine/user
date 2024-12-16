<?php

declare(strict_types=1);

namespace Pi\User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Service\ExportService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ExportServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return ExportService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ExportService
    {
        // Get config
        $config = $container->get('config');
        $config = $config['export'] ?? [];

        return new ExportService($config);
    }
}