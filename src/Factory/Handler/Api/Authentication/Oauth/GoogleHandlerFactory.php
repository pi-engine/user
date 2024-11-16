<?php

namespace Pi\User\Factory\Handler\Api\Authentication\Oauth;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\User\Handler\Api\Authentication\Oauth\GoogleHandler;
use Pi\User\Service\AccountService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class GoogleHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return GoogleHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): GoogleHandler
    {
        // Get config
        $config = $container->get('config');

        return new GoogleHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class),
            $config['account']['oauth']['google']
        );
    }
}