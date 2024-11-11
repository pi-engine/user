<?php

namespace User;

use Laminas\Mvc\Middleware\PipeSpec;
use Laminas\Router\Http\Literal;
use Logger\Middleware\LoggerRequestResponseMiddleware;
use Pi\Core\Middleware\ErrorMiddleware;
use Pi\Core\Middleware\RequestPreparationMiddleware;
use Pi\Core\Middleware\SecurityMiddleware;

return [
    'service_manager' => [
        'aliases'   => [
            Repository\AccountRepositoryInterface::class    => Repository\AccountRepository::class,
            Repository\PermissionRepositoryInterface::class => Repository\PermissionRepository::class,
            Repository\ProfileRepositoryInterface::class    => Repository\ProfileRepository::class,
            Repository\RoleRepositoryInterface::class       => Repository\RoleRepository::class,
        ],
        'factories' => [
            Repository\AccountRepository::class                     => Factory\Repository\AccountRepositoryFactory::class,
            Repository\PermissionRepository::class                  => Factory\Repository\PermissionRepositoryFactory::class,
            Repository\ProfileRepository::class                     => Factory\Repository\ProfileRepositoryFactory::class,
            Repository\RoleRepository::class                        => Factory\Repository\RoleRepositoryFactory::class,
            Middleware\AuthenticationMiddleware::class              => Factory\Middleware\AuthenticationMiddlewareFactory::class,
            Middleware\AuthorizationMiddleware::class               => Factory\Middleware\AuthorizationMiddlewareFactory::class,
            Middleware\AvatarUploadMiddleware::class                => Factory\Middleware\AvatarUploadMiddlewareFactory::class,
            Middleware\ValidationMiddleware::class                  => Factory\Middleware\ValidationMiddlewareFactory::class,
            Validator\EmailValidator::class                         => Factory\Validator\EmailValidatorFactory::class,
            Validator\IdentityValidator::class                      => Factory\Validator\IdentityValidatorFactory::class,
            Validator\NameValidator::class                          => Factory\Validator\NameValidatorFactory::class,
            Validator\MobileValidator::class                        => Factory\Validator\MobileValidatorFactory::class,
            Validator\OtpValidator::class                           => Factory\Validator\OtpValidatorFactory::class,
            Validator\PasswordValidator::class                      => Factory\Validator\PasswordValidatorFactory::class,
            Service\AccountService::class                           => Factory\Service\AccountServiceFactory::class,
            Service\AvatarService::class                            => Factory\Service\AvatarServiceFactory::class,
            Service\TokenService::class                             => Factory\Service\TokenServiceFactory::class,
            Service\RoleService::class                              => Factory\Service\RoleServiceFactory::class,
            Service\PermissionService::class             => Factory\Service\PermissionServiceFactory::class,
            Service\HistoryService::class                => Factory\Service\HistoryServiceFactory::class,
            Service\ExportService::class                 => Factory\Service\ExportServiceFactory::class,
            Handler\Admin\Profile\AddHandler::class      => Factory\Handler\Admin\Profile\AddHandlerFactory::class,
            Handler\Admin\Profile\EditHandler::class     => Factory\Handler\Admin\Profile\EditHandlerFactory::class,
            Handler\Admin\Profile\ListHandler::class     => Factory\Handler\Admin\Profile\ListHandlerFactory::class,
            Handler\Admin\Profile\PasswordHandler::class => Factory\Handler\Admin\Profile\PasswordHandlerFactory::class,
            Handler\Admin\Profile\ViewHandler::class     => Factory\Handler\Admin\Profile\ViewHandlerFactory::class,
            Handler\Admin\Profile\ExportHandler::class   => Factory\Handler\Admin\Profile\ExportHandlerFactory::class,
            Handler\Admin\Profile\CleanHandler::class    => Factory\Handler\Admin\Profile\CleanHandlerFactory::class,
            Handler\Admin\Profile\StatusHandler::class   => Factory\Handler\Admin\Profile\StatusHandlerFactory::class,
            Handler\Admin\Profile\DeleteHandler::class   => Factory\Handler\Admin\Profile\DeleteHandlerFactory::class,
            Handler\Admin\Role\AddHandler::class         => Factory\Handler\Admin\Role\AddHandlerFactory::class,
            Handler\Admin\Role\EditHandler::class                    => Factory\Handler\Admin\Role\EditHandlerFactory::class,
            Handler\Admin\Role\ListHandler::class                    => Factory\Handler\Admin\Role\ListHandlerFactory::class,
            Handler\Admin\Role\DeleteHandler::class                  => Factory\Handler\Admin\Role\DeleteHandlerFactory::class,
            Handler\Admin\Permission\Page\ListHandler::class         => Factory\Handler\Admin\Permission\Page\ListHandlerFactory::class,
            Handler\Admin\Permission\Resource\ListHandler::class     => Factory\Handler\Admin\Permission\Resource\ListHandlerFactory::class,
            Handler\Admin\Permission\Role\ListHandler::class         => Factory\Handler\Admin\Permission\Role\ListHandlerFactory::class,
            Handler\Admin\Cache\ListHandler::class                   => Factory\Handler\Admin\Cache\ListHandlerFactory::class,
            Handler\Admin\Cache\ViewHandler::class                   => Factory\Handler\Admin\Cache\ViewHandlerFactory::class,
            Handler\Admin\Cache\PersistHandler::class                => Factory\Handler\Admin\Cache\PersistHandlerFactory::class,
            Handler\Admin\Cache\DeleteHandler::class                 => Factory\Handler\Admin\Cache\DeleteHandlerFactory::class,
            Handler\Api\Profile\ViewHandler::class                   => Factory\Handler\Api\Profile\ViewHandlerFactory::class,
            Handler\Api\Profile\UpdateHandler::class                 => Factory\Handler\Api\Profile\UpdateHandlerFactory::class,
            Handler\Api\Profile\DeviceTokenHandler::class            => Factory\Handler\Api\Profile\DeviceTokenHandlerFactory::class,
            Handler\Api\Profile\HistoryHandler::class                => Factory\Handler\Api\Profile\HistoryHandlerFactory::class,
            Handler\Api\Password\AddHandler::class                   => Factory\Handler\Api\Password\AddHandlerFactory::class,
            Handler\Api\Password\UpdateHandler::class                => Factory\Handler\Api\Password\UpdateHandlerFactory::class,
            Handler\Api\Authentication\LoginHandler::class           => Factory\Handler\Api\Authentication\LoginHandlerFactory::class,
            Handler\Api\Authentication\LogoutHandler::class          => Factory\Handler\Api\Authentication\LogoutHandlerFactory::class,
            Handler\Api\Authentication\RegisterHandler::class        => Factory\Handler\Api\Authentication\RegisterHandlerFactory::class,
            Handler\Api\Authentication\RefreshHandler::class         => Factory\Handler\Api\Authentication\RefreshHandlerFactory::class,
            Handler\Api\Authentication\Mobile\RequestHandler::class  => Factory\Handler\Api\Authentication\Mobile\RequestHandlerFactory::class,
            Handler\Api\Authentication\Mobile\VerifyHandler::class   => Factory\Handler\Api\Authentication\Mobile\VerifyHandlerFactory::class,
            Handler\Api\Authentication\Email\RequestHandler::class   => Factory\Handler\Api\Authentication\Email\RequestHandlerFactory::class,
            Handler\Api\Authentication\Email\VerifyHandler::class    => Factory\Handler\Api\Authentication\Email\VerifyHandlerFactory::class,
            Handler\Api\Authentication\Mfa\RequestHandler::class     => Factory\Handler\Api\Authentication\Mfa\RequestHandlerFactory::class,
            Handler\Api\Authentication\Mfa\VerifyHandler::class      => Factory\Handler\Api\Authentication\Mfa\VerifyHandlerFactory::class,
            Handler\Api\Authentication\Oauth\GoogleHandler::class    => Factory\Handler\Api\Authentication\Oauth\GoogleHandlerFactory::class,
            Handler\Api\Authentication\Oauth\MicrosoftHandler::class => Factory\Handler\Api\Authentication\Oauth\MicrosoftHandlerFactory::class,
            Handler\Api\Authentication\Oauth\Oauth2Handler::class    => Factory\Handler\Api\Authentication\Oauth\Oauth2HandlerFactory::class,
            Handler\Api\Authentication\Oauth\SettingHandler::class   => Factory\Handler\Api\Authentication\Oauth\SettingHandlerFactory::class,
            Handler\Api\Captcha\ReCaptcha\VerifyHandler::class       => Factory\Handler\Api\Captcha\ReCaptcha\VerifyHandlerFactory::class,
            Handler\Api\Avatar\UploadHandler::class                  => Factory\Handler\Api\Avatar\UploadHandlerFactory::class,
            Handler\InstallerHandler::class                          => Factory\Handler\InstallerHandlerFactory::class,
        ],
    ],
    'router'          => [
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
                                        'title'       => 'User view',
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'view',
                                        'permissions' => 'user-view',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                                        'title'       => 'User update',
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'update',
                                        'permissions' => 'user-update',
                                        'validator'   => 'edit',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                                        'title'       => 'User update device token',
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'device-token',
                                        'permissions' => 'user-update',
                                        'validator'   => 'device-token',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                                        'title'       => 'User history',
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'profile',
                                        'handler'     => 'history',
                                        'permissions' => 'user-view',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\Profile\HistoryHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Api avatar section
                    'avatar'         => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/avatar',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'view' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/upload',
                                    'defaults' => [
                                        'title'       => 'User avatar upload',
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'avatar',
                                        'handler'     => 'upload',
                                        'permissions' => 'user-avatar-upload',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\AvatarUploadMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\Avatar\UploadHandler::class
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
                                        'title'       => 'User password add',
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'password',
                                        'handler'     => 'add',
                                        'permissions' => 'user-password',
                                        'validator'   => 'password-add',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                                        'title'       => 'User password update',
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'password',
                                        'handler'     => 'update',
                                        'permissions' => 'user-password',
                                        'validator'   => 'password-update',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                                        'title'      => 'User login',
                                        'module'     => 'user',
                                        'section'    => 'api',
                                        'package'    => 'authentication',
                                        'handler'    => 'login',
                                        'validator'  => 'login',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            ErrorMiddleware::class,
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
                                        'title'       => 'User logout',
                                        'module'      => 'user',
                                        'section'     => 'api',
                                        'package'     => 'authentication',
                                        'handler'     => 'logout',
                                        'permissions' => 'user-logout',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            //Middleware\AuthorizationMiddleware::class,
                                            ErrorMiddleware::class,
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
                                        'module'     => 'user',
                                        'section'    => 'api',
                                        'package'    => 'authentication',
                                        'handler'    => 'register',
                                        'validator'  => 'add',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\Authentication\RegisterHandler::class
                                        ),
                                    ],
                                ],
                            ],
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
                                                'module'     => 'user',
                                                'section'    => 'api',
                                                'package'    => 'authentication',
                                                'handler'    => 'request',
                                                'validator'  => 'email-request',
                                                'controller' => PipeSpec::class,
                                                'middleware' => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    Middleware\ValidationMiddleware::class,
                                                    ErrorMiddleware::class,
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
                                                'module'     => 'user',
                                                'section'    => 'api',
                                                'package'    => 'authentication',
                                                'handler'    => 'verify',
                                                'validator'  => 'email-verify',
                                                'controller' => PipeSpec::class,
                                                'middleware' => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    Middleware\ValidationMiddleware::class,
                                                    ErrorMiddleware::class,
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
                                                'module'     => 'user',
                                                'section'    => 'api',
                                                'package'    => 'authentication',
                                                'handler'    => 'request',
                                                'validator'  => 'mobile-request',
                                                'controller' => PipeSpec::class,
                                                'middleware' => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    Middleware\ValidationMiddleware::class,
                                                    ErrorMiddleware::class,
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
                                                'module'     => 'user',
                                                'section'    => 'api',
                                                'package'    => 'authentication',
                                                'handler'    => 'verify',
                                                'validator'  => 'mobile-verify',
                                                'controller' => PipeSpec::class,
                                                'middleware' => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    Middleware\ValidationMiddleware::class,
                                                    ErrorMiddleware::class,
                                                    Handler\Api\Authentication\Mobile\VerifyHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'mfa'      => [
                                'type'         => Literal::class,
                                'options'      => [
                                    'route'    => '/mfa',
                                    'defaults' => [],
                                ],
                                'child_routes' => [
                                    'request' => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/request',
                                            'defaults' => [
                                                'module'     => 'user',
                                                'section'    => 'api',
                                                'package'    => 'authentication',
                                                'handler'    => 'request',
                                                'controller' => PipeSpec::class,
                                                'middleware' => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    Middleware\AuthenticationMiddleware::class,
                                                    ErrorMiddleware::class,
                                                    Handler\Api\Authentication\Mfa\RequestHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                    'verify'  => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/verify',
                                            'defaults' => [
                                                'module'     => 'user',
                                                'section'    => 'api',
                                                'package'    => 'authentication',
                                                'handler'    => 'verify',
                                                'controller' => PipeSpec::class,
                                                'middleware' => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    Middleware\AuthenticationMiddleware::class,
                                                    ErrorMiddleware::class,
                                                    Handler\Api\Authentication\Mfa\VerifyHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'oauth'    => [
                                'type'         => Literal::class,
                                'options'      => [
                                    'route'    => '/oauth',
                                    'defaults' => [],
                                ],
                                'child_routes' => [
                                    'google'    => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/google',
                                            'defaults' => [
                                                'module'     => 'user',
                                                'section'    => 'api',
                                                'package'    => 'authentication',
                                                'handler'    => 'google',
                                                'controller' => PipeSpec::class,
                                                'middleware' => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    ErrorMiddleware::class,
                                                    Handler\Api\Authentication\Oauth\GoogleHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                    'microsoft' => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/microsoft',
                                            'defaults' => [
                                                'module'     => 'user',
                                                'section'    => 'api',
                                                'package'    => 'authentication',
                                                'handler'    => 'microsoft',
                                                'controller' => PipeSpec::class,
                                                'middleware' => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    ErrorMiddleware::class,
                                                    Handler\Api\Authentication\Oauth\MicrosoftHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                    'oauth2'    => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/oauth2',
                                            'defaults' => [
                                                'module'     => 'user',
                                                'section'    => 'api',
                                                'package'    => 'authentication',
                                                'handler'    => 'oauth2',
                                                'controller' => PipeSpec::class,
                                                'middleware' => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    ErrorMiddleware::class,
                                                    Handler\Api\Authentication\Oauth\Oauth2Handler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                    'setting'   => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/setting',
                                            'defaults' => [
                                                'module'     => 'user',
                                                'section'    => 'api',
                                                'package'    => 'authentication',
                                                'handler'    => 'oauth',
                                                'controller' => PipeSpec::class,
                                                'middleware' => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    ErrorMiddleware::class,
                                                    Handler\Api\Authentication\Oauth\SettingHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Api captcha section
                    'captcha'        => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/captcha',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'recaptcha' => [
                                'type'         => Literal::class,
                                'options'      => [
                                    'route'    => '/recaptcha',
                                    'defaults' => [],
                                ],
                                'child_routes' => [
                                    'verify' => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/verify',
                                            'defaults' => [
                                                'module'     => 'user',
                                                'section'    => 'api',
                                                'package'    => 'recaptcha',
                                                'handler'    => 'verify',
                                                'controller' => PipeSpec::class,
                                                'middleware' => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    ErrorMiddleware::class,
                                                    Handler\Api\Captcha\ReCaptcha\VerifyHandler::class
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
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            Middleware\ValidationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Admin\Profile\PasswordHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'delete'   => [
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
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Admin\Profile\ViewHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'export'   => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/export',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'profile',
                                        'handler'     => 'export',
                                        'permissions' => 'user-profile-export',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Admin\Profile\ExportHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'clean'    => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/clean',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'profile',
                                        'handler'     => 'clean',
                                        'permissions' => 'user-profile-clean',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Admin\Profile\CleanHandler::class
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
                            'list'   => [
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
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Admin\Role\ListHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'add'    => [
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
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Admin\Role\AddHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'edit'   => [
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
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                                        'handler'     => 'delete',
                                        'permissions' => 'user-role-delete',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
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
                            'resource' => [
                                'type'         => Literal::class,
                                'options'      => [
                                    'route'    => '/resource',
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
                                                'package'     => 'permission-resource',
                                                'handler'     => 'list',
                                                'permissions' => 'user-permission-resource-list',
                                                'controller'  => PipeSpec::class,
                                                'middleware'  => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    Middleware\AuthenticationMiddleware::class,
                                                    Middleware\AuthorizationMiddleware::class,
                                                    LoggerRequestResponseMiddleware::class,
                                                    ErrorMiddleware::class,
                                                    Handler\Admin\Permission\Resource\ListHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'page'     => [
                                'type'         => Literal::class,
                                'options'      => [
                                    'route'    => '/page',
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
                                                'package'     => 'permission-page',
                                                'handler'     => 'list',
                                                'permissions' => 'user-permission-page-list',
                                                'controller'  => PipeSpec::class,
                                                'middleware'  => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    Middleware\AuthenticationMiddleware::class,
                                                    Middleware\AuthorizationMiddleware::class,
                                                    LoggerRequestResponseMiddleware::class,
                                                    ErrorMiddleware::class,
                                                    Handler\Admin\Permission\Page\ListHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'role'     => [
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
                                                'package'     => 'permission-role',
                                                'handler'     => 'list',
                                                'permissions' => 'user-permission-role-list',
                                                'controller'  => PipeSpec::class,
                                                'middleware'  => new PipeSpec(
                                                    RequestPreparationMiddleware::class,
                                                    SecurityMiddleware::class,
                                                    Middleware\AuthenticationMiddleware::class,
                                                    Middleware\AuthorizationMiddleware::class,
                                                    LoggerRequestResponseMiddleware::class,
                                                    ErrorMiddleware::class,
                                                    Handler\Admin\Permission\Role\ListHandler::class
                                                ),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Admin cache section
                    'cache'      => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/cache',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'list'    => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/list',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'cache',
                                        'handler'     => 'list',
                                        'permissions' => 'user-cache-list',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Admin\Cache\ListHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'view'    => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/view',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'cache',
                                        'handler'     => 'view',
                                        'permissions' => 'user-cache-view',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Admin\Cache\ViewHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'persist' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/persist',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'cache',
                                        'handler'     => 'persist',
                                        'permissions' => 'user-cache-persist',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Admin\Cache\PersistHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'delete'  => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/delete',
                                    'defaults' => [
                                        'module'      => 'user',
                                        'section'     => 'admin',
                                        'package'     => 'cache',
                                        'handler'     => 'delete',
                                        'permissions' => 'user-cache-delete',
                                        'controller'  => PipeSpec::class,
                                        'middleware'  => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Admin\Cache\DeleteHandler::class
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
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    Middleware\AuthenticationMiddleware::class,
                                    InstallerMiddleware::class,
                                    ErrorMiddleware::class,
                                    Handler\InstallerHandler::class
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'view_manager'    => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];