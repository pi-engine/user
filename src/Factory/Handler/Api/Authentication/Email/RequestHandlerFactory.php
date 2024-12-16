<?php

declare(strict_types=1);

namespace Pi\User\Factory\Handler\Api\Authentication\Email;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Handler\Api\Authentication\Email\RequestHandler;
use Pi\User\Service\AccountService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class RequestHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return RequestHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): RequestHandler
    {
        return new RequestHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class)
        );
    }
}