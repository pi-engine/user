<?php

namespace User\Factory\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Handler\ErrorHandler;
use Pi\Core\Middleware\SecurityMiddleware;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Middleware\AvatarUploadMiddleware;
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