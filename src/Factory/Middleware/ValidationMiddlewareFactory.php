<?php

namespace User\Factory\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Handler\ErrorHandler;
use User\Middleware\ValidationMiddleware;
use User\Service\AccountService;
use User\Service\CacheService;
use User\Service\UtilityService;

class ValidationMiddlewareFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return ValidationMiddleware
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ValidationMiddleware
    {
        // Get config
        $config  = $container->get('config');
        $config  = $config['validation'] ?? [];

        return new ValidationMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class),
            $container->get(UtilityService::class),
            $container->get(CacheService::class),
            $container->get(ErrorHandler::class),
            $config
        );
    }
}