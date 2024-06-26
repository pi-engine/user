<?php

return [
    'admin' => [
        [
            'title'       => 'Admin user profile list',
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
            'title'       => 'Admin user profile add',
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
            'title'       => 'Admin user profile edit',
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
            'title'       => 'Admin user profile password',
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
            'title'       => 'Admin user profile view',
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
            'title'       => 'Admin user profile export',
            'module'      => 'user',
            'section'     => 'admin',
            'package'     => 'profile',
            'handler'     => 'view',
            'permissions' => 'user-profile-export',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin user role list',
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
            'title'       => 'Admin user role add',
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
            'title'       => 'Admin user role edit',
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
            'title'       => 'Admin user permission list',
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
            'title'       => 'Admin user permission view',
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
            'title'       => 'Admin user permission access',
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
            'title'       => 'User view',
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
            'title'       => 'User update',
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
            'title'       => 'User update device token',
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
            'title'       => 'User password add',
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
            'title'       => 'User password update',
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
        [
            'title'       => 'User refresh',
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
            'title'       => 'User logout',
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
            'title'       => 'User avatar upload',
            'module'      => 'user',
            'section'     => 'api',
            'package'     => 'avatar',
            'handler'     => 'upload',
            'permissions' => 'user-avatar-upload',
            'role'        => [
                'member',
                'admin',
            ],
        ],
    ],
];