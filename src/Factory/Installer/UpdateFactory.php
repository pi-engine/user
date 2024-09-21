<?php

namespace User\Factory\Installer;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Installer\Update;
use User\Service\InstallerService;

class UpdateFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return Update
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Update
    {
        return new Update(
            $container->get(InstallerService::class)
        );
    }
}