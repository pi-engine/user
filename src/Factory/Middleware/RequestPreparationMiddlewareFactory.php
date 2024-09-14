<?php

namespace User\Factory\Middleware;

use Interop\Container\Containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Handler\ErrorHandler;
use User\Middleware\RequestPreparationMiddleware;

class RequestPreparationMiddlewareFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): RequestPreparationMiddleware
    {
        // Get config
        $config = $container->get('config');
        $config = $config['security'] ?? [];

        return new RequestPreparationMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(ErrorHandler::class),
            $config
        );
    }
}