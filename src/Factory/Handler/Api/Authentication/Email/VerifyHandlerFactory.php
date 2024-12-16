<?php

declare(strict_types=1);

namespace Pi\User\Factory\Handler\Api\Authentication\Email;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Handler\Api\Authentication\Email\VerifyHandler;
use Pi\User\Service\AccountService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class VerifyHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return VerifyHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): VerifyHandler
    {
        return new VerifyHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class)
        );
    }
}