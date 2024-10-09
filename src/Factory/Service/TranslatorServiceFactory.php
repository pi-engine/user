<?php

namespace User\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Service\TranslatorService;

class TranslatorServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): TranslatorService
    {
        // Get config
        $config = $container->get('config');

        return new TranslatorService(
            $config['translator']
        );
    }
}