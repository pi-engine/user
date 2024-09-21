<?php

namespace User\Factory\Middleware;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Handler\ErrorHandler;
use User\Middleware\AvatarUploadMiddleware;
use User\Middleware\SecurityMiddleware;
use User\Service\AccountService;
use User\Service\AvatarService;

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