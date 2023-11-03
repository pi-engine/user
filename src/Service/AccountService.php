<?php

namespace User\Service;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Math\Rand;
use Notification\Service\NotificationService;
use Psr\SimpleCache\InvalidArgumentException;
use User\Repository\AccountRepositoryInterface;

use function array_merge;
use function in_array;
use function is_object;
use function is_string;
use function sprintf;
use function time;

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

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /** @var NotificationService */
    protected NotificationService $notificationService;

    /** @var HistoryService */
    protected HistoryService $historyService;

    /* @var array */
    protected array $config;

    protected array $accountFields
        = [
            'name',
            //'email',
            //'identity',
            //'mobile',
            //'status',
        ];

    protected array $profileFields
        = [
            'user_id',
            'first_name',
            'last_name',
            'birthdate',
            'gender',
            'avatar',
            'ip_register',
            'register_source',
            'id_number',
            'homepage',
            'phone',
            'address_1',
            'address_2',
            'item_id',
            'country',
            'state',
            'city',
            'zip_code',
            'bank_name',
            'bank_card',
            'bank_account',
        ];

    /**
     * @param AccountRepositoryInterface $accountRepository
     * @param RoleService $roleService
     * @param TokenService $tokenService
     * @param CacheService $cacheService
     * @param UtilityService $utilityService
     * @param NotificationService $notificationService
     * @param HistoryService $historyService
     * @param                            $config
     */
    public function __construct(
        AccountRepositoryInterface $accountRepository,
        RoleService                $roleService,
        TokenService               $tokenService,
        CacheService               $cacheService,
        UtilityService             $utilityService,
        NotificationService        $notificationService,
        HistoryService             $historyService,
                                   $config
    )
    {
        $this->accountRepository = $accountRepository;
        $this->roleService = $roleService;
        $this->tokenService = $tokenService;
        $this->cacheService = $cacheService;
        $this->utilityService = $utilityService;
        $this->notificationService = $notificationService;
        $this->historyService = $historyService;
        $this->config = $config;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function login($params): array
    {
        // Set login column
        $identityColumn = $params['identityColumn'] ?? 'identity';
        $credentialColumn = $params['credentialColumn'] ?? 'credential';

        // Do log in
        $authentication = $this->accountRepository->authentication($identityColumn, $credentialColumn);
        $adapter = $authentication->getAdapter();
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

            // Get profile
            $profile = $this->getProfile(['user_id' => (int)$account['id']]);

            // Sync profile and account
            $account = array_merge($account, $profile);

            // Get roles
            $account['roles'] = $this->roleService->getRoleAccount((int)$account['id']);

            // Generate access token
            $accessToken = $this->tokenService->generate(
                [
                    'account' => $account,
                    'type' => 'access',
                ]
            );

            // Generate refresh token
            $refreshToken = $this->tokenService->generate(
                [
                    'account' => $account,
                    'type' => 'refresh',
                ]
            );

            // Set extra info
            $account['last_login'] = time();
            $account['has_password'] = $this->hasPassword($account['id']);
            $account['access_token'] = $accessToken['token'];
            $account['refresh_token'] = $refreshToken['token'];

            // Set source roles params
            if (isset($params['source']) && !empty($params['source']) && is_string($params['source']) && !in_array($params['source'], $account['roles'])) {
                $this->roleService->addRoleAccount((int)$account['id'], $params['source']);

                // Set new role
                $account['roles'][] = $params['source'];
                $account['roles'] = array_values($account['roles']);
            }

            // Get from cache if exist
            $user = $this->cacheService->getUser($account['id']);

            // Set/Update user data to cache
            $this->cacheService->setUser($account['id'], [
                'account' => [
                    'id' => (int)$account['id'],
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'identity' => $account['identity'],
                    'mobile' => $account['mobile'],
                    'last_login' => $account['last_login'],
                ],
                'roles' => $account['roles'],
                'access_keys' => (isset($user['access_keys']) && !empty($user['access_keys']))
                    ? array_unique(array_merge($user['access_keys'], [$accessToken['key']]))
                    : [$accessToken['key']],
                'refresh_keys' => (isset($user['refresh_keys']) && !empty($user['refresh_keys']))
                    ? array_unique(array_merge($user['refresh_keys'], [$refreshToken['key']]))
                    : [$refreshToken['key']],
            ]);

            // Save log
            $this->historyService->logger('login', ['params' => $params, 'account' => $account]);

            $result = [
                'result' => true,
                'data' => $account,
                'error' => [],
            ];
        } else {
            // Save log
            $account = $this->getAccount([$identityColumn => $params['identity']]);
            $this->historyService->logger('failedLogin', ['params' => $params, 'account' => $account]);

            $result = [
                'result' => false,
                'data' => [],
                'error' => [
                    'message' => 'Invalid Username or Password',
                ],
                'status' => StatusCodeInterface::STATUS_UNAUTHORIZED,
            ];
        }

        return $result;
    }

    public function logout($params): array
    {
        // Save log
        $user = $this->cacheService->getUser($params['user_id']);
        $this->historyService->logger('logout', ['params' => $params, 'account' => $user['account']]);

        if (isset($params['all_session']) && (int)$params['all_session'] == 1) {
            $this->cacheService->deleteUser($params['user_id']);
            $message = 'You are logout successfully from all of your sessions !';
        } else {
            $this->cacheService->removeItem($params['user_id'], 'access_keys', $params['token_id']);
            $message = 'You are logout successfully from this session !';
        }

        return [
            'result' => true,
            'data' => [
                'message' => $message,
            ],
            'error' => [],
        ];
    }

    public function prepareMobileLogin($params): array
    {
        // Set new password as OTP
        $otpCode = Rand::getInteger(100000, 999999);
        $otpExpire = (time() + 120);
        $isNew = 0;

        // Check account exist
        $account = $this->getAccount(['mobile' => $params['mobile']]);

        // Create account if not exist
        // Update OTP password if account exist
        if (empty($account)) {
            $account = $this->addAccount(
                [
                    'mobile' => $params['mobile'],
                    'first_name' => $params['first_name'] ?? null,
                    'last_name' => $params['last_name'] ?? null,
                    'source' => $params['source'] ?? null,
                    'otp' => $otpCode,
                ]
            );

            // Set is new
            $isNew = 1;
        } else {
            $bcrypt = new Bcrypt();
            $otp = $bcrypt->create($otpCode);
            $this->accountRepository->updateAccount((int)$account['id'], ['otp' => $otp]);
        }

        // Set user cache
        $this->cacheService->setUser(
            $account['id'],
            [
                'account' => [
                    'id' => (int)$account['id'],
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'identity' => $account['identity'],
                    'mobile' => $account['mobile'],
                    'last_login' => $user['account']['last_login'] ?? time(),
                ],
                'otp' => [
                    'code' => $otpCode,
                    'time_expire' => $otpExpire,
                ],
            ]
        );

        // Set sms message
        $message = 'Code: %s
        لغو11';
        if (
            isset($params['source'])
            && !empty($params['source'])
            && isset($this->config['otp_sms'])
            && in_array($params['source'], array_keys($this->config['otp_sms']))
        ) {
            $message = $this->config['otp_sms'][$params['source']];
        }

        // Set notification params
        $notificationParams = [
            'sms' => [
                'message' => sprintf($message, $otpCode),
                'mobile' => $account['mobile'],
                'source' => $params['source'] ?? '',
            ],
        ];

        // Send notification
        $this->notificationService->send($notificationParams);

        // Set result
        return [
            'result' => true,
            'data' => [
                'message' => 'Verify code send to your mobile number !',
                'name' => $account['name'],
                'mobile' => $account['mobile'],
                'is_new' => $isNew,
                'otp_expire' => $otpExpire,
            ],
            'error' => [],
        ];
    }

    public function prepareMailLogin($params): array
    {
        // Set new password as OTP
        $otpCode = Rand::getInteger(100000, 999999);
        $otpExpire = (time() + 180);
        $isNew = 0;

        // Check account exist
        $account = $this->getAccount(['email' => $params['email']]);

        // Create account if not exist
        // Update OTP password if account exist
        if (empty($account)) {
            $account = $this->addAccount(
                [
                    'email' => $params['email'],
                    'source' => $params['source'] ?? null,
                    'first_name' => $params['first_name'] ?? null,
                    'last_name' => $params['last_name'] ?? null,
                    'otp' => $otpCode,
                ]
            );

            // Set is new
            $isNew = 1;
        } else {
            $bcrypt = new Bcrypt();
            $otp = $bcrypt->create($otpCode);
            $this->accountRepository->updateAccount((int)$account['id'], ['otp' => $otp]);
        }

        // Set user cache
        $this->cacheService->setUser(
            $account['id'],
            [
                'account' => [
                    'id' => (int)$account['id'],
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'identity' => $account['identity'],
                    'mobile' => $account['mobile'],
                    'last_login' => $user['account']['last_login'] ?? time(),
                ],
                'otp' => [
                    'code' => $otpCode,
                    'time_expire' => $otpExpire,
                ],
            ]
        );

        // Set notification params
        $notificationParams = [
            'email' => [
                'to' => [
                    'email' => $account['email'],
                    'name' => $account['name'],
                ],
                'subject' => $this->config['otp_email']['subject'],
                'body' => sprintf($this->config['otp_email']['body'], $otpCode),
            ],
        ];

        // Send notification
        $this->notificationService->send($notificationParams);

        // Set result
        return [
            'result' => true,
            'data' => [
                'message' => 'Verify code send to your email !',
                'name' => $account['name'],
                'email' => $account['email'],
                'is_new' => $isNew,
                'otp_expire' => $otpExpire,
            ],
            'error' => [],
        ];
    }

    public function addAccount($params): array
    {
        // Set name
        if (
            isset($params['first_name'])
            && !empty($params['first_name'])
            && isset($params['last_name'])
            && !empty($params['last_name'])
        ) {
            $params['name'] = sprintf('%s %s', $params['first_name'], $params['last_name']);
        }

        $otp = null;
        $credential = null;
        if (isset($params['credential']) && !empty($params['credential'])) {
            $credential = $this->generatePassword($params['credential']);
        }
        if (isset($params['otp']) && !empty($params['otp'])) {
            $otp = $this->generatePassword($params['otp']);
        }

        $paramsAccount = [
            'name' => $params['name'] ?? null,
            'email' => $params['email'] ?? null,
            'identity' => $params['identity'] ?? null,
            'mobile' => $params['mobile'] ?? null,
            'credential' => $credential,
            'otp' => $otp,
            'status' => $this->userRegisterStatus(),
            'time_created' => time(),
        ];

        $account = $this->accountRepository->addAccount($paramsAccount);
        $account = $this->canonizeAccount($account);

        $profileParams = [
            'user_id' => (int)$account['id'],
            'first_name' => $params['first_name'] ?? null,
            'last_name' => $params['last_name'] ?? null,
            'id_number' => $params['id_number'] ?? null,
            'birthdate' => $params['birthdate'] ?? null,
            'gender' => $params['gender'] ?? null,
            'avatar' => $params['avatar'] ?? null,
            'ip_register' => $params['ip_register'] ?? null,
            'register_source' => $params['register_source'] ?? null,
            'homepage' => $params['homepage'] ?? null,
            'phone' => $params['phone'] ?? null,
            'address_1' => $params['address_1'] ?? null,
            'address_2' => $params['address_2'] ?? null,
            'item_id' => $params['item_id'] ?? 0,
            'country' => $params['country'] ?? null,
            'state' => $params['state'] ?? null,
            'city' => $params['city'] ?? null,
            'zip_code' => $params['zip_code'] ?? null,
            'bank_name' => $params['bank_name'] ?? null,
            'bank_card' => $params['bank_card'] ?? null,
            'bank_account' => $params['bank_account'] ?? null,
        ];

        $profile = $this->accountRepository->addProfile($profileParams);
        $profile = $this->canonizeProfile($profile);

        $account = array_merge($account, $profile);

        // Set user roles
        $this->roleService->addDefaultRoles((int)$account['id']);

        // Set user roles that receive from service
        if (isset($params['roles'])) {
            $roles = explode(',', $params['roles']);
            foreach ($roles as $role) {
                if ($role != 'member') {
                    $this->roleService->addRoleAccount($account['id'], $role);
                }
            }
        }

        // Get roles
        $roles = $this->roleService->getRoleAccount((int)$account['id']);

        // Set source roles params
        if (isset($params['source']) && !empty($params['source']) && is_string($params['source']) && !in_array($params['source'], $roles)) {
            $this->roleService->addRoleAccount((int)$account['id'], $params['source']);
        }

        // Save log
        $this->historyService->logger('register', ['params' => $params, 'account' => $account]);

        return $account;
    }

    public function getAccount($params): array
    {
        $account = $this->accountRepository->getAccount($params);
        return $this->canonizeAccount($account);
    }

    public function addOrGetAccount($params): array
    {
        $account = [];
        if (isset($params['email']) && !empty($params['email'])) {
            $account = $this->getAccount(['email' => $params['email']]);
        } elseif (isset($params['mobile']) && !empty($params['mobile'])) {
            $account = $this->getAccount(['mobile' => $params['mobile']]);
        } elseif (isset($params['identity']) && !empty($params['identity'])) {
            $account = $this->getAccount(['identity' => $params['identity']]);
        }

        if (empty($account)) {
            $addParams = [
                'email' => $params['email'] ?? null,
                'mobile' => $params['mobile'] ?? null,
                'identity' => $params['identity'] ?? null,
                'credential' => $params['credential'] ?? null,
                'source' => $params['source'] ?? null,
                'first_name' => $params['first_name'] ?? null,
                'last_name' => $params['last_name'] ?? null,
                'id_number' => $params['id_number'] ?? null,
                'birthdate' => $params['birthdate'] ?? null,
                'gender' => $params['gender'] ?? null,
                'avatar' => $params['avatar'] ?? null,
                'ip_register' => $params['ip_register'] ?? null,
                'register_source' => $params['register_source'] ?? null,
                'homepage' => $params['homepage'] ?? null,
                'phone' => $params['phone'] ?? null,
                'address_1' => $params['address_1'] ?? null,
                'address_2' => $params['address_2'] ?? null,
                'item_id' => $params['item_id'] ?? 0,
                'country' => $params['country'] ?? null,
                'state' => $params['state'] ?? null,
                'city' => $params['city'] ?? null,
                'zip_code' => $params['zip_code'] ?? null,
            ];
            $account = $this->addAccount($addParams);
        } else {
            $profile = $this->getProfile(['user_id' => (int)$account['id']]);
            $account = array_merge($account, $profile);
        }

        return $account;
    }

    public function getUserFromCache($id): array
    {
        $user = $this->cacheService->getUser($id);

        return [
            'account' => $user['account'],
            'roles' => $user['roles'],
        ];
    }

    public function getUserFromCacheFull($id): array
    {
        return $this->cacheService->getUser($id);
    }

    public function getAccountCount($params = []): int
    {
        return $this->accountRepository->getAccountCount($params);
    }

    public function getAccountList($params): array
    {
        $limit = $params['limit'] ?? 10;
        $page = $params['page'] ?? 1;
        /// changed by kerloper
        $key = $params['key'] ?? '';
        $order = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ((int)$page - 1) * (int)$limit;

        // Set params
        /// changed by kerloper
        $listParams = [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'order' => $order,
            'offset' => $offset,
            'key' => $key,
        ];

        if (isset($params['name']) && !empty($params['name'])) {
            $listParams['name'] = $params['name'];
        }
        if (isset($params['identity']) && !empty($params['identity'])) {
            $listParams['identity'] = $params['identity'];
        }
        if (isset($params['email']) && !empty($params['email'])) {
            $listParams['email'] = $params['email'];
        }
        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $listParams['mobile'] = $params['mobile'];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $listParams['status'] = $params['status'];
        }

        $filters = $this->prepareFilter($params);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $itemIdList = [];
                $rowSet = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $itemIdList[] = $this->canonizeAccountId($row);
                }
                $listParams['id'] = $itemIdList;
            }

        }

        // Get list
        $list = [];
        $rowSet = $this->accountRepository->getAccountList($listParams);
        foreach ($rowSet as $row) {
            /// changed by kerloper
            $list[$row->getId()] = $this->canonizeAccount($row);
        }

        // Get roles
        $roleList = $this->roleService->getRoleAccountList(array_keys($list));

        // Get count
        $count = $this->accountRepository->getAccountCount($listParams);


        $list = array_values($list);
        $i = 0;
        foreach ($list as $user) {
            $list[$i]['roles'] = $roleList[$user['id']];
            $i++;
        }

        return [
            'list' => $list,
            'roles' => $roleList,
            'paginator' => [
                'count' => $count,
                'limit' => $limit,
                'page' => $page,
            ],
        ];
    }

    public function canonizeAccountId(object|array $roleAccountList): int|null
    {
        if (empty($roleAccountList)) {
            return 0;
        }

        if (is_object($roleAccountList)) {
            $accountId = $roleAccountList->getUserId();
        } else {
            $accountId = $roleAccountList['user_id'];
        }

        return $accountId;
    }


    public function updateAccount($params, $account): array
    {
        // Set name
        if (
            isset($params['first_name'])
            && !empty($params['first_name'])
            && isset($params['last_name'])
            && !empty($params['last_name'])
        ) {
            $params['name'] = sprintf('%s %s', $params['first_name'], $params['last_name']);
        }

        // Clean up
        $accountParams = [];
        $profileParams = [];
        foreach ($params as $key => $value) {
            if (in_array($key, $this->accountFields)) {
                if (!empty($value) && is_string($value)) {
                    $accountParams[$key] = $value;
                }
            }

            if (in_array($key, $this->profileFields)) {
                if (empty($value)) {
                    $profileParams[$key] = null;
                } elseif (is_string($value)) {
                    $profileParams[$key] = $value;
                }
            }
        }

        if (!empty($accountParams)) {
            $this->accountRepository->updateAccount((int)$account['id'], $accountParams);
        }

        if (!empty($profileParams)) {
            $this->accountRepository->updateProfile((int)$account['id'], $profileParams);
        }

        // Get account after update
        $account = $this->getAccount(['id' => (int)$account['id']]);
        $profile = $this->getProfile(['user_id' => (int)$account['id']]);

        // Get user from cache if exist
        $user = $this->cacheService->getUser($account['id']);

        // restore roles that receive from service
        if (isset($params['roles'])) {
            $this->roleService->deleteAllRoleAccount($account['id']);
            $roles = explode(',', $params['roles']);
            foreach ($roles as $role) {
                if ($role != 'member') {
                    $this->roleService->addRoleAccount($account['id'], $role);
                }
            }
        }

        // Set/Update user data to cache
        $this->cacheService->setUser(
            $account['id'],
            [
                'account' => [
                    'id' => $account['id'],
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'identity' => $account['identity'],
                    'mobile' => $account['mobile'],
                    'last_login' => $user['account']['last_login'] ?? time(),
                ], 
            ]
        );

        // Save log
        $this->historyService->logger('update', ['params' => $params, 'account' => $account]);

        return array_merge($account, $profile);
    }

    public function updatedDeviceToken($params, $account): void
    {
        // Update cache
        $this->cacheService->addItem($account['id'], 'device_tokens', $params['device_token']);

        // Save log
        $this->historyService->logger('updateupdatedDeviceToken', ['params' => $params, 'account' => $account]);
    }

    public function getProfile($params): array
    {
        $profile = $this->accountRepository->getProfile($params);
        return $this->canonizeProfile($profile);
    }

    public function generatePassword($credential): string
    {
        $bcrypt = new Bcrypt();
        return $bcrypt->create($credential);
    }

    public function addPassword($params, $account = []): array
    {
        $userId = $account['id'] ?? $params['user_id'];
        $credential = $this->generatePassword($params['credential']);

        $this->accountRepository->updateAccount((int)$userId, ['credential' => $credential]);

        // Save log
        $account = empty($account) ? ['id' => $userId] : $account;
        $this->historyService->logger('addPassword', ['params' => $params, 'account' => $account]);

        return [
            'result' => true,
            'data' => [
                'message' => 'Password set successfully !',
            ],
            'error' => [],
        ];
    }

    public function updatePassword($params, $account): array
    {
        $hash = $this->accountRepository->getAccountPassword((int)$account['id']);

        $bcrypt = new Bcrypt();
        if ($bcrypt->verify($params['current_credential'], $hash)) {
            $credential = $bcrypt->create($params['new_credential']);

            $this->accountRepository->updateAccount((int)$account['id'], ['credential' => $credential]);

            // Save log
            $this->historyService->logger('updatePassword', ['params' => $params, 'account' => $account]);

            $result = [
                'result' => true,
                'data' => [
                    'message' => 'Password update successfully !',
                ],
                'error' => [],
            ];
        } else {
            // Save log
            $this->historyService->logger('failedUpdatePassword', ['params' => $params, 'account' => $account]);

            $result = [
                'result' => false,
                'data' => [],
                'error' => [
                    'message' => 'Error to update password !',
                ],
                'status' => StatusCodeInterface::STATUS_UNAUTHORIZED,
            ];
        }

        return $result;
    }

    public function updatePasswordByAdmin($params): array
    {
        $credential = $this->generatePassword($params['credential']);

        $this->accountRepository->updateAccount((int)$params['user_id'], ['credential' => $credential]);

        // Save log
        $this->historyService->logger('updatePasswordByAdmin', ['params' => $params, 'account' => ['id' => (int)$params['user_id']]]);

        return [
            'result' => true,
            'data' => [
                'message' => 'Password set successfully !',
            ],
            'error' => [],
        ];
    }

    public function hasPassword($userId): bool
    {
        $hash = $this->accountRepository->getAccountPassword((int)$userId);

        if (empty($hash)) {
            return false;
        }

        return true;
    }

    public function refreshToken($params): array
    {
        $accessToken = $this->tokenService->generate(
            [
                'user_id' => $params['user_id'],
                'type' => 'access',
                'roles' => [
                    'member',
                ],
            ]
        );

        // Update cache
        $this->cacheService->addItem($params['user_id'], 'access_keys', $accessToken['key']);

        // Set result array
        return [
            'result' => true,
            'data' => [
                'access_token' => $accessToken['token'],
            ],
            'error' => [],
        ];
    }

    public function canonizeAccount($account): array
    {
        if (empty($account)) {
            return [];
        }

        if (is_object($account)) {
            $account = [
                'id' => (int)$account->getId(),
                'name' => $account->getName(),
                'identity' => $account->getIdentity(),
                'email' => $account->getEmail(),
                'mobile' => $account->getMobile(),
                'status' => (int)$account->getStatus(),
                'time_created' => $account->getTimeCreated(),
            ];
        } else {
            $account = [
                'id' => (int)$account['id'],
                'name' => $account['name'] ?? '',
                'email' => $account['email'] ?? '',
                'identity' => $account['identity'] ?? '',
                'mobile' => $account['mobile'] ?? '',
                'status' => (int)$account['status'],
                'time_created' => $account['time_created'] ?? '',
            ];
        }

        $account['time_created_view'] = ' - ';
        if (!empty($account['time_created']) && is_numeric($account['time_created'])) {
            $account['time_created_view'] = $this->utilityService->date($account['time_created']);
        }

        return $account;
    }

    public function canonizeProfile($profile): array
    {
        if (empty($profile)) {
            return [];
        }

        if (is_object($profile)) {
            $profile = [
                'user_id' => $profile->getUserId(),
                'first_name' => $profile->getFirstName(),
                'last_name' => $profile->getLastName(),
                'id_number' => $profile->getIdNumber(),
                'birthdate' => $profile->getBirthdate(),
                'gender' => $profile->getGender(),
                'avatar' => $profile->getAvatar(),
                'ip_register' => $profile->getIpRegister(),
                'register_source' => $profile->getRegisterSource(),
                'homepage' => $profile->getHomepage(),
                'phone' => $profile->getPhone(),
                'address_1' => $profile->getAddress1(),
                'address_2' => $profile->getAddress2(),
                'item_id' => $profile->getItemId(),
                'country' => $profile->getCountry(),
                'state' => $profile->getState(),
                'city' => $profile->getCity(),
                'zip_code' => $profile->getZipCode(),
                'bank_name' => $profile->getBankName(),
                'bank_card' => $profile->getBankCard(),
                'bank_account' => $profile->getBankAccount(),
            ];
        } else {
            $profile = [
                'user_id' => $profile['user_id'],
                'first_name' => $profile['first_name'],
                'last_name' => $profile['last_name'],
                'id_number' => $profile['id_number'],
                'birthdate' => $profile['birthdate'],
                'gender' => $profile['gender'],
                'avatar' => $profile['avatar'],
                'ip_register' => $profile['ip_register'],
                'register_source' => $profile['register_source'],
                'homepage' => $profile['homepage'],
                'phone' => $profile['phone'],
                'address_1' => $profile['address_1'],
                'address_2' => $profile['address_2'],
                'item_id' => $profile['item_id'],
                'country' => $profile['country'],
                'state' => $profile['state'],
                'city' => $profile['city'],
                'zip_code' => $profile['zip_code'],
                'bank_name' => $profile['bank_name'],
                'bank_card' => $profile['bank_card'],
                'bank_account' => $profile['bank_account'],
            ];
        }

        return $profile;
    }

    public function isDuplicated($type, $value, $id = 0): bool
    {
        return (bool)$this->accountRepository->count(
            [
                'field' => $type,
                'value' => $value,
                'id' => $id,
            ]
        );
    }

    public function userRegisterStatus(): int
    {
        // ToDo: call it from config
        return 1;
    }

    public function prepareFilter($params): array
    {
        // Set filter list
        $filters = [];
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'roles':
                    $filters[$key] = [
                        'role' => $key,
                        'value' => explode(',', $value),
                        'type' => 'string',
                    ];
                    break;
            }
        }
        return $filters;
    }
}
