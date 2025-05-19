<?php

declare(strict_types=1);

namespace Pi\User\Factory\Handler\Admin\Permission\Resource;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Handler\Admin\Permission\Resource\ManageHandler;
use Pi\User\Service\PermissionService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ManageHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return ManageHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ManageHandler
    {
        return new ManageHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(PermissionService::class)
        );
    }
}