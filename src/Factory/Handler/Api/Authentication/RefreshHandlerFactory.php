<?php

declare(strict_types=1);

namespace Pi\User\Factory\Handler\Api\Authentication;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Handler\Api\Authentication\RefreshHandler;
use Pi\User\Service\AccountService;
use Pi\User\Service\TokenService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class RefreshHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return RefreshHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): RefreshHandler
    {
        return new RefreshHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class),
            $container->get(TokenService::class)
        );
    }
}