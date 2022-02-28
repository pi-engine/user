<?php

namespace User;

use Laminas\Mvc\Middleware\PipeSpec;
use Laminas\Router\Http\Literal;

return [
    'service_manager' => [
        'aliases'   => [
            Repository\AccountRepositoryInterface::class => Repository\AccountRepository::class,
            Service\ServiceInterface::class              => Service\AccountService::class,
        ],
        'factories' => [
            Repository\AccountRepository::class        => Factory\Repository\AccountRepositoryFactory::class,
            Service\AccountService::class              => Factory\Service\AccountServiceFactory::class,
            Service\TokenService::class                => Factory\Service\TokenServiceFactory::class,
            Service\CacheService::class                => Factory\Service\CacheServiceFactory::class,
            Middleware\AuthenticationMiddleware::class => Factory\Middleware\AuthenticationMiddlewareFactory::class,
            Middleware\SecurityMiddleware::class       => Factory\Middleware\SecurityMiddlewareFactory::class,
            Middleware\ValidationMiddleware::class     => Factory\Middleware\ValidationMiddlewareFactory::class,
            Validator\EmailValidator::class            => Factory\Validator\EmailValidatorFactory::class,
            Validator\IdentityValidator::class         => Factory\Validator\IdentityValidatorFactory::class,
            Validator\NameValidator::class             => Factory\Validator\NameValidatorFactory::class,
            Handler\Api\ProfileHandler::class          => Factory\Handler\Api\ProfileHandlerFactory::class,
            Handler\Api\LoginHandler::class            => Factory\Handler\Api\LoginHandlerFactory::class,
            Handler\Api\LogoutHandler::class           => Factory\Handler\Api\LogoutHandlerFactory::class,
            Handler\Api\RegisterHandler::class         => Factory\Handler\Api\RegisterHandlerFactory::class,
            Handler\Api\RefreshHandler::class          => Factory\Handler\Api\RefreshHandlerFactory::class,
            Handler\ErrorHandler::class                => Factory\Handler\ErrorHandlerFactory::class,
        ],
    ],

    'router' => [
        'routes' => [
            'user' => [
                'type'         => Literal::class,
                'options'      => [
                    'route'    => '/user',
                    'defaults' => [],
                ],
                'child_routes' => [
                    'login'    => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/login',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\ValidationMiddleware::class,
                                    Middleware\SecurityMiddleware::class,
                                    Handler\Api\LoginHandler::class
                                ),
                            ],
                        ],
                    ],
                    'logout'   => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/logout',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AuthenticationMiddleware::class,
                                    Middleware\SecurityMiddleware::class,
                                    Handler\Api\LogoutHandler::class
                                ),
                            ],
                        ],
                    ],
                    'register' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/register',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\ValidationMiddleware::class,
                                    Middleware\SecurityMiddleware::class,
                                    Handler\Api\RegisterHandler::class
                                ),
                            ],
                        ],
                    ],
                    'profile'  => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/profile',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AuthenticationMiddleware::class,
                                    Middleware\SecurityMiddleware::class,
                                    Handler\Api\ProfileHandler::class
                                ),
                            ],
                        ],
                    ],
                    'refresh'  => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/refresh',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AuthenticationMiddleware::class,
                                    Middleware\SecurityMiddleware::class,
                                    Handler\Api\RefreshHandler::class
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];