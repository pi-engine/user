<?php

declare(strict_types=1);

namespace Pi\User\Factory\Handler\Admin\Cache;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Pi\User\Handler\Admin\Cache\PersistHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class PersistHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return PersistHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): PersistHandler
    {
        return new PersistHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(CacheService::class)
        );
    }
}