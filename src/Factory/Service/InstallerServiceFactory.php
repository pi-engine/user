<?php

namespace User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Service\InstallerService;
use User\Service\PermissionService;

class InstallerServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return InstallerService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): InstallerService
    {
        return new InstallerService(
            $container->get(PermissionService::class)
        );
    }
}