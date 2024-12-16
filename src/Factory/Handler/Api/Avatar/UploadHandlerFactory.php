<?php

declare(strict_types=1);

namespace Pi\User\Factory\Handler\Api\Avatar;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Handler\Api\Avatar\UploadHandler;
use Pi\User\Service\AccountService;
use Pi\User\Service\AvatarService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class UploadHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return UploadHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UploadHandler
    {
        return new UploadHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class),
            $container->get(AvatarService::class)
        );
    }
}