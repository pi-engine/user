<?php

declare(strict_types=1);

namespace Pi\User\Factory\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Handler\ErrorHandler;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\UtilityService;
use Pi\User\Middleware\ValidationMiddleware;
use Pi\User\Service\AccountService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

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
        $config = $container->get('config');
        $config = $config['validation'] ?? [];

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