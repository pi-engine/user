<?php

namespace User\Factory\Installer;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Installer\Install;
use User\Service\InstallerService;

class InstallFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return Install
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Install
    {
        return new Install(
            $container->get(InstallerService::class)
        );
    }
}