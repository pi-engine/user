<?php

namespace Pi\User\Factory\Handler\Api\Authentication\Oauth;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Pi\User\Handler\Api\Authentication\Oauth\MicrosoftHandler;
use Pi\User\Handler\Api\Authentication\Oauth\Oauth2Handler;
use Pi\User\Service\AccountService;

class Oauth2HandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return MicrosoftHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Oauth2Handler
    {
        // Get config
        $config = $container->get('config');

        return new Oauth2Handler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(AccountService::class),
            $config['account']['oauth']['oauth2']
        );
    }
}