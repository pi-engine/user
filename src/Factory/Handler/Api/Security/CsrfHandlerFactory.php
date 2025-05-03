<?php

declare(strict_types=1);

namespace Pi\User\Factory\Handler\Api\Security;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CsrfService;
use Pi\User\Handler\Api\Security\CsrfHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class CsrfHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return CsrfHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): CsrfHandler
    {
        return new CsrfHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(CsrfService::class)
        );
    }
}