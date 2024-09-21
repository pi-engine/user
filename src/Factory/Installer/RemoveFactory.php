<?php

namespace User\Factory\Installer;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
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