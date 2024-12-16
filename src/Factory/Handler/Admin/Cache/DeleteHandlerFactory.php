<?php

declare(strict_types=1);

namespace Pi\User\Factory\Handler\Admin\Cache;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Pi\User\Handler\Admin\Cache\DeleteHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class DeleteHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return DeleteHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): DeleteHandler
    {
        return new DeleteHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(CacheService::class)
        );
    }
}