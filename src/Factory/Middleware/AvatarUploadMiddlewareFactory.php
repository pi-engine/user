<?php

namespace Pi\User\Factory\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Handler\ErrorHandler;
use Pi\Core\Middleware\SecurityMiddleware;
use Pi\User\Middleware\AvatarUploadMiddleware;
use Pi\User\Service\AccountService;
use Pi\User\Service\AvatarService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class AvatarUploadMiddlewareFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return SecurityMiddleware
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AvatarUploadMiddleware
    {
        $config = $container->get('config');
        $config = $config['avatar'] ?? [];

        return new AvatarUploadMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class),
            $container->get(AvatarService::class),
            $container->get(ErrorHandler::class),
            $config
        );
    }
}