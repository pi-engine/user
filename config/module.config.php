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
            Installer\Install::class                      => Factory\Installer\InstallFactory::class,
            Installer\Update::class                       => Factory\Installer\UpdateFactory::class,
            Installer\Remove::class                       => Factory\Installer\RemoveFactory::class,
            Repository\AccountRepository::class           => Factory\Repository\AccountRepositoryFactory::class,
            Repository\PermissionRepository::class        => Factory\Repository\PermissionRepositoryFactory::class,
            Repository\ProfileRepository::class           => Factory\Repository\ProfileRepositoryFactory::class,
            Repository\RoleRepository::class              => Factory\Repository\RoleRepositoryFactory::class,
            Service\AccountService::class                 => Factory\Service\AccountServiceFactory::class,
            Service\TokenService::class                   => Factory\Service\TokenServiceFactory::class,
            Service\CacheService::class                   => Factory\Service\CacheServiceFactory::class,
            Service\RoleService::class                    => Factory\Service\RoleServiceFactory::class,
            Service\PermissionService::class              => Factory\Service\PermissionServiceFactory::class,
            Service\UtilityService::class                 => Factory\Service\UtilityServiceFactory::class,
            Service\InstallerService::class               => Factory\Service\InstallerServiceFactory::class,
            Middleware\AuthenticationMiddleware::class    => Factory\Middleware\AuthenticationMiddlewareFactory::class,
            Middleware\AuthorizationMiddleware::class     => Factory\Middleware\AuthorizationMiddlewareFactory::class,
            Middleware\SecurityMiddleware::class          => Factory\Middleware\SecurityMiddlewareFactory::class,
            Middleware\ValidationMiddleware::class        => Factory\Middleware\ValidationMiddlewareFactory::class,
            Validator\EmailValidator::class               => Factory\Validator\EmailValidatorFactory::class,
            Validator\IdentityValidator::class            => Factory\Validator\IdentityValidatorFactory::class,
            Validator\NameValidator::class                => Factory\Validator\NameValidatorFactory::class,
            Validator\MobileValidator::class              => Factory\Validator\MobileValidatorFactory::class,
            Validator\OtpValidator::class                 => Factory\Validator\OtpValidatorFactory::class,
            Handler\Admin\Profile\AddHandler::class       => Factory\Handler\Admin\Profile\AddHandlerFactory::class,
            Handler\Admin\Profile\EditHandler::class      => Factory\Handler\Admin\Profile\EditHandlerFactory::class,
            Handler\Admin\Profile\ListHandler::class      => Factory\Handler\Admin\Profile\ListHandlerFactory::class,
            Handler\Admin\Profile\PasswordHandler::class  => Factory\Handler\Admin\Profile\PasswordHandlerFactory::class,
            Handler\Admin\Profile\ViewHandler::class      => Factory\Handler\Admin\Profile\ViewHandlerFactory::class,
            Handler\Admin\Role\AddHandler::class          => Factory\Handler\Admin\Role\AddHandlerFactory::class,
            Handler\Admin\Role\EditHandler::class         => Factory\Handler\Admin\Role\EditHandlerFactory::class,
            Handler\Admin\Role\ListHandler::class         => Factory\Handler\Admin\Role\ListHandlerFactory::class,
            Handler\Admin\Permission\ListHandler::class   => Factory\Handler\Admin\Permission\ListHandlerFactory::class,
            Handler\Admin\Permission\AccessHandler::class => Factory\Handler\Admin\Permission\AccessHandlerFactory::class,
            Handler\Admin\Permission\ViewHandler::class   => Factory\Handler\Admin\Permission\ViewHandlerFactory::class,
            Handler\Api\ProfileHandler::class             => Factory\Handler\Api\ProfileHandlerFactory::class,
            Handler\Api\LoginHandler::class               => Factory\Handler\Api\LoginHandlerFactory::class,
            Handler\Api\LogoutHandler::class              => Factory\Handler\Api\LogoutHandlerFactory::class,
            Handler\Api\RegisterHandler::class            => Factory\Handler\Api\RegisterHandlerFactory::class,
            Handler\Api\RefreshHandler::class             => Factory\Handler\Api\RefreshHandlerFactory::class,
            Handler\Api\PasswordHandler::class            => Factory\Handler\Api\PasswordHandlerFactory::class,
            Handler\Api\UpdateHandler::class              => Factory\Handler\Api\UpdateHandlerFactory::class,
            Handler\Api\MobileHandler::class              => Factory\Handler\Api\MobileHandlerFactory::class,
            Handler\Api\VerifyHandler::class              => Factory\Handler\Api\VerifyHandlerFactory::class,
            Handler\ErrorHandler::class                   => Factory\Handler\ErrorHandlerFactory::class,
            Handler\InstallerHandler::class               => Factory\Handler\InstallerHandlerFactory::class,
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
                                    Handler\Api\RegisterHandler::class
                                ),
                            ],
                        ],
                    ],
                    'mobile'   => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/mobile',
                            'defaults' => [
                                'module'      => 'user',
                                'section'     => 'api',
                                'package'     => 'profile',
                                'handler'     => 'mobile',
                                'permissions' => 'user-mobile',
                                'validator'   => 'mobile',
                                'controller'  => PipeSpec::class,
                                'middleware'  => new PipeSpec(
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\ValidationMiddleware::class,
                                    Handler\Api\MobileHandler::class
                                ),
                            ],
                        ],
                    ],
                    'verify'   => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/verify',
                            'defaults' => [
                                'module'      => 'user',
                                'section'     => 'api',
                                'package'     => 'profile',
                                'handler'     => 'verify',
                                'permissions' => 'user-verify',
                                'validator'   => 'verify',
                                'controller'  => PipeSpec::class,
                                'middleware'  => new PipeSpec(
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\ValidationMiddleware::class,
                                    Handler\Api\VerifyHandler::class
                                ),
                            ],
                        ],
                    ],
                    'profile'  => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/profile',
                            'defaults' => [
                                'module'      => 'user',
                                'section'     => 'api',
                                'package'     => 'profile',
                                'handler'     => 'profile',
                                'permissions' => 'user-profile',
                                'controller'  => PipeSpec::class,
                                'middleware'  => new PipeSpec(
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\AuthenticationMiddleware::class,
                                    Middleware\AuthorizationMiddleware::class,
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
                                'module'      => 'user',
                                'section'     => 'api',
                                'package'     => 'profile',
                                'handler'     => 'password',
                                'permissions' => 'user-password',
                                'validator'   => 'password',
                                'controller'  => PipeSpec::class,
                                'middleware'  => new PipeSpec(
                                    Middleware\SecurityMiddleware::class,
                                    Middleware\AuthenticationMiddleware::class,
                                    Middleware\AuthorizationMiddleware::class,
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
                                    Handler\Api\RefreshHandler::class
                                ),
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
                                        'validator'   => 'password',
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