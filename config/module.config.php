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
            Repository\AccountRepository::class          => Factory\Repository\AccountRepositoryFactory::class,
            Service\AccountService::class                => Factory\Service\AccountServiceFactory::class,
            Service\TokenService::class                  => Factory\Service\TokenServiceFactory::class,
            Service\CacheService::class                  => Factory\Service\CacheServiceFactory::class,
            Middleware\AuthenticationMiddleware::class   => Factory\Middleware\AuthenticationMiddlewareFactory::class,
            Middleware\SecurityMiddleware::class         => Factory\Middleware\SecurityMiddlewareFactory::class,
            Middleware\ValidationMiddleware::class       => Factory\Middleware\ValidationMiddlewareFactory::class,
            Middleware\AdminMiddleware::class            => Factory\Middleware\AdminMiddlewareFactory::class,
            Validator\EmailValidator::class              => Factory\Validator\EmailValidatorFactory::class,
            Validator\IdentityValidator::class           => Factory\Validator\IdentityValidatorFactory::class,
            Validator\NameValidator::class               => Factory\Validator\NameValidatorFactory::class,
            Handler\Admin\Profile\AddHandler::class      => Factory\Handler\Admin\Profile\AddHandlerFactory::class,
            Handler\Admin\Profile\EditHandler::class     => Factory\Handler\Admin\Profile\EditHandlerFactory::class,
            Handler\Admin\Profile\ListHandler::class     => Factory\Handler\Admin\Profile\ListHandlerFactory::class,
            Handler\Admin\Profile\PasswordHandler::class => Factory\Handler\Admin\Profile\PasswordHandlerFactory::class,
            Handler\Admin\Profile\ViewHandler::class     => Factory\Handler\Admin\Profile\ViewHandlerFactory::class,
            Handler\Admin\Role\AddHandler::class         => Factory\Handler\Admin\Role\AddHandlerFactory::class,
            Handler\Admin\Role\EditHandler::class        => Factory\Handler\Admin\Role\EditHandlerFactory::class,
            Handler\Admin\Role\ListHandler::class        => Factory\Handler\Admin\Role\ListHandlerFactory::class,
            Handler\Api\ProfileHandler::class            => Factory\Handler\Api\ProfileHandlerFactory::class,
            Handler\Api\LoginHandler::class              => Factory\Handler\Api\LoginHandlerFactory::class,
            Handler\Api\LogoutHandler::class             => Factory\Handler\Api\LogoutHandlerFactory::class,
            Handler\Api\RegisterHandler::class           => Factory\Handler\Api\RegisterHandlerFactory::class,
            Handler\Api\RefreshHandler::class            => Factory\Handler\Api\RefreshHandlerFactory::class,
            Handler\Api\PasswordHandler::class           => Factory\Handler\Api\PasswordHandlerFactory::class,
            Handler\Api\UpdateHandler::class             => Factory\Handler\Api\UpdateHandlerFactory::class,
            Handler\ErrorHandler::class                  => Factory\Handler\ErrorHandlerFactory::class,
        ],
    ],

    'router' => [
        'routes' => [
            // Api section
            'api'   => [
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
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\ValidationMiddleware::class,
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
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\AuthenticationMiddleware::class,
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
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\ValidationMiddleware::class,
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
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\AuthenticationMiddleware::class,
                                    Handler\Api\ProfileHandler::class
                                ),
                            ],
                        ],
                    ],
                    'edit'     => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/update',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\AuthenticationMiddleware::class,
                                    Middleware\ValidationMiddleware::class,
                                    Handler\Api\UpdateHandler::class
                                ),
                            ],
                        ],
                    ],
                    'password' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/password',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\AuthenticationMiddleware::class,
                                    Middleware\ValidationMiddleware::class,
                                    Handler\Api\PasswordHandler::class
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
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\AuthenticationMiddleware::class,
                                    Handler\Api\RefreshHandler::class
                                ),
                            ],
                        ],
                    ],
                ],
            ],
            // Admin section
            'admin' => [
                'type'         => Literal::class,
                'options'      => [
                    'route'    => '/admin/user',
                    'defaults' => [],
                ],
                'child_routes' => [
                    // Admin profile section
                    'profile' => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/profile',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'list' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/list',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AdminMiddleware::class,
                                            Handler\Admin\Profile\ListHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'add' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AdminMiddleware::class,
                                            Handler\Admin\Profile\AddHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/edit',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AdminMiddleware::class,
                                            Handler\Admin\Profile\EditHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'password' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/password',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AdminMiddleware::class,
                                            Handler\Admin\Profile\PasswordHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'view' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/view',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AdminMiddleware::class,
                                            Handler\Admin\Profile\ViewHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Admin role section
                    'role' => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/role',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'list' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/list',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AdminMiddleware::class,
                                            Handler\Admin\Role\ListHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'add' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AdminMiddleware::class,
                                            Handler\Admin\Role\AddHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/edit',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AdminMiddleware::class,
                                            Handler\Admin\Role\EditHandler::class
                                        ),
                                    ],
                                ],
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