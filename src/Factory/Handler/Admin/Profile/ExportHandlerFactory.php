<?php

declare(strict_types=1);

namespace Pi\User\Factory\Handler\Admin\Profile;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Service\MediaService;
use Pi\User\Handler\Admin\Profile\ExportHandler;
use Pi\User\Handler\Admin\Profile\ListHandler;
use Pi\User\Service\AccountService;
use Pi\User\Service\ExportService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ExportHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return ListHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ExportHandler
    {
        return new ExportHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class),
            $container->get(ExportService::class),
            $container->get(MediaService::class)
        );
    }
}