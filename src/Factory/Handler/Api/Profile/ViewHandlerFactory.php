<?php

namespace Pi\User\Factory\Handler\Api\Profile;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Handler\Api\Profile\ViewHandler;
use Pi\User\Service\AccountService;
use Pi\User\Service\TokenService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ViewHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return ViewHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ViewHandler
    {
        return new ViewHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class),
            $container->get(TokenService::class)
        );
    }
}