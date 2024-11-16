<?php

namespace Pi\User\Factory\Handler\Api\Profile;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Handler\Api\Profile\HistoryHandler;
use Pi\User\Service\AccountService;
use Pi\User\Service\HistoryService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class HistoryHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return HistoryHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): HistoryHandler
    {
        return new HistoryHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class),
            $container->get(HistoryService::class)
        );
    }
}