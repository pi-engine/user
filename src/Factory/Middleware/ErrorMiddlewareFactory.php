<?php

namespace User\Factory\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Handler\ErrorHandler;
use User\Middleware\ErrorMiddleware;

class ErrorMiddlewareFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return ErrorMiddleware
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ErrorMiddleware
    {
        return new ErrorMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(ErrorHandler::class)
        );
    }
}