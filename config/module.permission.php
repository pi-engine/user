<?php

return [
    'admin' => [
        [
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'profile',
            'handler'     => 'list',
            'permissions' => 'user-profile-list',
            'role'        => [
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'profile',
            'handler'     => 'add',
            'permissions' => 'user-profile-add',
            'validation'  => 'add',
            'role'        => [
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'profile',
            'handler'     => 'edit',
            'permissions' => 'user-profile-edit',
            'validation'  => 'edit',
            'role'        => [
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'profile',
            'handler'     => 'password',
            'permissions' => 'user-profile-password',
            'validation'  => 'password',
            'role'        => [
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'profile',
            'handler'     => 'view',
            'permissions' => 'user-profile-view',
            'role'        => [
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'role',
            'handler'     => 'list',
            'permissions' => 'user-role-list',
            'role'        => [
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'role',
            'handler'     => 'add',
            'permissions' => 'user-role-add',
            'role'        => [
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'role',
            'handler'     => 'edit',
            'permissions' => 'user-role-edit',
            'role'        => [
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'permission',
            'handler'     => 'list',
            'permissions' => 'user-permission-list',
            'role'        => [
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'permission',
            'handler'     => 'view',
            'permissions' => 'user-permission-view',
            'role'        => [
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'permission',
            'handler'     => 'access',
            'permissions' => 'user-permission-access',
            'role'        => [
                'admin',
            ],
        ],
    ],
    'api'   => [
        [
            'module'      => 'user',
            'section'     => 'api',
            'package'     => 'profile',
            'handler'     => 'refresh',
            'permissions' => 'user-refresh',
            'role'        => [
                'member',
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'api',
            'package'     => 'profile',
            'handler'     => 'logout',
            'permissions' => 'user-logout',
            'role'        => [
                'member',
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'api',
            'package'     => 'profile',
            'handler'     => 'view',
            'permissions' => 'user-view',
            'role'        => [
                'member',
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'api',
            'package'     => 'profile',
            'handler'     => 'update',
            'permissions' => 'user-update',
            'role'        => [
                'member',
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'api',
            'package'     => 'profile',
            'handler'     => 'device-token',
            'permissions' => 'user-update',
            'role'        => [
                'member',
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'api',
            'package'     => 'password',
            'handler'     => 'add',
            'permissions' => 'user-password-add',
            'role'        => [
                'member',
                'admin',
            ],
        ],
        [
            'module'      => 'user',
            'section'     => 'api',
            'package'     => 'password',
            'handler'     => 'update',
            'permissions' => 'user-password-update',
            'role'        => [
                'member',
                'admin',
            ],
        ],
    ],
];