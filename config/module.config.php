<?php

namespace User;

use Laminas\Mvc\Middleware\PipeSpec;
use Laminas\Router\Http\Literal;

return [
    'service_manager' => [
        'aliases'   => [
            Repository\AccountRepositoryInterface::class    => Repository\AccountRepository::class,
            Repository\PermissionRepositoryInterface::class => Repository\PermissionRepository::class,
            Repository\ProfileRepositoryInterface::class    => Repository\ProfileRepository::class,
            Repository\RoleRepositoryInterface::class       => Repository\RoleRepository::class,
        ],
        'factories' => [
            Installer\Install::class                                => Factory\Installer\InstallFactory::class,
            Installer\Update::class                                 => Factory\Installer\UpdateFactory::class,
            Installer\Remove::class                                 => Factory\Installer\RemoveFactory::class,
            Repository\AccountRepository::class                     => Factory\Repository\AccountRepositoryFactory::class,
            Repository\PermissionRepository::class                  => Factory\Repository\PermissionRepositoryFactory::class,
            Repository\ProfileRepository::class                     => Factory\Repository\ProfileRepositoryFactory::class,
            Repository\RoleRepository::class                        => Factory\Repository\RoleRepositoryFactory::class,
            Service\AccountService::class                           => Factory\Service\AccountServiceFactory::class,
            Service\TokenService::class                             => Factory\Service\TokenServiceFactory::class,
            Service\CacheService::class                             => Factory\Service\CacheServiceFactory::class,
            Service\RoleService::class                              => Factory\Service\RoleServiceFactory::class,
            Service\PermissionService::class                        => Factory\Service\PermissionServiceFactory::class,
            Service\UtilityService::class                           => Factory\Service\UtilityServiceFactory::class,
            Service\HistoryService::class                           => Factory\Service\HistoryServiceFactory::class,
            Service\InstallerService::class                         => Factory\Service\InstallerServiceFactory::class,
            Middleware\AuthenticationMiddleware::class              => Factory\Middleware\AuthenticationMiddlewareFactory::class,
            Middleware\AuthorizationMiddleware::class               => Factory\Middleware\AuthorizationMiddlewareFactory::class,
            Middleware\SecurityMiddleware::class                    => Factory\Middleware\SecurityMiddlewareFactory::class,
            Middleware\ValidationMiddleware::class                  => Factory\Middleware\ValidationMiddlewareFactory::class,
            Middleware\InstallerMiddleware::class                   => Factory\Middleware\InstallerMiddlewareFactory::class,
            Validator\EmailValidator::class                         => Factory\Validator\EmailValidatorFactory::class,
            Validator\IdentityValidator::class                      => Factory\Validator\IdentityValidatorFactory::class,
            Validator\NameValidator::class                          => Factory\Validator\NameValidatorFactory::class,
            Validator\MobileValidator::class                        => Factory\Validator\MobileValidatorFactory::class,
            Validator\OtpValidator::class                           => Factory\Validator\OtpValidatorFactory::class,
            Validator\PasswordValidator::class                      => Factory\Validator\PasswordValidatorFactory::class,
            Handler\Admin\Profile\AddHandler::class                 => Factory\Handler\Admin\Profile\AddHandlerFactory::class,
            Handler\Admin\Profile\EditHandler::class                => Factory\Handler\Admin\Profile\EditHandlerFactory::class,
            Handler\Admin\Profile\ListHandler::class                => Factory\Handler\Admin\Profile\ListHandlerFactory::class,
            Handler\Admin\Profile\PasswordHandler::class            => Factory\Handler\Admin\Profile\PasswordHandlerFactory::class,
            Handler\Admin\Profile\ViewHandler::class                => Factory\Handler\Admin\Profile\ViewHandlerFactory::class,
            Handler\Admin\Role\AddHandler::class                    => Factory\Handler\Admin\Role\AddHandlerFactory::class,
            Handler\Admin\Role\EditHandler::class                   => Factory\Handler\Admin\Role\EditHandlerFactory::class,
            Handler\Admin\Role\ListHandler::class                   => Factory\Handler\Admin\Role\ListHandlerFactory::class,
            Handler\Admin\Role\DeleteHandler::class                   => Factory\Handler\Admin\Role\DeleteHandlerFactory::class,
            Handler\Admin\Permission\ListHandler::class             => Factory\Handler\Admin\Permission\ListHandlerFactory::class,
            Handler\Admin\Permission\AccessHandler::class           => Factory\Handler\Admin\Permission\AccessHandlerFactory::class,
            Handler\Admin\Permission\ViewHandler::class             => Factory\Handler\Admin\Permission\ViewHandlerFactory::class,
            Handler\Api\Profile\ViewHandler::class                  => Factory\Handler\Api\Profile\ViewHandlerFactory::class,
            Handler\Api\Profile\UpdateHandler::class                => Factory\Handler\Api\Profile\UpdateHandlerFactory::class,
            Handler\Api\Profile\DeviceTokenHandler::class           => Factory\Handler\Api\Profile\DeviceTokenHandlerFactory::class,
            Handler\Api\Profile\HistoryHandler::class               => Factory\Handler\Api\Profile\HistoryHandlerFactory::class,
            Handler\Api\Password\AddHandler::class                  => Factory\Handler\Api\Password\AddHandlerFactory::class,
            Handler\Api\Password\UpdateHandler::class               => Factory\Handler\Api\Password\UpdateHandlerFactory::class,
            Handler\Api\Authentication\LoginHandler::class          => Factory\Handler\Api\Authentication\LoginHandlerFactory::class,
            Handler\Api\Authentication\LogoutHandler::class         => Factory\Handler\Api\Authentication\LogoutHandlerFactory::class,
            Handler\Api\Authentication\RegisterHandler::class       => Factory\Handler\Api\Authentication\RegisterHandlerFactory::class,
            Handler\Api\Authentication\RefreshHandler::class        => Factory\Handler\Api\Authentication\RefreshHandlerFactory::class,
            Handler\Api\Authentication\Mobile\RequestHandler::class => Factory\Handler\Api\Authentication\Mobile\RequestHandlerFactory::class,
            Handler\Api\Authentication\Mobile\VerifyHandler::class  => Factory\Handler\Api\Authentication\Mobile\VerifyHandlerFactory::class,
            Handler\Api\Authentication\Email\RequestHandler::class  => Factory\Handler\Api\Authentication\Email\RequestHandlerFactory::class,
            Handler\Api\Authentication\Email\VerifyHandler::class   => Factory\Handler\Api\Authentication\Email\VerifyHandlerFactory::class,
            Handler\ErrorHandler::class                             => Factory\Handler\ErrorHandlerFactory::class,
            Handler\InstallerHandler::class                         => Factory\Handler\InstallerHandlerFactory::class,

            'translator' => Factory\Service\TranslatorFactory::class,
        ],
    ],

    'router' => [
        'routes' => [
            // Api section
            'api_user'   => [
                'type'         => Literal::class,
                'options'      => [
                    'route'    => '/user',
                    'defaults' => [],
                ],
                'child_routes' => [

                    // Api profile section
                    'profile'        => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/profile',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'view'         => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/view',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'view',
                                        'permissions' => 'user-view',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Api\Profile\ViewHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'edit'         => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/update',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'update',
                                        'permissions' => 'user-update',
                                        'validator'   => 'edit',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            Handler\Api\Profile\UpdateHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'device-token' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/device-token',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'device-token',
                                        'permissions' => 'user-update',
                                        'validator'   => 'device-token',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            Handler\Api\Profile\DeviceTokenHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'history'      => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/history',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'history',
                                        'permissions' => 'user-view',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Handler\Api\Profile\HistoryHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],

                    // Api profile section
                    'password'       => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/password',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'add'    => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'password',
                                        'handler'     => 'add',
                                        'permissions' => 'user-password',
                                        'validator'   => 'password-add',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            Handler\Api\Password\AddHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'update' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/update',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'password',
                                        'handler'     => 'update',
                                        'permissions' => 'user-password',
                                        'validator'   => 'password-update',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            Handler\Api\Password\UpdateHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],

                    // Api Authentication section
                    'authentication' => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/authentication',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'login'    => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/login',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'login',
                                        'permissions' => 'user-login',
                                        'validator'   => 'login',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            Handler\Api\Authentication\LoginHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'logout'   => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/logout',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'logout',
                                        'permissions' => 'user-logout',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Api\Authentication\LogoutHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'register' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/register',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'register',
                                        'permissions' => 'user-register',
                                        'validator'   => 'add',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            Handler\Api\Authentication\RegisterHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            /* 'refresh'  => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/refresh',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'refresh',
                                        'permissions' => 'user-refresh',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Api\Authentication\RefreshHandler::class
                                        ),
                                    ],
                                ],
                            ], */
                            'email'    => [
                                'type'         => Literal::class,
                                'options'      => [
                                    'route'    => '/email',
                                    'defaults' => [],
                                ],
                                'child_routes' => [
                                    'request' => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/request',
                                            'defaults' => [
                                                'module'      => 'user',
                                                'section'     => 'api',
                                                'package'     => 'profile',
                                                'handler'     => 'request',
                                                'permissions' => 'user-email-request',
                                                'validator'   => 'email-request',
                                                'controller'  => PipeSpec::class,
                                                'middleware'  => new PipeSpec(
                                                    Middleware\SecurityMiddleware::class,
                                                    Middleware\ValidationMiddleware::class,
                                                    Handler\Api\Authentication\Email\RequestHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                    'verify'  => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/verify',
                                            'defaults' => [
                                                'module'      => 'user',
                                                'section'     => 'api',
                                                'package'     => 'profile',
                                                'handler'     => 'verify',
                                                'permissions' => 'user-email-verify',
                                                'validator'   => 'email-verify',
                                                'controller'  => PipeSpec::class,
                                                'middleware'  => new PipeSpec(
                                                    Middleware\SecurityMiddleware::class,
                                                    Middleware\ValidationMiddleware::class,
                                                    Handler\Api\Authentication\Email\VerifyHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'mobile'   => [
                                'type'         => Literal::class,
                                'options'      => [
                                    'route'    => '/mobile',
                                    'defaults' => [],
                                ],
                                'child_routes' => [
                                    'request' => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/request',
                                            'defaults' => [
                                                'module'      => 'user',
                                                'section'     => 'api',
                                                'package'     => 'profile',
                                                'handler'     => 'request',
                                                'permissions' => 'user-mobile-request',
                                                'validator'   => 'mobile-request',
                                                'controller'  => PipeSpec::class,
                                                'middleware'  => new PipeSpec(
                                                    Middleware\SecurityMiddleware::class,
                                                    Middleware\ValidationMiddleware::class,
                                                    Handler\Api\Authentication\Mobile\RequestHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                    'verify'  => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/verify',
                                            'defaults' => [
                                                'module'      => 'user',
                                                'section'     => 'api',
                                                'package'     => 'profile',
                                                'handler'     => 'verify',
                                                'permissions' => 'user-mobile-verify',
                                                'validator'   => 'mobile-verify',
                                                'controller'  => PipeSpec::class,
                                                'middleware'  => new PipeSpec(
                                                    Middleware\SecurityMiddleware::class,
                                                    Middleware\ValidationMiddleware::class,
                                                    Handler\Api\Authentication\Mobile\VerifyHandler::class
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
            // Admin section
            'admin_user' => [
                'type'         => Literal::class,
                'options'      => [
                    'route'    => '/admin/user',
                    'defaults' => [],
                ],
                'child_routes' => [
                    // Admin profile section
                    'profile'    => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/profile',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'list'     => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/list',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'profile',
                                        'handler'     => 'list',
                                        'permissions' => 'user-profile-list',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Admin\Profile\ListHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'add'      => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'profile',
                                        'handler'     => 'add',
                                        'permissions' => 'user-profile-add',
                                        'validator'   => 'add',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            Handler\Admin\Profile\AddHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'edit'     => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/edit',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'profile',
                                        'handler'     => 'edit',
                                        'permissions' => 'user-profile-edit',
                                        'validator'   => 'edit',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            Handler\Admin\Profile\EditHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'status'   => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/status',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'profile',
                                        'handler'     => 'edit',
                                        'permissions' => 'user-profile-edit',
                                        'validator'   => 'edit',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            Handler\Admin\Profile\StatusHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'password' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/password',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'profile',
                                        'handler'     => 'password',
                                        'permissions' => 'user-profile-password',
                                        'validator'   => 'password-admin',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            Handler\Admin\Profile\PasswordHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/delete',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'profile',
                                        'handler'     => 'password',
                                        'permissions' => 'user-profile-password',
                                        'validator'   => 'password-admin',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Admin\Profile\DeleteHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'view'     => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/view',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'profile',
                                        'handler'     => 'view',
                                        'permissions' => 'user-profile-view',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Admin\Profile\ViewHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Admin role section
                    'role'       => [
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
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'role',
                                        'handler'     => 'list',
                                        'permissions' => 'user-role-list',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Admin\Role\ListHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'add'  => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'role',
                                        'handler'     => 'add',
                                        'permissions' => 'user-role-add',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
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
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'role',
                                        'handler'     => 'edit',
                                        'permissions' => 'user-role-edit',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Admin\Role\EditHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/delete',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'role',
                                        'handler'     => 'edit',
                                        'permissions' => 'user-role-edit',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Admin\Role\DeleteHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Admin permission section
                    'permission' => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/permission',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'list'   => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/list',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'permission',
                                        'handler'     => 'list',
                                        'permissions' => 'user-permission-list',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Admin\Permission\ListHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'view'   => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/view',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'permission',
                                        'handler'     => 'view',
                                        'permissions' => 'user-permission-view',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Admin\Permission\ViewHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'access' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/access',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'permission',
                                        'handler'     => 'access',
                                        'permissions' => 'user-permission-access',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            Middleware\SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Handler\Admin\Permission\AccessHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Admin installer
                    'installer'  => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/installer',
                            'defaults' => [
                                'module'     => 'user',
                                'section'    => 'admin',
                                'package'    => 'installer',
                                'handler'    => 'installer',
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\AuthenticationMiddleware::class,
                                    Middleware\InstallerMiddleware::class,
                                    Handler\InstallerHandler::class
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