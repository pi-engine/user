<?php

namespace User\Service;

use Laminas\Crypt\Password\Bcrypt;
use Psr\SimpleCache\InvalidArgumentException;
use User\Repository\AccountRepositoryInterface;

class AccountService implements ServiceInterface
{
    /* @var AccountRepositoryInterface */
    protected AccountRepositoryInterface $accountRepository;

    /* @var RoleService */
    protected RoleService $roleService;

    /* @var TokenService */
    protected TokenService $tokenService;

    /* @var CacheService */
    protected CacheService $cacheService;

    /**
     * @param AccountRepositoryInterface $accountRepository
     * @param TokenService               $tokenService
     * @param CacheService               $cacheService
     */
    public function __construct(
        AccountRepositoryInterface $accountRepository,
        RoleService $roleService,
        TokenService $tokenService,
        CacheService $cacheService
    ) {
        $this->accountRepository = $accountRepository;
        $this->roleService       = $roleService;
        $this->tokenService      = $tokenService;
        $this->cacheService      = $cacheService;
    }

    /**
     * @param $params
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function login($params): array
    {
        // Do log in
        $authentication = $this->accountRepository->authentication();
        $adapter        = $authentication->getAdapter();
        $adapter->setIdentity($params['identity'])->setCredential($params['credential']);

        // Check login
        if ($authentication->authenticate()->isValid()) {
            // Get user account
            $account = (array)$adapter->getResultRowObject(
                [
                    'id',
                    'name',
                    'email',
                    'identity',
                    'status',
                ]
            );

            // Canonize account
            $account = $this->canonizeAccount($account);

            // Get roles
            $account['roles'] = $this->roleService->getRoleAccount($account['id']);

            // Generate access token
            $accessToken = $this->tokenService->generate(
                [
                    'user_id' => $account['id'],
                    'type'    => 'access',
                    'roles'   => $account['roles'],
                ]
            );

            // Generate refresh token
            $refreshToken = $this->tokenService->generate(
                [
                    'user_id' => $account['id'],
                    'type'    => 'refresh',
                    'roles'   => $account['roles'],
                ]
            );

            // Set extra info
            $account['last_login']    = time();
            $account['access_token']  = $accessToken['token'];
            $account['refresh_token'] = $refreshToken['token'];

            // Get from cache if exist
            $user = $this->cacheService->getUser($account['id']);

            // Set/Update user data to cache
            $this->cacheService->setUser($account['id'], [
                'account'      => [
                    'id'         => $account['id'],
                    'name'       => $account['name'],
                    'email'      => $account['email'],
                    'identity'   => $account['identity'],
                    'last_login' => $account['last_login'],
                ],
                'roles'        => $account['roles'],
                'access_keys'  => (isset($user['access_keys']) && !empty($user['access_keys']))
                    ? array_unique(array_merge($user['access_keys'], [$accessToken['key']]))
                    : [$accessToken['key']],
                'refresh_keys' => (isset($user['refresh_keys']) && !empty($user['refresh_keys']))
                    ? array_unique(array_merge($user['refresh_keys'], [$refreshToken['key']]))
                    : [$refreshToken['key']],
            ]);

            $result = [
                'result' => true,
                'data'   => $account,
                'error'  => '',
            ];
        } else {
            $result = [
                'result' => false,
                'data'   => [],
                'error'  => 'error in login',
            ];
        }

        return $result;
    }

    public function logout($params): array
    {
        if (isset($params['all_session']) && (int)$params['all_session'] == 1) {
            $this->cacheService->deleteUser($params['user_id']);
            $message = 'You are logout successfully from all of your sessions !';
        } else {
            $this->cacheService->removeItem($params['user_id'], 'access_keys', $params['token_id']);
            $message = 'You are logout successfully from this session !';
        }

        return [
            'result' => true,
            'data'   => [
                'message' => $message,
            ],
            'error'  => '',
        ];
    }

    public function getAccount($params): array
    {
        $account = $this->accountRepository->getAccount($params);
        return $this->canonizeAccount($account);
    }

    public function getAccountList($params): array
    {
        $limit  = (int)$params['limit'] ?? 10;
        $page   = (int)$params['page'] ?? 1;
        $order  = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ($page - 1) * $limit;

        // Set params
        $listParams = [
            'page'   => $page,
            'limit'  => $limit,
            'order'  => $order,
            'offset' => $offset,
        ];

        // Get list
        $list   = [];
        $rowSet = $this->accountRepository->getAccountList($listParams);
        foreach ($rowSet as $row) {
            $list[] = $this->canonizeAccount($row);
        }

        // Get count
        $count = $this->accountRepository->getAccountCount($listParams);

        return [
            'list'      => $list,
            'paginator' => [
                'count' => $count,
                'limit' => $limit,
                'page'  => $page,
            ],
        ];
    }

    public function addAccount($params): array
    {
        $params['credential']   = $this->generateCredential($params['credential']);
        $params['status']       = $this->userRegisterStatus();
        $params['time_created'] = time();

        $account = $this->accountRepository->addAccount($params);
        $account = $this->canonizeAccount($account);

        // Set user roles
        $this->roleService->addDefaultRoles((int)$account['id']);

        return $account;
    }

    public function updateAccount($params, $account): array
    {
        // Clean up
        foreach ($params as $key => $value) {
            if (empty($value) || !isset($account[$key]) || $account[$key] == $value) {
                unset($params[$key]);
            }
        }

        if (!empty($params)) {
            $this->accountRepository->updateAccount((int)$account['id'], $params);
        }

        return $this->getAccount(['id' => (int)$account['id']]);
    }

    public function updatePassword($params, $account): array
    {
        $hash = $this->accountRepository->getAccountCredential((int)$account['id']);

        $bcrypt = new Bcrypt();
        if ($bcrypt->verify($params['current_credential'], $hash)) {
            $credential = $bcrypt->create($params['new_credential']);

            $this->accountRepository->updateAccount((int)$account['id'], ['credential' => $credential]);

            $result = [
                'result' => true,
                'data'   => [
                    'message' => 'Password update successfully !',
                ],
                'error'  => '',
            ];
        } else {
            $result = [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error to update password !',
                ],
            ];
        }

        return $result;
    }

    public function canonizeAccount($account): array
    {
        if (empty($account)) {
            return [];
        }

        if (is_object($account)) {
            $account = [
                'id'         => $account->getId(),
                'name'       => $account->getName(),
                'identity'   => $account->getIdentity(),
                'email'      => $account->getEmail(),
                'status'     => $account->getStatus(),
            ];
        } else {
            $account = [
                'id'         => $account['id'],
                'name'       => $account['name'],
                'email'      => $account['email'],
                'identity'   => $account['identity'],
                'status'     => $account['status'],
            ];
        }

        return $account;
    }

    public function refreshToken($params): array
    {
        $accessToken = $this->tokenService->generate(
            [
                'user_id' => $params['user_id'],
                'type'    => 'access',
                'roles'   => [
                    'member',
                ],
            ]
        );

        // Update cache
        $this->cacheService->addItem($params['user_id'], 'access_keys', $accessToken['key']);

        // Set result array
        return [
            'result' => true,
            'data'   => [
                'access_token' => $accessToken['token'],
            ],
            'error'  => '',
        ];
    }

    public function generateCredential($credential): string
    {
        $bcrypt = new Bcrypt();
        return $bcrypt->create($credential);
    }

    public function isDuplicated($type, $value, $id = 0): bool
    {
        return (bool)$this->accountRepository->count(
            [
                'field' => $type,
                'value' => $value,
                'id'    => $id,
            ]
        );
    }

    public function userRegisterStatus(): int
    {
        // ToDo: call it from config
        return 1;
    }
}