<?php

namespace User\Factory\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Security\Account\AccountLocked;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Handler\ErrorHandler;
use User\Middleware\AuthenticationMiddleware;
use User\Service\AccountService;
use User\Service\CacheService;
use User\Service\TokenService;

class AuthenticationMiddlewareFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return AuthenticationMiddleware
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AuthenticationMiddleware
    {
        // Get config
        $config = $container->get('config');
        $config = $config['account'] ?? [];

        return new AuthenticationMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class),
            $container->get(TokenService::class),
            $container->get(CacheService::class),
            $container->get(AccountLocked::class),
            $container->get(ErrorHandler::class),
            $config
        );
    }
}