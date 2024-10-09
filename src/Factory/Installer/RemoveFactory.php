<?php

namespace User\Factory\Installer;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Installer\Remove;
use User\Service\InstallerService;

class RemoveFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return Remove
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Remove
    {
        return new Remove(
            $container->get(InstallerService::class)
        );
    }
}