<?php

namespace User\Factory\Middleware;

use Interop\Container\Containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Handler\ErrorHandler;
use User\Middleware\JsonToArrayMiddleware;

class JsonToArrayMiddlewareFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): JsonToArrayMiddleware
    {
        return new JsonToArrayMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(ErrorHandler::class)
        );
    }
}