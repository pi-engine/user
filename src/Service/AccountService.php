<?php

namespace User\Service;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Math\Rand;
use Notification\Service\NotificationService;
use RobThree\Auth\Algorithm;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;
use User\Repository\AccountRepositoryInterface;
use User\Security\AccountLocked;
use User\Security\AccountLoginAttempts;

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

    /** @var PermissionService */
    protected PermissionService $permissionService;

    /* @var TokenService */
    protected TokenService $tokenService;

    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /** @var AvatarService */
    protected AvatarService $avatarService;

    /** @var NotificationService */
    protected NotificationService $notificationService;

    /** @var HistoryService */
    protected HistoryService $historyService;

    /** @var TranslatorService */
    protected TranslatorService $translatorService;

    /** @var AccountLoginAttempts */
    protected AccountLoginAttempts $accountLoginAttempts;

    /** @var AccountLocked */
    protected AccountLocked $accountLocked;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $identityColumn = 'identity';

    /* @var string */
    protected string $credentialColumn = 'credential';

    /* @var array */
    protected array $accountFields
        = [
            'name',
            //'email',
            //'identity',
            //'mobile',
            //'status',
            'multi_factor_status',
            'multi_factor_secret',
        ];

    /* @var array */
    protected array $profileFields
        = [
            'user_id',
            'first_name',
            'last_name',
            'birthdate',
            'gender',
            'avatar',
        ];

    /* @var array */
    protected array $informationFields
        = [
            //'user_id',
            //'name',
            //'email',
            //'identity',
            //'mobile',
            //'status',
            //'multi_factor_status',
            //'multi_factor_secret',
            //'first_name',
            //'last_name',
            //'birthdate',
            //'gender',
            //'avatar',
            'avatar_params',
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
            'device_type',
            'device_token',
        ];

    protected array $emptyRoles = ['api' => [], 'admin' => []];

    /* @var string */
    protected string $hashPattern;

    /**
     * @param AccountRepositoryInterface $accountRepository
     * @param RoleService                $roleService
     * @param PermissionService          $permissionService
     * @param TokenService               $tokenService
     * @param CacheService               $cacheService
     * @param UtilityService             $utilityService
     * @param AvatarService              $avatarService
     * @param NotificationService        $notificationService
     * @param HistoryService             $historyService
     * @param TranslatorService          $translatorService
     * @param AccountLoginAttempts       $accountLoginAttempts
     * @param AccountLocked              $accountLocked
     * @param                            $config
     */
    public function __construct(
        AccountRepositoryInterface $accountRepository,
        RoleService $roleService,
        PermissionService $permissionService,
        TokenService $tokenService,
        CacheService $cacheService,
        UtilityService $utilityService,
        AvatarService $avatarService,
        NotificationService $notificationService,
        HistoryService $historyService,
        TranslatorService $translatorService,
        AccountLoginAttempts $accountLoginAttempts,
        AccountLocked $accountLocked,
        $config
    ) {
        $this->accountRepository    = $accountRepository;
        $this->roleService          = $roleService;
        $this->permissionService    = $permissionService;
        $this->tokenService         = $tokenService;
        $this->cacheService         = $cacheService;
        $this->avatarService        = $avatarService;
        $this->utilityService       = $utilityService;
        $this->notificationService  = $notificationService;
        $this->historyService       = $historyService;
        $this->translatorService    = $translatorService;
        $this->accountLoginAttempts = $accountLoginAttempts;
        $this->accountLocked        = $accountLocked;
        $this->config               = $config;
        $this->hashPattern          = $config['hash_pattern'] ?? 'bcrypt';
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function login($params): array
    {
        // Set login column
        $this->identityColumn   = $params['identityColumn'] ?? $this->identityColumn;
        $this->credentialColumn = $params['credentialColumn'] ?? $this->credentialColumn;

        // Do log in
        $authentication = $this->accountRepository->authentication($this->identityColumn, $this->credentialColumn, $this->hashPattern);
        $adapter        = $authentication->getAdapter();
        $adapter->setIdentity($params['identity'])->setCredential($params['credential']);

        // Check login
        if ($authentication->authenticate()->isValid()) {
            // Get a user account
            $account = (array)$adapter->getResultRowObject(
                [
                    'id',
                    'name',
                    'email',
                    'mobile',
                    'identity',
                    'status',
                    'time_created',
                    'multi_factor_status',
                    'multi_factor_secret',
                ]
            );

            // Canonize account
            $account = $this->canonizeAccount($account);

            // Complete login
            $result = $this->postLoginSuccess($account, $params);
        } else {
            $result = $this->postLoginError($params);
        }

        return $result;
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function loginOauth($params): array
    {
        // Set login column
        $this->identityColumn = 'email';

        // Do log in
        $authAdapter = $this->accountRepository->authenticationOauth($params);

        // Check login
        if ($authAdapter->isValid()) {
            // Get a user account
            $account = $authAdapter->getIdentity();

            // Canonize account
            $account = $this->canonizeAccount($account);

            // Complete login
            $result = $this->postLoginSuccess($account, $params);
        } else {
            if (isset($this->config['oauth']['oauth_register']) && (int)$this->config['oauth']['oauth_register'] === 1) {
                $this->addAccount($params);
                $result = $this->loginOauth($params);
            } else {
                $result = $this->postLoginError($params);
            }
        }

        return $result;
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function loginOauth2($params): array
    {
        // Set login column
        $this->identityColumn = 'identity';

        // Do log in
        $authAdapter = $this->accountRepository->authenticationOauth2($params);

        // Check login
        if ($authAdapter->isValid()) {
            // Get a user account
            $account = $authAdapter->getIdentity();

            // Canonize account
            $account = $this->canonizeAccount($account);

            // Complete login
            $result = $this->postLoginSuccess($account, $params);
        } else {
            if (isset($this->config['oauth']['oauth_register']) && (int)$this->config['oauth']['oauth_register'] === 1) {
                $this->addAccount($params);
                $result = $this->loginOauth2($params);
            } else {
                $result = $this->postLoginError($params);
            }
        }

        return $result;
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function logout($params): array
    {
        // Set message
        $message = 'You are logout successfully from this session !';

        // Get and check user
        $user = $this->cacheService->getUser($params['user_id']);
        if (!empty($user)) {
            // Save log
            $this->historyService->logger('logout', ['request' => $params, 'account' => $user['account']]);

            // Check and clean user cache for logout
            if (isset($params['all_session']) && (int)$params['all_session'] === 1) {
                $this->cacheService->deleteUserItem($params['user_id'], 'all_keys', '');
                $message = 'You are logout successfully from all of your sessions !';
            } else {
                $this->cacheService->deleteUserItem($params['user_id'], 'access_keys', $params['token_id']);
            }
        }

        return [
            'result' => true,
            'data'   => [
                'message' => $message,
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $account
     * @param       $params
     *
     * @return array
     */
    public function postLoginSuccess($account, $params): array
    {
        // Check account is lock or not
        if ($this->accountLocked->isLocked(['type' => 'id', 'user_id' => (int)$account['id'], 'security_stream' => $params['security_stream']])) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => $this->accountLocked->getErrorMessage(),
                ],
                'status' => $this->accountLocked->getStatusCode(),
            ];
        }

        // Get from cache if exist
        $user = $this->cacheService->getUser($account['id']);

        // Set multi factor
        $multiFactorGlobal = (int)$this->config['multi_factor']['status'] ?? 0;
        $multiFactorStatus = (int)$account['multi_factor_status'] ?? 0;
        $multiFactorVerify = 0;
        unset($account['multi_factor_status']);

        // Get profile
        $profile = $this->getProfile(['user_id' => (int)$account['id']]);

        // Sync profile and account
        $account = array_merge($account, $profile);

        // Get roles
        $account['roles']      = $this->roleService->getRoleAccount((int)$account['id']);
        $account['roles_full'] = $this->roleService->canonizeAccountRole($account['roles']);

        // Generate access token
        $accessToken = $this->tokenService->encryptToken(
            [
                'account' => $account,
                'type'    => 'access',
            ]
        );

        // Generate refresh token
        $refreshToken = $this->tokenService->encryptToken(
            [
                'account' => $account,
                'type'    => 'refresh',
            ]
        );

        // Set multi factor
        $multiFactor = [
            $accessToken['key'] => [
                'key'                 => $accessToken['key'],
                'multi_factor_global' => $multiFactorGlobal,
                'multi_factor_status' => $multiFactorStatus,
                'multi_factor_verify' => $multiFactorVerify,
            ],
        ];

        // Set extra info
        $account['last_login']          = time();
        $account['has_password']        = $this->hasPassword($account['id']);
        $account['multi_factor_global'] = $multiFactorGlobal;
        $account['multi_factor_status'] = $multiFactorStatus;
        $account['multi_factor_verify'] = $multiFactorVerify;
        $account['access_token']        = $accessToken['token'];
        $account['refresh_token']       = $refreshToken['token'];
        $account['is_company_setup']    = false;
        $account['permission']          = null;
        $account['token_payload']       = [
            'iat' => $accessToken['payload']['iat'],
            'exp' => $accessToken['payload']['exp'],
        ];

        // Set permission
        if (isset($this->config['login']['permission']) && (int)$this->config['login']['permission'] === 1) {
            $permissionParams = [
                'section' => 'api',
                'role'    => $account['roles'],
            ];

            $account['permission'] = $this->permissionService->getPermissionRole($permissionParams);
        }

        // Check company setup
        if (isset($this->config['login']['get_company']) && (int)$this->config['login']['get_company'] === 1) {
            $isCompanySetup = false;

            if (isset($user['authorization']['company']['is_company_setup'])) {
                $isCompanySetup = $user['authorization']['company']['is_company_setup'];
            } elseif (isset($user['account']['is_company_setup'])) {
                $isCompanySetup = $user['account']['is_company_setup'];
            }

            $account['is_company_setup'] = $isCompanySetup;
        }

        // Check permission for company package
        if (isset($this->config['login']['permission_package']) && (int)$this->config['login']['permission_package'] === 1) {
            if (isset($user['authorization']['package_id']) && (int)$user['authorization']['package_id'] > 0) {
                $key     = sprintf('package-%s', $user['authorization']['package_id']);
                $package = $this->cacheService->getItem($key);

                // Check and clean account permission base of package access
                if (
                    !empty($package)
                    && isset($package['information']['access'])
                    && !empty($package['information']['access'])
                ) {
                    $account['permission'] = array_intersect(array_values($account['permission']), array_values($package['information']['access']));
                }
            }
        }

        // Set source roles params
        if (isset($params['source']) && !empty($params['source']) && is_string($params['source']) && !in_array($params['source'], $account['roles'])) {
            $this->roleService->addRoleAccount($account, $params['source']);

            // Set new role
            $account['roles'][] = $params['source'];
            $account['roles']   = array_values($account['roles']);
        }

        // reset permission
        $account['permission'] = array_values($account['permission']);

        // Set cache params
        $cacheParams = [
            'account'      => [
                'id'                  => (int)$account['id'],
                'name'                => $account['name'],
                'email'               => $account['email'],
                'identity'            => $account['identity'],
                'mobile'              => $account['mobile'],
                'first_name'          => $account['first_name'],
                'last_name'           => $account['last_name'],
                'time_created'        => $account['time_created'],
                'last_login'          => $account['last_login'],
                'has_password'        => $account['has_password'],
                'multi_factor_global' => $account['multi_factor_global'],
                'multi_factor_status' => $account['multi_factor_status'],
                'multi_factor_verify' => $account['multi_factor_verify'],
                'is_company_setup'    => $account['is_company_setup'],
            ],
            'roles'        => $account['roles'],
            'permission'   => $account['permission'],
            'access_keys'  => (isset($user['access_keys']) && !empty($user['access_keys']))
                ? array_unique(array_merge($user['access_keys'], [$accessToken['key']]))
                : [$accessToken['key']],
            'refresh_keys' => (isset($user['refresh_keys']) && !empty($user['refresh_keys']))
                ? array_unique(array_merge($user['refresh_keys'], [$refreshToken['key']]))
                : [$refreshToken['key']],
            'multi_factor' => (isset($user['multi_factor']) && !empty($user['multi_factor']))
                ? array_merge($user['multi_factor'], $multiFactor)
                : $multiFactor,
        ];

        // Manage single session policy if set
        if (isset($this->config['login']['session_policy']) && $this->config['login']['session_policy'] == 'single') {
            $cacheParams['access_keys']  = [$accessToken['key']];
            $cacheParams['refresh_keys'] = [$refreshToken['key']];
        }

        // Set/Update user data to cache
        $this->cacheService->setUser($account['id'], $cacheParams);

        // Save log
        $this->historyService->logger('login', ['request' => $params, 'account' => $account]);

        return [
            'result' => true,
            'data'   => $account,
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function postLoginError($params): array
    {
        // Get a user account
        $account = $this->getAccount([$this->identityColumn => $params['identity']]);

        // Check account exists
        if (!empty($account)) {
            // Save log
            $this->historyService->logger('failedLogin', ['request' => $params, 'account' => $account]);

            // Save failed attempts
            $result = $this->accountLoginAttempts->incrementFailedAttempts(
                ['type' => 'id', 'user_id' => (int)$account['id'], 'security_stream' => $params['security_stream']]
            );
        } else {
            // Save failed attempts
            $userIp = $params['security_stream']['ip']['data']['client_ip'] ?? '';
            $result = $this->accountLoginAttempts->incrementFailedAttempts(
                ['type' => 'ip', 'user_ip' => $userIp, 'security_stream' => $params['security_stream']]
            );
        }

        // Check an attempt result
        if (!$result['can_try']) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => $this->accountLoginAttempts->getErrorMessage(),
                ],
                'status' => $this->accountLoginAttempts->getStatusCode(),
            ];
        }

        // Set message
        $message = 'Invalid Username or Password';
        if (isset($result['attempts_remind']) && is_numeric($result['attempts_remind']) && !$params['security_stream']['ip']['data']['in_whitelist']) {
            $message = sprintf('Invalid Username or Password, You can try %s more times', $result['attempts_remind']);
        }

        return [
            'result' => false,
            'data'   => [],
            'error'  => [
                'message' => $message,
            ],
            'status' => StatusCodeInterface::STATUS_UNAUTHORIZED,
        ];
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function perMobileLogin($params): array
    {
        // Set new password as OTP
        $otpCode   = Rand::getInteger(100000, 999999);
        $otpExpire = (time() + 120);
        $isNew     = 0;

        // Check account exist
        $account = $this->getAccount(['mobile' => $params['mobile']]);

        // Create account if not exist
        // Update OTP password if account exist
        if (empty($account)) {
            $account = $this->addAccount(
                [
                    'mobile'     => $params['mobile'],
                    'first_name' => $params['first_name'] ?? null,
                    'last_name'  => $params['last_name'] ?? null,
                    'source'     => $params['source'] ?? null,
                    'otp'        => $otpCode,
                ]
            );

            // Set is new
            $isNew = 1;
        } else {
            $otp = $this->generatePassword($otpCode);
            $this->accountRepository->updateAccount((int)$account['id'], ['otp' => $otp]);
        }

        // Set user cache
        $this->cacheService->setUser(
            $account['id'],
            [
                'account' => [
                    'id'           => (int)$account['id'],
                    'name'         => $account['name'],
                    'email'        => $account['email'],
                    'identity'     => $account['identity'],
                    'mobile'       => $account['mobile'],
                    'time_created' => $account['time_created'],
                    'last_login'   => $user['account']['last_login'] ?? time(),
                ],
                'otp'     => [
                    'code'        => $otpCode,
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
                'mobile'  => $account['mobile'],
                'source'  => $params['source'] ?? '',
            ],
        ];

        // Send notification
        $this->notificationService->send($notificationParams);

        // Set result
        return [
            'result' => true,
            'data'   => [
                'message'    => 'Verify code send to your mobile number !',
                'name'       => $account['name'],
                'mobile'     => $account['mobile'],
                'is_new'     => $isNew,
                'otp_expire' => $otpExpire,
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function preMailLogin($params): array
    {
        // Set new password as OTP
        $otpCode   = Rand::getInteger(100000, 999999);
        $otpExpire = (time() + 180);
        $isNew     = 0;

        // Check account exist
        $account = $this->getAccount(['email' => $params['email']]);

        // Create account if not exist
        // Update OTP password if account exist
        if (empty($account)) {
            $account = $this->addAccount(
                [
                    'email'      => $params['email'],
                    'source'     => $params['source'] ?? null,
                    'first_name' => $params['first_name'] ?? null,
                    'last_name'  => $params['last_name'] ?? null,
                    'otp'        => $otpCode,
                ]
            );

            // Set is new
            $isNew = 1;
        } else {
            $otp = $this->generatePassword($otpCode);
            $this->accountRepository->updateAccount((int)$account['id'], ['otp' => $otp]);
        }

        // Set user cache
        $this->cacheService->setUser(
            $account['id'],
            [
                'account' => [
                    'id'           => (int)$account['id'],
                    'name'         => $account['name'],
                    'email'        => $account['email'],
                    'identity'     => $account['identity'],
                    'mobile'       => $account['mobile'],
                    'time_created' => $account['time_created'],
                    'last_login'   => $user['account']['last_login'] ?? time(),
                ],
                'otp'     => [
                    'code'        => $otpCode,
                    'time_expire' => $otpExpire,
                ],
            ]
        );

        // Set notification params
        $notificationParams = [
            'email' => [
                'to'      => [
                    'email' => $account['email'],
                    'name'  => $account['name'],
                ],
                'subject' => $this->config['otp_email']['subject'],
                'body'    => sprintf($this->config['otp_email']['body'], $otpCode),
            ],
        ];

        // Send notification
        $this->notificationService->send($notificationParams);

        // Set result
        return [
            'result' => true,
            'data'   => [
                'message'    => 'Verify code send to your email !',
                'name'       => $account['name'],
                'email'      => $account['email'],
                'is_new'     => $isNew,
                'otp_expire' => $otpExpire,
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function addAccount($params, array $operator = []): array
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

        $otp        = null;
        $credential = null;
        if (isset($params['credential']) && !empty($params['credential'])) {
            $credential = $this->generatePassword($params['credential']);
        }
        if (isset($params['otp']) && !empty($params['otp'])) {
            $otp = $this->generatePassword($params['otp']);
        }

        $paramsAccount = [
            'name'         => $params['name'] ?? null,
            'email'        => $params['email'] ?? null,
            'identity'     => $params['identity'] ?? null,
            'mobile'       => $params['mobile'] ?? null,
            'credential'   => $credential,
            'otp'          => $otp,
            'status'       => $this->userRegisterStatus(),
            'time_created' => time(),
        ];

        $account = $this->accountRepository->addAccount($paramsAccount);
        $account = $this->canonizeAccount($account);

        // Save log
        $this->historyService->logger('register', ['request' => $params, 'account' => $account, 'operator' => $operator]);


        // Clean up
        $profileParams     = [
            'user_id' => (int)$account['id'],
        ];
        $informationParams = [];
        foreach ($params as $key => $value) {
            if (in_array($key, $this->profileFields)) {
                if (empty($value)) {
                    $profileParams[$key] = null;
                } else {
                    $profileParams[$key] = $value;
                }
            }

            if (in_array($key, $this->informationFields)) {
                if (empty($value)) {
                    $informationParams[$key] = null;
                } else {
                    $informationParams[$key] = $value;
                }
            }
        }

        $profileParams['information'] = json_encode(
            $informationParams,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
        );

        $profile = $this->accountRepository->addProfile($profileParams);
        $profile = $this->canonizeProfile($profile);

        $account = array_merge($account, $profile);

        // Set user roles
        $this->roleService->addDefaultRoles($account, $operator);

        // Get roles
        $roles = $this->roleService->getRoleAccount((int)$account['id']);

        // Set source roles params
        if (isset($params['source']) && !empty($params['source']) && is_string($params['source']) && !in_array($params['source'], $roles)) {
            $this->roleService->addRoleAccount($account, $params['source']);
        }

        return $account;
    }

    /**
     * @param       $params
     * @param       $account
     * @param array $operator
     *
     * @return array
     */
    public function addRoleAccountByAdmin($params, $account, array $operator = []): void
    {
        // Set user roles that receive from service
        if (isset($params['roles'])) {
            $roles = explode(',', $params['roles']);
            foreach ($roles as $role) {
                if ($role != 'member') {
                    $this->roleService->addRoleAccount($account, $role, $role == 'admin' ? 'admin' : 'api', $operator);
                }
            }

            // Make user logout after edit role
            $this->logout(['user_id' => (int)$account['id'], 'all_session' => 1]);
        }
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccount($params): array
    {
        $account = $this->accountRepository->getAccount($params);
        return $this->canonizeAccount($account);
    }

    /**
     * @param       $params
     *
     * @return array
     */
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
                'email'           => $params['email'] ?? null,
                'mobile'          => $params['mobile'] ?? null,
                'identity'        => $params['identity'] ?? null,
                'credential'      => $params['credential'] ?? null,
                'source'          => $params['source'] ?? null,
                'first_name'      => $params['first_name'] ?? null,
                'last_name'       => $params['last_name'] ?? null,
                'id_number'       => $params['id_number'] ?? null,
                'birthdate'       => $params['birthdate'] ?? null,
                'gender'          => $params['gender'] ?? null,
                'avatar'          => $params['avatar'] ?? null,
                'ip_register'     => $params['ip_register'] ?? null,
                'register_source' => $params['register_source'] ?? null,
                'homepage'        => $params['homepage'] ?? null,
                'phone'           => $params['phone'] ?? null,
                'address_1'       => $params['address_1'] ?? null,
                'address_2'       => $params['address_2'] ?? null,
                'item_id'         => $params['item_id'] ?? 0,
                'country'         => $params['country'] ?? null,
                'state'           => $params['state'] ?? null,
                'city'            => $params['city'] ?? null,
                'zip_code'        => $params['zip_code'] ?? null,
            ];

            $account = $this->addAccount($addParams);
        } else {
            $profile = $this->getProfile(['user_id' => (int)$account['id']]);
            $account = array_merge($account, $profile);
        }

        return $account;
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function addOrUpdateAccount($params): array
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
                'email'           => $params['email'] ?? null,
                'mobile'          => $params['mobile'] ?? null,
                'identity'        => $params['identity'] ?? null,
                'credential'      => $params['credential'] ?? null,
                'source'          => $params['source'] ?? null,
                'first_name'      => $params['first_name'] ?? null,
                'last_name'       => $params['last_name'] ?? null,
                'id_number'       => $params['id_number'] ?? null,
                'birthdate'       => $params['birthdate'] ?? null,
                'gender'          => $params['gender'] ?? null,
                'avatar'          => $params['avatar'] ?? null,
                'ip_register'     => $params['ip_register'] ?? null,
                'register_source' => $params['register_source'] ?? null,
                'homepage'        => $params['homepage'] ?? null,
                'phone'           => $params['phone'] ?? null,
                'address_1'       => $params['address_1'] ?? null,
                'address_2'       => $params['address_2'] ?? null,
                'item_id'         => $params['item_id'] ?? 0,
                'country'         => $params['country'] ?? null,
                'state'           => $params['state'] ?? null,
                'city'            => $params['city'] ?? null,
                'zip_code'        => $params['zip_code'] ?? null,
            ];

            $account = $this->addAccount($addParams);
        } else {
            $account = $this->updateAccount($params, $account);

            // Get roles
            $roles = $this->roleService->getRoleAccount((int)$account['id']);

            // Set new role
            if (isset($params['source']) && !empty($params['source']) && is_string($params['source']) && !in_array($params['source'], $roles)) {
                $this->roleService->addRoleAccount($account, $params['source']);

                // Make user logout after edit role
                $this->logout(['user_id' => (int)$account['id'], 'all_session' => 1]);
            }

            // Delete old role
            if (isset($params['remove_role'])
                && !empty($params['remove_role'])
                && is_string($params['remove_role'])
                && in_array($params['remove_role'], $roles)
            ) {
                $this->roleService->deleteRoleAccount($account, $params['remove_role']);

                // Make user logout after edit role
                $this->logout(['user_id' => (int)$account['id'], 'all_session' => 1]);
            }
        }

        return $account;
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getUserFromCache($id): array
    {
        $user = $this->cacheService->getUser($id);

        return [
            'account' => $user['account'],
            'roles'   => $user['roles'],
        ];
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getUserFromCacheFull($id): array
    {
        return $this->cacheService->getUser($id);
    }

    /**
     * @param array $params
     *
     * @return int
     */
    public function getAccountCount(array $params = []): int
    {
        return $this->accountRepository->getAccountCount($params);
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccountList($params): array
    {
        $limit  = $params['limit'] ?? 10;
        $page   = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ((int)$page - 1) * (int)$limit;

        // Set params
        $listParams = [
            'page'   => (int)$page,
            'limit'  => (int)$limit,
            'order'  => $order,
            'offset' => $offset,
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
        if (isset($params['mobiles']) && !empty($params['mobiles'])) {
            $listParams['mobiles'] = $params['mobiles'];
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['status'])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['data_from']) && !empty($params['data_from'])) {
            $listParams['data_from'] = strtotime(
                ($params['data_from']) != null
                    ? sprintf('%s 00:00:00', $params['data_from'])
                    : sprintf('%s 00:00:00', date('Y-m-d', strtotime('-1 month')))
            );
        }
        if (isset($params['data_to']) && !empty($params['data_to'])) {
            $listParams['data_to'] = strtotime(
                ($params['data_to']) != null
                    ? sprintf('%s 00:00:00', $params['data_to'])
                    : sprintf('%s 23:59:59', date('Y-m-d'))
            );
        }

        $filters = $this->prepareFilter($params);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $itemIdList = [];
                $rowSet     = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $itemIdList[] = $this->canonizeAccountId($row);
                }
                $listParams['id'] = $itemIdList;
            }
        }

        // Get list
        $list   = [];
        $rowSet = $this->accountRepository->getAccountList($listParams);
        foreach ($rowSet as $row) {
            $list[$row->getId()] = $this->canonizeAccount($row);
        }

        // Get count
        $count = $this->accountRepository->getAccountCount($listParams);

        // Get roles
        $roleList = $this->roleService->getRoleAccountList(array_keys($list));
        foreach ($list as $id => $user) {
            $list[$id]['roles'] = isset($roleList[$user['id']]) ? $roleList[$user['id']] : $this->emptyRoles;
        }

        return [
            'list'      => array_values($list),
            'roles'     => $roleList,
            'paginator' => [
                'count' => $count,
                'limit' => $limit,
                'page'  => $page,
            ],
        ];
    }

    /**
     * @param       $account
     *
     * @return array
     */
    public function viewAccount($account): array
    {
        // Get user from cache
        $user = $this->cacheService->getUser((int)$account['id']);

        // Check user has password or not
        $account['has_password'] = $this->hasPassword((int)$account['id']);

        // Set profile params
        $profile = $this->getProfile(['user_id' => (int)$account['id']]);

        // Sync profile and account
        $account = array_merge($account, $profile);

        // Get roles
        $account['roles']      = $this->roleService->getRoleAccount((int)$account['id']);
        $account['roles_full'] = $this->roleService->canonizeAccountRole($account['roles']);

        // Set permission
        if (isset($this->config['login']['permission']) && (int)$this->config['login']['permission'] === 1) {
            $permissionParams = [
                'section' => 'api',
                'role'    => $account['roles'],
            ];

            $account['permission'] = $this->permissionService->getPermissionRole($permissionParams);
        }

        // Check company setup
        if (isset($this->config['login']['get_company']) && (int)$this->config['login']['get_company'] === 1) {
            $isCompanySetup = false;

            if (isset($user['authorization']['company']['is_company_setup'])) {
                $isCompanySetup = $user['authorization']['company']['is_company_setup'];
            } elseif (isset($user['account']['is_company_setup'])) {
                $isCompanySetup = $user['account']['is_company_setup'];
            }

            $account['is_company_setup'] = $isCompanySetup;
        }

        // Check permission for company package
        if (isset($this->config['login']['permission_package']) && (int)$this->config['login']['permission_package'] === 1) {
            if (isset($user['authorization']['package_id']) && (int)$user['authorization']['package_id'] > 0) {
                $key     = sprintf('package-%s', $user['authorization']['package_id']);
                $package = $this->cacheService->getItem($key);

                // Check and clean account permission base of package access
                if (
                    !empty($package)
                    && isset($package['information']['access'])
                    && !empty($package['information']['access'])
                ) {
                    $account['permission'] = array_intersect(array_values($account['permission']), array_values($package['information']['access']));
                }
            }
        }

        // reset permission
        $account['permission'] = array_values($account['permission']);

        // Set cache params
        $cacheParams = [
            'account'    => [
                'id'                  => (int)$account['id'],
                'name'                => $account['name'],
                'email'               => $account['email'],
                'identity'            => $account['identity'],
                'mobile'              => $account['mobile'],
                'first_name'          => $account['first_name'],
                'last_name'           => $account['last_name'],
                'time_created'        => $account['time_created'],
                'last_login'          => $account['last_login'],
                'has_password'        => $account['has_password'],
                'multi_factor_global' => $account['multi_factor_global'],
                'multi_factor_status' => $account['multi_factor_status'],
                'multi_factor_verify' => $account['multi_factor_verify'],
                'is_company_setup'    => $account['is_company_setup'],
            ],
            'roles'      => $account['roles'],
            'permission' => $account['permission'],
        ];

        // Set/Update user data to cache
        $this->cacheService->setUser($account['id'], $cacheParams);

        return $account;
    }

    /**
     * @param       $params
     * @param       $account
     * @param array $operator
     *
     * @return array
     */
    public function updateAccount($params, $account, array $operator = []): array
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
        $accountParams     = [];
        $profileParams     = [];
        $informationParams = [];
        foreach ($params as $key => $value) {
            if (in_array($key, $this->accountFields)) {
                if (!empty($value)) {
                    $accountParams[$key] = $value;
                }
            }

            if (in_array($key, $this->profileFields)) {
                if (empty($value)) {
                    $profileParams[$key] = null;
                } else {
                    $profileParams[$key] = $value;
                }
            }

            if (in_array($key, $this->informationFields)) {
                if (empty($value)) {
                    $informationParams[$key] = '';
                } else {
                    $informationParams[$key] = $value;
                }
            }
        }

        //if (isset($params['device_type'])) {
        //    $informationParams['device_type'] = $params['device_type'];
        //}
        //if (isset($params['device_token'])) {
        //    $informationParams['device_token'] = $params['device_token'];
        //}

        if (!empty($informationParams)) {
            $profile = $this->getProfile(['user_id' => (int)$account['id']]);
            foreach ($profile['information'] as $key => $value) {
                if (isset($informationParams[$key]) && empty($informationParams[$key])) {
                    $informationParams[$key] = null;
                } elseif (!isset($informationParams[$key])) {
                    $informationParams[$key] = $value;
                }
            }

            //if (isset($params['device_type'])) {
            //    $informationParams['device_type'] = $params['device_type'];
            //}
            //if (isset($params['device_token'])) {
            //    $informationParams['device_token'] = $params['device_token'];
            //}

            $profileParams['information'] = json_encode(
                $informationParams,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
            );
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

        // Set/Update user data to cache
        $this->cacheService->setUser(
            $account['id'],
            [
                'account' => [
                    'id'                  => $account['id'],
                    'name'                => $account['name'],
                    'email'               => $account['email'],
                    'identity'            => $account['identity'],
                    'mobile'              => $account['mobile'],
                    'last_login'          => $user['account']['last_login'] ?? time(),
                    'status'              => (int)$account['status'],
                    'time_created'        => $account['time_created'] ?? '',
                    'multi_factor_status' => (int)$account['multi_factor_status'],
                ],
            ]
        );

        // Save log
        $this->historyService->logger('update', ['request' => $params, 'account' => $account, 'operator' => $operator]);

        return array_merge($account, $profile);
    }

    /**
     * @param        $roles
     * @param        $account
     * @param string $section
     * @param array  $operator
     *
     * @return void
     */
    public function updateAccountRoles($roles, $account, string $section = 'api', array $operator = []): void
    {
        $this->roleService->updateAccountRoles($roles, $account, $section, $operator);

        // Make user logout after edit role
        $this->logout(['user_id' => (int)$account['id'], 'all_session' => 1]);
    }

    /**
     * @param       $params
     * @param       $account
     *
     * @return void
     */
    public function updatedDeviceToken($params, $account): void
    {
        // Update cache
        $this->cacheService->setUserItem($account['id'], 'device_tokens', $params['device_token']);

        // Save log
        $this->historyService->logger('updatedDeviceToken', ['request' => $params, 'account' => $account]);
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getProfile($params): array
    {
        $profile = $this->accountRepository->getProfile($params);
        return $this->canonizeProfile($profile);
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccountProfileList($params): array
    {
        $limit  = $params['limit'] ?? 10;
        $page   = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ((int)$page - 1) * (int)$limit;

        // Set params
        $listParams = [
            'page'   => (int)$page,
            'limit'  => (int)$limit,
            'order'  => $order,
            'offset' => $offset,
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
        if (isset($params['mobiles']) && !empty($params['mobiles'])) {
            $listParams['mobiles'] = $params['mobiles'];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $listParams['id'] = $params['id'];
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['data_from']) && !empty($params['data_from'])) {
            $listParams['data_from'] = strtotime(
                ($params['data_from']) != null
                    ? sprintf('%s 00:00:00', $params['data_from'])
                    : sprintf('%s 00:00:00', date('Y-m-d', strtotime('-1 month')))
            );
        }
        if (isset($params['data_to']) && !empty($params['data_to'])) {
            $listParams['data_to'] = strtotime(
                ($params['data_to']) != null
                    ? sprintf('%s 00:00:00', $params['data_to'])
                    : sprintf('%s 23:59:59', date('Y-m-d'))
            );
        }

        $filters = $this->prepareFilter($params);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $itemIdList = [];
                $rowSet     = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $itemIdList[] = $this->canonizeAccountId($row);
                }
                $listParams['id'] = $itemIdList;
            }
        }

        // Get list
        $list   = [];
        $rowSet = $this->accountRepository->getAccountProfileList($listParams);
        foreach ($rowSet as $row) {
            $list[$row->getId()] = $this->canonizeAccountProfile($row);
        }

        // Get count
        $count = $this->accountRepository->getAccountCount($listParams);

        // Get roles
        $roleList = $this->roleService->getRoleAccountList(array_keys($list));
        foreach ($list as $id => $user) {
            $list[$id]['roles'] = isset($roleList[$user['id']]) ? $roleList[$user['id']] : $this->emptyRoles;
        }

        return [
            'list'      => array_values($list),
            'paginator' => [
                'count' => $count,
                'limit' => $limit,
                'page'  => $page,
            ],
        ];
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccountProfile($params): array
    {
        return $this->canonizeAccountProfile($this->accountRepository->getAccountProfile($params));
    }

    /**
     * @param       $params
     * @param array $account
     *
     * @return array
     */
    public function addPassword($params, array $account = []): array
    {
        // Set user id
        $userId = $account['id'] ?? $params['user_id'];

        // Generate password
        $credential = $this->generatePassword($params['credential']);

        // Update account
        $this->accountRepository->updateAccount((int)$userId, ['credential' => $credential]);

        // Save log
        $account = empty($account) ? ['id' => $userId] : $account;
        $this->historyService->logger('addPassword', ['request' => $params, 'account' => $account]);

        return [
            'result' => true,
            'data'   => [
                'message' => 'Password set successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param       $account
     * @param array $operator
     *
     * @return array
     */
    public function updatePassword($params, $account, array $operator = []): array
    {
        $hash = $this->accountRepository->getAccountPassword((int)$account['id']);
        if ($this->passwordEqualityCheck($params['current_credential'], $hash)) {
            $credential = $this->generatePassword($params['new_credential']);
            $this->accountRepository->updateAccount((int)$account['id'], ['credential' => $credential]);

            // Save log
            $this->historyService->logger('updatePassword', ['request' => $params, 'account' => $account, 'operator' => $operator]);

            $result = [
                'result' => true,
                'data'   => [
                    'message' => 'Password update successfully !',
                ],
                'error'  => [],
            ];
        } else {
            // Save log
            $this->historyService->logger('failedUpdatePassword', ['request' => $params, 'account' => $account, 'operator' => $operator]);

            $result = [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error to update password !',
                ],
                'status' => StatusCodeInterface::STATUS_UNAUTHORIZED,
            ];
        }

        return $result;
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function updatePasswordByAdmin($params, array $operator = []): array
    {
        // Set password
        $credential = $this->generatePassword($params['credential']);

        // Update user
        $this->accountRepository->updateAccount((int)$params['user_id'], ['credential' => $credential]);

        // Save log
        $this->historyService->logger(
            'updatePasswordByAdmin',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Password set successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function updateStatusByAdmin($params, array $operator = []): array
    {
        // Set status
        $params['status'] = $params['status'] ?? 0;

        // Set params
        $paramsList = [
            'status'                                               => $params['status'],
            $params['status'] ? 'time_activated' : 'time_disabled' => time(),
        ];

        // Update user
        $this->accountRepository->updateAccount((int)$params['user_id'], $paramsList);

        // Delete cache
        if ($params['status'] == 0) {
            $this->cacheService->deleteUser((int)$params['user_id']);
        }

        // Save log
        $this->historyService->logger(
            'updateStatusByAdmin',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Status change successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function deleteUserByAdmin($params, array $operator = []): array
    {
        // Delete user
        $this->accountRepository->updateAccount((int)$params['user_id'], ['status' => 0, 'time_deleted' => time()]);

        // Delete cache
        $this->cacheService->deleteUser((int)$params['user_id']);

        // Save log
        $this->historyService->logger(
            'deleteUserByAdmin',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Delete user successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param $userId
     *
     * @return bool
     */
    public function hasPassword($userId): bool
    {
        $hash = $this->accountRepository->getAccountPassword((int)$userId);

        if (empty($hash)) {
            return false;
        }

        return true;
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function refreshToken($params): array
    {
        $accessToken = $this->tokenService->encryptToken(
            [
                'user_id' => $params['user_id'],
                'type'    => 'access',
                'roles'   => [
                    'member',
                ],
            ]
        );

        // Update cache
        $this->cacheService->setUserItem($params['user_id'], 'access_keys', $accessToken['key']);

        // Set result array
        return [
            'result' => true,
            'data'   => [
                'access_token' => $accessToken['token'],
            ],
            'error'  => [],
        ];
    }

    /**
     * @param     $type
     * @param     $value
     * @param int $id
     *
     * @return bool
     */
    public function isDuplicated($type, $value, int $id = 0): bool
    {
        return (bool)$this->accountRepository->duplicatedAccount(
            [
                'field' => $type,
                'value' => $value,
                'id'    => $id,
            ]
        );
    }

    /**
     * @return int
     */
    public function userRegisterStatus(): int
    {
        return $this->config['register']['status'] ?? 1;
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function prepareFilter($params): array
    {
        // Set filter list
        $filters = [];
        foreach ($params as $key => $value) {
            if ($key == 'roles') {
                if (($value != '') && !empty($value) && ($value != null)) {
                    $filters[$key] = [
                        'role'  => $key,
                        'value' => explode(',', $value),
                        'type'  => 'string',
                    ];
                }
            }
        }
        return $filters;
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccountListByOperator($params): array
    {
        $limit  = $params['limit'] ?? 10;
        $page   = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ((int)$page - 1) * (int)$limit;

        // Set params
        $listParams = [
            'page'   => (int)$page,
            'limit'  => (int)$limit,
            'order'  => $order,
            'offset' => $offset,
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
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['status'])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['data_from']) && !empty($params['data_from'])) {
            $listParams['data_from'] = strtotime(
                ($params['data_from']) != null
                    ? sprintf('%s 00:00:00', $params['data_from'])
                    : sprintf('%s 00:00:00', date('Y-m-d', strtotime('-1 month')))
            );
        }
        if (isset($params['data_to']) && !empty($params['data_to'])) {
            $listParams['data_to'] = strtotime(
                ($params['data_to']) != null
                    ? sprintf('%s 00:00:00', $params['data_to'])
                    : sprintf('%s 23:59:59', date('Y-m-d'))
            );
        }

        $filters = $this->prepareFilter($params);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $itemIdList = [];
                $rowSet     = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $itemIdList[] = $this->canonizeAccountId($row);
                }
                $listParams['id'] = $itemIdList;
            }
        }

        $notAllow = $this->prepareFilter(['roles' => implode(',', $this->roleService->getAdminRoleList())]);
        if (!empty($notAllow)) {
            foreach ($notAllow as $filter) {
                $notAllowItemIdList = [];
                $rowSet             = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $notAllowItemIdList[] = $this->canonizeAccountId($row);
                }
                $listParams['not_allowed_id'] = $notAllowItemIdList;
            }
        }

        // Get list
        $list   = [];
        $rowSet = $this->accountRepository->getAccountList($listParams);
        foreach ($rowSet as $row) {
            $list[$row->getId()] = $this->canonizeAccount($row);
        }

        // Get count
        $count = $this->accountRepository->getAccountCount($listParams);

        // Get roles
        $roleList = $this->roleService->getRoleAccountList(array_keys($list));
        foreach ($list as $id => $user) {
            $list[$id]['roles'] = isset($roleList[$user['id']]) ? $roleList[$user['id']] : $this->emptyRoles;
        }

        return [
            'list'      => array_values($list),
            'roles'     => $roleList,
            'paginator' => [
                'count' => $count,
                'limit' => $limit,
                'page'  => $page,
            ],
        ];
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccountByOperator($params): array
    {
        $notAllow = $this->prepareFilter(['roles' => implode(',', $this->roleService->getAdminRoleList())]);
        if (!empty($notAllow)) {
            foreach ($notAllow as $filter) {
                $notAllowItemIdList = [];
                $rowSet             = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $notAllowItemIdList[] = $this->canonizeAccountId($row);
                }
                $params['not_allowed_id'] = $notAllowItemIdList;
            }
        }
        $account = $this->accountRepository->getAccount($params);
        return $this->canonizeAccount($account);
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getProfileByOperator($params): array
    {
        $notAllow = $this->prepareFilter(['roles' => implode(',', $this->roleService->getAdminRoleList())]);
        if (!empty($notAllow)) {
            foreach ($notAllow as $filter) {
                $notAllowItemIdList = [];
                $rowSet             = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $notAllowItemIdList[] = $this->canonizeAccountId($row);
                }
                $params['not_allowed_id'] = $notAllowItemIdList;
            }
        }
        $profile = $this->accountRepository->getProfile($params);
        return $this->canonizeProfile($profile);
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function updateStatusByOperator($params, array $operator = []): array
    {
        // Set status
        $params['status'] = $params['status'] ?? 0;

        // Set params
        $paramsList = [
            'status'                                               => $params['status'],
            $params['status'] ? 'time_activated' : 'time_disabled' => time(),
        ];

        // Update user
        $this->accountRepository->updateAccount((int)$params['user_id'], $paramsList);

        // Delete cache
        if ($params['status'] == 0) {
            $this->cacheService->deleteUser((int)$params['user_id']);
        }

        // Save log
        $this->historyService->logger(
            'updateStatusByOperator',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Status change successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function deleteUserByOperator($params, array $operator = []): array
    {
        $this->accountRepository->updateAccount((int)$params['user_id'], ['status' => 0, 'time_deleted' => time()]);
        $this->cacheService->deleteUser((int)$params['user_id']);

        // Save log
        $this->historyService->logger(
            'deleteUserByOperator',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Delete user successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function updatePasswordByOperator($params, array $operator = []): array
    {
        $credential = $this->generatePassword($params['credential']);

        // Update account
        $this->accountRepository->updateAccount((int)$params['user_id'], ['credential' => $credential]);

        // Save log
        $this->historyService->logger(
            'updatePasswordByOperator',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Password set successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return void
     */
    public function resetAccount($params, array $operator = []): void
    {
        switch ($params['type']) {
            case 'password':
                // Update account
                $this->accountRepository->updateAccount((int)$params['user_id'], ['credential' => null]);

                // Save log
                $this->historyService->logger(
                    'resetPasswordByOperator',
                    ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
                );
                break;

            case 'mfa':
                // Update account
                $this->accountRepository->updateAccount((int)$params['user_id'], ['multi_factor_status' => 0, 'multi_factor_secret' => null]);

                // Save log
                $this->historyService->logger(
                    'resetMfaByOperator',
                    ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
                );
                break;

            case 'avatar':
                // Set avatar params
                $avatar = [
                    'avatar'        => '',
                    'avatar_params' => [],
                ];

                // Set account
                $account = ['id' => (int)$params['user_id']];

                // Update profile
                $this->updateAccount($avatar, $account, $operator);

                // Save log
                $this->historyService->logger(
                    'resetAvatarByOperator',
                    ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
                );
                break;
        }

        // Make user logout after edit role
        $this->logout(['user_id' => (int)$params['user_id'], 'all_session' => 1]);
    }

    /**
     * @param $account
     *
     * @return array
     * @throws TwoFactorAuthException
     */
    public function requestMfa($account): array
    {
        // Set multi factor
        $multiFactorGlobal = (int)$this->config['multi_factor']['status'] ?? 0;

        // Get mfa information
        $mfa = $this->accountRepository->getMultiFactor((int)$account['id']);

        // Call MultiFactorAuth
        $multiFactorAuth = new TwoFactorAuth($this->config['sitename'], 6, 30, Algorithm::Sha1, new EndroidQrCodeProvider());

        // Set data
        $secret = null;
        $image  = null;
        if (!isset($mfa['multi_factor_secret']) || empty($mfa['multi_factor_secret'])) {
            $secret = $multiFactorAuth->createSecret(160);
            $image  = $multiFactorAuth->getQRCodeImageAsDataUri($account['email'], $secret);
        }

        return [
            'multi_factor_status' => $mfa['multi_factor_status'],
            'multi_factor_secret' => $secret,
            'multi_factor_image'  => $image,
            'multi_factor_global' => $multiFactorGlobal,
            'multi_factor_verify' => 0,
        ];
    }

    /**
     * @param $account
     * @param $params
     * @param $tokenId
     *
     * @return array
     * @throws TwoFactorAuthException
     */
    public function verifyMfa($account, $params, $tokenId): array
    {
        // Set multi factor
        $multiFactorGlobal = (int)$this->config['multi_factor']['status'] ?? 0;

        // Get multifactor information
        $mfa = $this->accountRepository->getMultiFactor((int)$account['id']);

        // Call MultiFactorAuth
        $multiFactorAuth = new TwoFactorAuth($this->config['sitename'], 6, 30, Algorithm::Sha1, new EndroidQrCodeProvider());

        // check secret code and verify code
        $result = false;
        if (
            isset($mfa['multi_factor_status'])
            && (int)$mfa['multi_factor_status'] === 1
            && isset($mfa['multi_factor_secret'])
            && !empty($mfa['multi_factor_secret'])
        ) {
            $result = $multiFactorAuth->verifyCode($mfa['multi_factor_secret'], $params['verification']);
        } elseif (isset($params['multi_factor_secret']) && !empty($params['multi_factor_secret'])) {
            $result = $multiFactorAuth->verifyCode($params['multi_factor_secret'], $params['verification']);
        }

        // Check a result
        if (!$result) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error to verify code !',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ],
                'status' => StatusCodeInterface::STATUS_FORBIDDEN,
            ];
        }

        // Get from cache
        $user = $this->cacheService->getUser($account['id']);

        // update multi factor
        $user['multi_factor'][$tokenId]['multi_factor_status'] = 1;
        $user['multi_factor'][$tokenId]['multi_factor_verify'] = 1;

        // Update cache
        $this->cacheService->setUser($account['id'], ['multi_factor' => $user['multi_factor']]);

        // Check update profile
        if ((int)$mfa['multi_factor_status'] === 0) {
            // Set login params
            $updateParams = [
                'multi_factor_status' => 1,
                'multi_factor_secret' => $params['multi_factor_secret'],
            ];

            // Update account
            $this->updateAccount($updateParams, $account);
        }

        return [
            'result' => true,
            'data'   => [
                'message'             => 'Congratulations! Your multi-factor authentication (MFA) has been successfully verified. Your account is now secure.',
                'multi_factor_global' => $multiFactorGlobal,
                'multi_factor_status' => 1,
                'multi_factor_verify' => 1,
            ],
            'error'  => [],
        ];
    }

    /**
     * @param mixed $password
     *
     * @return string
     */
    protected function generatePassword(mixed $password): string
    {
        switch ($this->hashPattern) {
            default:
            case'bcrypt':
                $bcrypt = new Bcrypt();
                $hash   = $bcrypt->create($password);
                break;
            case'sha512':
                $hash = hash('sha512', $password);
                break;
        }

        return $hash;
    }

    /**
     * @param mixed $credential
     * @param mixed $hash
     *
     * @return boolean
     */
    protected function passwordEqualityCheck(mixed $credential, mixed $hash): bool
    {
        switch ($this->hashPattern) {
            default:
            case'bcrypt':
                $bcrypt = new Bcrypt();
                $result = $bcrypt->verify($credential, $hash);
                break;
            case'sha512':
                $result = hash_equals($hash, hash('sha512', $credential));
                break;
        }

        return $result;
    }

    /**
     * @param $account
     *
     * @return array
     */
    public function canonizeAccount($account): array
    {
        if (empty($account)) {
            return [];
        }

        if (is_object($account)) {
            $account = [
                'id'                  => (int)$account->getId(),
                'name'                => $account->getName(),
                'identity'            => $account->getIdentity(),
                'email'               => $account->getEmail(),
                'mobile'              => $account->getMobile(),
                'status'              => (int)$account->getStatus(),
                'time_created'        => $account->getTimeCreated(),
                'multi_factor_status' => (int)$account->getMultiFactorStatus(),
            ];
        } else {
            $account = [
                'id'                  => (int)$account['id'],
                'name'                => $account['name'] ?? '',
                'email'               => $account['email'] ?? '',
                'identity'            => $account['identity'] ?? '',
                'mobile'              => $account['mobile'] ?? '',
                'status'              => (int)$account['status'],
                'time_created'        => $account['time_created'] ?? '',
                'multi_factor_status' => (int)$account['multi_factor_status'],
            ];
        }

        // Set time
        $account['time_created_view'] = ' - ';
        if (!empty($account['time_created']) && is_numeric($account['time_created'])) {
            $account['time_created_view'] = $this->utilityService->date($account['time_created']);
        }

        return $account;
    }

    /**
     * @param $profile
     *
     * @return array
     */
    public function canonizeProfile($profile): array
    {
        if (empty($profile)) {
            return [];
        }

        if (is_object($profile)) {
            $profile = [
                'user_id'     => (int)$profile->getUserId(),
                'first_name'  => $profile->getFirstName(),
                'last_name'   => $profile->getLastName(),
                'birthdate'   => $profile->getBirthdate(),
                'gender'      => $profile->getGender(),
                'avatar'      => $profile->getAvatar(),
                'information' => $profile->getInformation(),
            ];
        } else {
            $profile = [
                'user_id'     => (int)$profile['user_id'],
                'first_name'  => $profile['first_name'],
                'last_name'   => $profile['last_name'],
                'birthdate'   => $profile['birthdate'],
                'gender'      => $profile['gender'],
                'avatar'      => $profile['avatar'],
                'information' => $profile['information'],
            ];
        }

        // Set information
        $profile['information'] = !empty($profile['information']) ? json_decode($profile['information'], true) : [];

        // Set avatar
        $profile = $this->avatarService->createUri($profile);

        return $profile;
    }

    /**
     * @param object|array $roleAccountList
     *
     * @return int|null
     */
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

    /**
     * @param $account
     *
     * @return array
     */
    public function canonizeAccountProfile($account): array
    {
        if (empty($account)) {
            return [];
        }

        if (is_object($account)) {
            $account = [
                'id'           => (int)$account->getId(),
                'name'         => $account->getName(),
                'identity'     => $account->getIdentity(),
                'email'        => $account->getEmail(),
                'mobile'       => $account->getMobile(),
                'status'       => (int)$account->getStatus(),
                'time_created' => $account->getTimeCreated(),
                'first_name'   => $account->getFirstName(),
                'last_name'    => $account->getLastName(),
                'birthdate'    => $account->getBirthdate(),
                'gender'       => $account->getGender(),
                'avatar'       => $account->getAvatar(),
                'information'  => $account->getInformation(),
            ];
        } else {
            $account = [
                'id'           => (int)$account['id'],
                'name'         => $account['name'] ?? '',
                'email'        => $account['email'] ?? '',
                'identity'     => $account['identity'] ?? '',
                'mobile'       => $account['mobile'] ?? '',
                'status'       => (int)$account['status'],
                'time_created' => $account['time_created'] ?? '',
                'first_name'   => $account['first_name'] ?? '',
                'last_name'    => $account['last_name'] ?? '',
                'birthdate'    => $account['birthdate'] ?? '',
                'gender'       => $account['gender'] ?? '',
                'avatar'       => $account['avatar'] ?? '',
                'information'  => $account['information'] ?? '',
            ];
        }

        // Set information
        $account['information'] = !empty($account['information']) ? json_decode($account['information'], true) : [];

        // Set time
        $account['time_created_view'] = ' - ';
        if (!empty($account['time_created']) && is_numeric($account['time_created'])) {
            $account['time_created_view'] = $this->utilityService->date($account['time_created']);
        }

        // Set avatar
        $account = $this->avatarService->createUri($account);

        return $account;
    }
}