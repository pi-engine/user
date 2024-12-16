<?php

declare(strict_types=1);

namespace Pi\User\Factory\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Handler\ErrorHandler;
use Pi\User\Middleware\AuthorizationMiddleware;
use Pi\User\Service\PermissionService;
use Pi\User\Service\RoleService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class AuthorizationMiddlewareFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return AuthorizationMiddleware
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AuthorizationMiddleware
    {
        return new AuthorizationMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(RoleService::class),
            $container->get(PermissionService::class),
            $container->get(ErrorHandler::class)
        );
    }
}