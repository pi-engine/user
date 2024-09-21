<?php

namespace User\Factory\Service;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
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